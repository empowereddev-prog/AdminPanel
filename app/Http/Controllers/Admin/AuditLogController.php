<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use OwenIt\Auditing\Models\Audit;
use Yajra\DataTables\Facades\DataTables;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $audits = Audit::latest()->get();
            return DataTables::of($audits)
                ->addIndexColumn()
                ->addColumn('old_values', function ($row) {
                    return !empty($row->old_values) 
                        ? json_encode($row->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) 
                        : 'N/A';
                })
                ->addColumn('new_values', function ($row) {
                    return !empty($row->new_values) 
                        ? json_encode($row->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) 
                        : 'N/A';
                })
                ->rawColumns(['old_values', 'new_values'])
                
                ->make(true);
        }
        return view('admin.audit.index');
    }
}
