<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationTemplate;
use App\Models\PermissionUser;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Auth;
use Illuminate\Support\Facades\DB;

class NotificationTemplateController extends Controller
{
    private $subadmin_menu_id = 36;

    /**
     * Index Page (List of Templates)
     */
    public function index(Request $request)
    {
        $data['pre'] = $pre = PermissionUser::checkpermission(Auth::user()->id, $this->subadmin_menu_id);

        if (!isset($pre) || empty($pre)) {
            return redirect('dashboard');
        }

        if ($request->ajax()) {
            $templates = NotificationTemplate::select('id', 'variable_name', 'subject')->get();

            return DataTables::of($templates)
                ->addIndexColumn()
                ->editColumn('variable_name', function ($row) {
                    return convertInCamelCase($row->variable_name);
                })
                ->addColumn('action', function ($row) {
                    return '<a href="' . route("notification-template.edit", $row->id) . '" 
                                title="Edit" style="margin-left:5px;font-size:20px">
                                <i class="mdi mdi-pencil"></i></a>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('admin.notification_template.index', $data);
    }

    /**
     * Edit Page
     */
    public function edit($id)
    {
        $data['template'] = NotificationTemplate::findOrFail($id);
        return view('admin.notification_template.edit', $data);
    }

    /**
     * Update Template
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'subject' => 'required|max:150',
            'description' => 'required',
        ]);

        $template = NotificationTemplate::findOrFail($id);
        $template->update([
            'subject' => $request->subject,
            'description' => $request->description,
        ]);

        return redirect()->route('notification-template.index')->with('success', 'Notification Template Updated Successfully');
    }
}
