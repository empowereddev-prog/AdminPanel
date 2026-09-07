<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use Carbon\Carbon;
use Yajra\Datatables\Datatables;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use App\Models\PermissionUser;
use Auth;

class PaymentManagementController extends Controller
{

    private $payment_management = 23;
    private $subadmin_menu_id = 23;
    /**
     * Display a listing of the payment history.
     */
    public function historyIndex(Request $request)
    {
        $data['pre'] = $pre = PermissionUser::checkpermission(Auth::user()->id, $this->subadmin_menu_id);
        if (!isset($pre) || empty($pre)) {
            return redirect('dashboard');
        }
        if ($request->ajax()) {
            $query = Subscription::select('subscriptions.*', 'users.name as user_name')
                ->leftJoin('users', 'subscriptions.user_id', '=', 'users.id');
            $this->applyFilters($query, $request);
            $data = $this->getData($query, $request);
            if (!empty($pre) && $pre->is_modify == 'yes') {
                // return DataTables::of($data)
                return DataTables::of($query)
                    ->filter(function ($query) use ($request) {
                        if ($search = $request->get('search')['value']) {
                            $query->where(function ($q) use ($search) {
                                $q->where('users.name', 'like', "%{$search}%")
                                    ->orWhere('subscriptions.subscription_type', 'like', "%{$search}%")
                                    ->orWhere('subscriptions.status', 'like', "%{$search}%");
                            });
                        }
                    })
                    ->addIndexColumn()
                    ->addColumn('user_name', fn($row) => $row->user_name ?? '-')
                    ->editColumn('subscription_type', fn($row) => $this->formatPlan($row->subscription_type))
                    ->editColumn('status', fn($row) => $this->formatStatus($row->status) ?? '-')
                    ->editColumn('start_date', fn($row) => $this->formatPurchasedOn($row->start_date))
                    ->addColumn('action', fn($row) => $this->formatAction($row->id))
                    ->rawColumns(['user_name', 'subscription_type', 'status', 'action'])
                    ->make(true);
            } else {
                return DataTables::of($query)
                    ->filter(function ($query) use ($request) {
                        if ($search = $request->get('search')['value']) {
                            $query->where(function ($q) use ($search) {
                                $q->where('users.name', 'like', "%{$search}%")
                                    ->orWhere('subscriptions.subscription_type', 'like', "%{$search}%")
                                    ->orWhere('subscriptions.status', 'like', "%{$search}%");
                            });
                        }
                    })
                    ->addIndexColumn()
                    ->addColumn('user_name', fn($row) => $row->user_name ?? '-')
                    ->editColumn('subscription_type', fn($row) => $this->formatPlan($row->subscription_type))
                    ->editColumn('status', fn($row) => $this->formatStatus($row->status) ?? '-')
                    ->editColumn('start_date', fn($row) => $this->formatPurchasedOn($row->start_date))
                    ->addColumn('action', fn($row) => $this->formatAction($row->id))
                    ->rawColumns(['user_name', 'subscription_type', 'status', 'action'])
                    ->make(true);
            }
        }

        return view('admin.payment.history_index', compact('pre'));
    }

    /**
     * View payment history.
     */
    public function historyView($id)
    {
        $decodedId = base64_decode($id);
        $data = Subscription::where('id', $decodedId)->with('user')->first();
        if ($data) {
            $data->start_date = $this->formatPurchasedOn($data->start_date);
            $data->end_date = $this->formatExpiresOn($data->end_date);
            return view('admin.payment.history_view', compact('data'));
        } else {
            return redirect()->back()->with('error', 'Payment history not found.');
        }
    }

    //Payment Management Helper function

    private function applyFilters($query, $request)
    {

        if ($request->has('status') && $request->get('status') != 'status') {
            $query->where('subscriptions.status', $request->input('status'));
        }

        if ($request->has('date_range')) {
            $this->applyDateRangeFilter($query, $request->get('date_range'));
        }
        if ($request->has('sort') && $request->get('sort') !== 'sortBy') {
            $sortDirection = $request->get('sort');
            $query->orderBy('start_date', $sortDirection);
        }
    }

