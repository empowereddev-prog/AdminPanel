<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PermissionUser;
use App\Models\SchoolSubscription;
use App\Models\Subscription;
use Auth;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\Datatables\Datatables;

class PaymentManagementController extends Controller
{
    private $payment_management = 23;
    private $subadmin_menu_id = 23;

    public function historyIndex(Request $request)
    {
        $data['pre'] = $pre = PermissionUser::checkpermission(Auth::user()->id, $this->subadmin_menu_id);
        if (!isset($pre) || empty($pre)) {
            return redirect('dashboard');
        }

        if ($request->ajax()) {
            $query = $this->paymentHistoryQuery();
            $this->applyFilters($query, $request);

            return DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($search = $request->get('search')['value'] ?? '') {
                        $query->where(function ($q) use ($search) {
                            $q->where('payer_name', 'like', "%{$search}%")
                                ->orWhere('subscription_type', 'like', "%{$search}%")
                                ->orWhere('status', 'like', "%{$search}%");
                        });
                    }
                })
                ->addIndexColumn()
                ->addColumn('user_name', fn ($row) => $row->payer_name ?? '-')
                ->editColumn('subscription_type', fn ($row) => $this->formatPlan($row->subscription_type))
                ->editColumn('status', fn ($row) => $this->formatStatus($row->status) ?? '-')
                ->editColumn('start_date', fn ($row) => $this->formatPurchasedOn($row->start_date))
                ->addColumn('action', fn ($row) => $this->formatAction($row->payment_key))
                ->rawColumns(['user_name', 'subscription_type', 'status', 'action'])
                ->make(true);
        }

        return view('admin.payment.history_index', compact('pre'));
    }

    public function historyView($id)
    {
        $data = $this->resolvePaymentRow($id);
        if (!$data) {
            return redirect()->back()->with('error', 'Payment history not found.');
        }

        $data->start_date = $this->formatPurchasedOn($data->start_date);
        $data->end_date = $this->formatExpiresOn($data->end_date);

        return view('admin.payment.history_view', compact('data'));
    }

    public function historyDownload($id)
    {
        $data = $this->resolvePaymentRow($id);
        if (!$data) {
            return redirect()->back()->with('error', 'Payment history does not exist.');
        }

        if ($data->start_date) {
            $expires = $data->end_date
                ? Carbon::parse($data->end_date)
                : $this->expiresFromStart($data->start_date, $data->subscription_type);
            $data->start_date = $this->formatPurchasedOn($data->start_date);
            $data->end_date = $expires ? $expires->format('d-m-Y') : 'N/A';
        }

        $data->amount = $this->formatAmount($data->price);
        $pdf = PDF::loadView('pdf.payment_history', [
            'data' => $data,
            'image' => public_path('assets/images/Admin_logo.png'),
        ]);

        return $pdf->stream('Invoice_Payment_History_' . $data->subscription_type_id . '_' . now()->format('d-m-Y') . '.pdf');
    }

    /**
     * Consumer IAP (parent has no school_id) union school contracts.
     * School-parent entitlement grants are excluded.
     */
    private function paymentHistoryQuery()
    {
        // Exclude the zero-price school entitlement grants, not school users.
        // Filtering on users.school_id also hid genuine App Store purchases by
        // any parent who later joined a school, so real revenue vanished from
        // the report. grantParentEntitlement() always writes price '0' and a
        // real IAP purchase never does, so the price is the discriminator.
        // The column is varchar, hence the explicit cast.
        $iap = DB::table('subscriptions')
            ->leftJoin('users', 'subscriptions.user_id', '=', 'users.id')
            ->whereRaw('CAST(subscriptions.price AS DECIMAL(10,2)) > 0')
            ->select([
                DB::raw("CONCAT('iap-', subscriptions.id) as payment_key"),
                'users.name as payer_name',
                'subscriptions.subscription_type',
                'subscriptions.price',
                'subscriptions.start_date',
                'subscriptions.status',
                'subscriptions.created_at',
            ]);

        $schools = DB::table('school_subscriptions')
            ->join('schools', 'school_subscriptions.school_id', '=', 'schools.id')
            ->select([
                DB::raw("CONCAT('sch-', school_subscriptions.id) as payment_key"),
                'schools.name as payer_name',
                'school_subscriptions.subscription_type',
                'school_subscriptions.price',
                'school_subscriptions.start_date',
                'school_subscriptions.status',
                'school_subscriptions.created_at',
            ]);

        return DB::query()->fromSub($iap->unionAll($schools), 'payment_rows');
    }

    private function applyFilters($query, $request)
    {
        if ($request->has('status') && $request->get('status') != 'status') {
            $status = strtolower((string) $request->input('status'));
            if ($status === 'successful') {
                $query->whereIn('status', ['successful', 'Successful']);
            } else {
                $query->where('status', $request->input('status'));
            }
        }

        if ($request->has('date_range')) {
            $this->applyDateRangeFilter($query, $request->get('date_range'));
        }
    }

    private function applyDateRangeFilter($query, $dateRange)
    {
        $parts = explode(' - ', (string) $dateRange);
        if (count($parts) < 2) {
            return;
        }

        try {
            $startDate = Carbon::createFromFormat('d-m-Y', trim($parts[0]))->format('Y-m-d');
            $endDate = Carbon::createFromFormat('d-m-Y', trim($parts[1]))->format('Y-m-d');
        } catch (\Throwable $e) {
            return;
        }

        $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereNull('start_date')
                ->orWhereBetween('start_date', [$startDate, $endDate]);
        });
    }

    private function resolvePaymentRow(string $encoded)
    {
        [$source, $id] = $this->decodePaymentKey($encoded);
        if (!$source || !$id) {
            return null;
        }

        if ($source === 'school') {
            $row = SchoolSubscription::with('school')->find($id);
            if (!$row || !$row->school) {
                return null;
            }

            return (object) [
                'order_id' => 'SCH-' . $row->id,
                'subscription_type' => $row->subscription_type,
                'subscription_type_id' => 'school-contract-' . $row->id,
                'price' => $row->price,
                'start_date' => $row->start_date,
                'end_date' => $row->end_date,
                'status' => $row->status,
                'user' => (object) [
                    'name' => $row->school->name,
                    'email' => $row->school->email,
                ],
            ];
        }

        $row = Subscription::with('user')->find($id);
        if (!$row) {
            return null;
        }

        // Mirrors the list query above: a school entitlement grant is not a
        // payment. Keyed on price so a visible row never 404s when opened.
        if ((float) $row->price <= 0) {
            return null;
        }

        $row->order_id = $row->id;
        $row->subscription_type_id = $row->subscription_type_id ?: ('iap-' . $row->id);

        return $row;
    }

    private function decodePaymentKey(string $encoded): array
    {
        $raw = base64_decode($encoded, true);
        if ($raw === false || $raw === '') {
            return [null, null];
        }

        if (str_starts_with($raw, 'sch-')) {
            return ['school', (int) substr($raw, 4)];
        }
        if (str_starts_with($raw, 'iap-')) {
            return ['iap', (int) substr($raw, 4)];
        }
        if (ctype_digit($raw)) {
            return ['iap', (int) $raw];
        }

        return [null, null];
    }

    private function expiresFromStart($start, ?string $type): ?Carbon
    {
        try {
            $createdDate = Carbon::parse($start);
        } catch (\Throwable $e) {
            return null;
        }

        return match ($type) {
            'monthly' => $createdDate->copy()->addMonth(),
            'yearly' => $createdDate->copy()->addYear(),
            'quarterly', 'quaterly' => $createdDate->copy()->addMonths(3),
            default => $createdDate->copy()->addMonth(),
        };
    }

    private function formatPlan($plan)
    {
        return "<span class='plan $plan'>" . ucfirst((string) $plan) . '</span>';
    }

    private function formatAmount($amount)
    {
        return number_format((float) $amount, 2);
    }

    private function formatStatus($status)
    {
        return "<span class='sts $status'>" . ucfirst((string) $status) . '</span>';
    }

    private function formatPurchasedOn($purchasedOn)
    {
        return $purchasedOn ? Carbon::parse($purchasedOn)->format('d-m-Y') : 'N/A';
    }

    private function formatExpiresOn($expiresOn)
    {
        return $expiresOn ? Carbon::parse($expiresOn)->format('d-m-Y') : 'N/A';
    }

    private function formatAction($paymentKey)
    {
        $encoded = base64_encode((string) $paymentKey);
        $pdfUrl = url(route('payment.history_download', ['id' => $encoded]));
        $viewUrl = url(route('payment.history_view', ['id' => $encoded]));

        return '<a href="' . $viewUrl . '" title="View" style="margin-left:5px;font-size:20px"><i class="mdi mdi-eye"></i></a>&nbsp;'
            . '<a href="' . $pdfUrl . '" title="Download" style="margin-left:5px;font-size:20px" target="_blank"><i class="mdi mdi-download"></i></a>&nbsp;';
    }
}
