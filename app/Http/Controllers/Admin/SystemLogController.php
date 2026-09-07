<?php

namespace App\Http\Controllers\Admin;
use DataTables;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use OwenIt\Auditing\Models\Audit;
use Carbon\Carbon;
use Illuminate\Support\Str;


class SystemLogController extends Controller
{
    public function index()
    {
        return view('admin.systemLog.index');
    }

    public function getUsers(Request $request)
    {
        // dd($request->get('sort'));
        if ($request->ajax()) {
            // Base query
            $query = Audit::where('user_type', null); 

            // Date range filtering
            if ($request->has('date_range')) {
                $dateRange = explode(' - ', $request->get('date_range'));
                $startDate = Carbon::createFromFormat('d-m-Y', $dateRange[0])->startOfDay()->format('Y-m-d H:i:s');
                $endDate = Carbon::createFromFormat('d-m-Y', $dateRange[1])->endOfDay()->format('Y-m-d H:i:s');
                $query->whereBetween('created_at', [$startDate, $endDate]);
            }
    
            // Sorting
            $sortDirection = in_array($request->get('sort'), ['asc', 'desc']) ? $request->get('sort') : 'desc';
            $query->orderBy('created_at', $sortDirection);
    
            // Fetch data
            $data = $query->get();
            // dd($data);
            // Add static user value to each data entry
            foreach ($data as $item) {
                $item->user = 'Admin'; // Add static 'user' field
                $item->auditable_type = class_basename($item->auditable_type);
            }
            return DataTables::of($data)
                ->addIndexColumn()
                ->editColumn('event', function ($row) {
                    return ucfirst($row->event);
                })
                ->editColumn('created_at', function ($row) {
                    return Carbon::parse($row->created_at)->format('d-m-Y');
                })
                ->editColumn('old_values', function ($row) {
                    $old_json_value = json_encode($row->old_values);
                    $oldValues = json_decode($old_json_value, true);
                if (is_array($oldValues)) {
                    $html = '';
                    foreach ($oldValues as $key => $value) {
                        $html .= '<strong>' . convertInCamelCase(htmlspecialchars($key)) . ':</strong> ' . htmlspecialchars($value) . '<br>';
                    }
                    return $html;
                }
                return '-';
            })
            ->editColumn('new_values', function ($row) {
                $new_json_value = json_encode($row->new_values);
                $newValues = json_decode($new_json_value, true);
            if (is_array($newValues)) {
                $html = '';
                foreach ($newValues as $key => $value) {
                    $html .= '<strong>' . convertInCamelCase(htmlspecialchars($key)) . ':</strong> ' . htmlspecialchars($value) . '<br>';
                }
                return $html;
            }
            return '-';
        })
                ->rawColumns(['old_values', 'new_values','created_at'])
                ->make(true);
        }
    }

}