    private function applyDateRangeFilter($query, $dateRange)
    {
        $dateRange = explode(' - ', $dateRange);
        $startDate = Carbon::createFromFormat('d-m-Y', $dateRange[0])->format('Y-m-d');
        $endDate = Carbon::createFromFormat('d-m-Y', $dateRange[1])->format('Y-m-d');

        $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereNull('start_date')
                ->orWhereBetween('start_date', [$startDate, $endDate]);
        });
    }

    private function getData($query, $request)
    {
        if ($request->has('order')) {
            $columnIndex = $request->order[0]['column'];
            $columnName = $request->columns[$columnIndex]['data'];
            $sortDirection = $request->order[0]['dir'];

            // Define allowed columns for sorting
            $orderableColumns = ['id', 'user_name', 'subscription_type', 'start_date'];

            // Map DataTables alias to real DB fields
            $columnMap = [
                'user_name' => 'users.name',
                'subscription_type' => 'subscriptions.subscription_type',
                'start_date' => 'subscriptions.created_at',
                'id' => 'subscriptions.id',
                // 'amount' => 'subscriptions.price'
            ];

            if (in_array($columnName, $orderableColumns)) {
                $query->orderBy($columnMap[$columnName] ?? $columnName, $sortDirection);
            } else {
                $query->latest('subscriptions.created_at');
            }
        } else {
            $query->latest('subscriptions.created_at');
        }

        return $query;
    }


    private function formatPlan($plan)
    {
        return "<span class='plan $plan'>" . ucfirst($plan) . "</span>";
    }

    private function formatAmount($amount)
    {
        return  number_format($amount, 2);
    }

    private function formatStatus($status)
    {
        return "<span class='sts $status'>" . ucfirst($status) . "</span>";
    }

    private function formatPurchasedOn($purchasedOn)
    {
        return $purchasedOn ? Carbon::parse($purchasedOn)->format('d-m-Y') : 'N/A';
    }
    private function formatExpiresOn($expiresOn)
    {
        return $expiresOn ? Carbon::parse($expiresOn)->format('d-m-Y') : 'N/A';
    }

    private function formatAction($id)
    {
        $pdfUrl = url(route('payment.history_download', ['id' => base64_encode($id)]));
        $viewUrl = url(route('payment.history_view', ['id' => base64_encode($id)]));
        return '<a href="' . $viewUrl . '" title="View" style="margin-left:5px;font-size:20px"><i class="mdi mdi-eye"></i></a>&nbsp;' .
            '<a href="' . $pdfUrl . '" title="Download" style="margin-left:5px;font-size:20px" target="_blank"><i class="mdi mdi-download"></i></a>&nbsp;';
    }

    public function historyDownload($id)
    {
        $decodedId = base64_decode($id);
        $data = Subscription::with('user')->where('id', $decodedId)->first();
        $createdDate = Carbon::createFromFormat('Y-m-d', $data->start_date);
        if ($data->subscription_type == 'monthly') {
            $expiresDate = $createdDate->copy()->addMonth();
        } elseif ($data->subscription_type == 'yearly') {
            $expiresDate = $createdDate->copy()->addYear();
        } elseif ($data->subscription_type == 'quaterly') {
            $expiresDate = $createdDate->copy()->addMonth(3);
        }
        if ($data) {
            if ($data->start_date) {
                $data->start_date = $this->formatPurchasedOn($data->start_date);
                $data->end_date = $expiresDate->format('d-m-Y');
            }
            // dd($data->expires_on);
            $data->amount =  $this->formatAmount($data->price);
            $pdf = PDF::loadView('pdf.payment_history', [
                'data' => $data,
                'image' => public_path('assets/images/Admin_logo.png')
            ]);
            return $pdf->stream('Invoice_Payment_History_' . $data->subscription_type_id . '_' . now()->format('d-m-Y') . '.pdf');
        } else {
            return redirect()->back()->with('error', 'Payment history does not exist.');
        }
    }
}
