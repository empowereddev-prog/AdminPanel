<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EmailTemplate;
use Yajra\Datatables\Datatables;
use App\Http\Requests\Admin\EmailTemplateRequest;
use App\Models\PermissionUser;
use Auth;
class EmailTemplateController extends Controller
{
    private $content = 5;
    private $subadmin_menu_id = 6;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
       
        $data['pre'] = $pre = PermissionUser::checkpermission(Auth::user()->id, $this->subadmin_menu_id) ;
        if (!isset($pre) || empty($pre)) {
            return redirect('dashboard');
        }
        if ($request->ajax()) {
            if(!empty($pre) && $pre->is_modify == 'yes'){
            // $data = EmailTemplate::orderBy('id', 'DESC')->get();
            $emailTemplate = EmailTemplate::select('variable_name', \DB::raw('GROUP_CONCAT(subject) as subject, GROUP_CONCAT(description) as description'))
            ->groupBy('variable_name')
            ->get();
            return DataTables::of($emailTemplate)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    $btn = "";
                    $btn .= '<a href="' . url("email-template/" . $row->variable_name . "/edit") . '" title="Edit" style="margin-left:5px;font-size:20px"><i class="mdi mdi-pencil""></i></a>&nbsp;';
                    return $btn;
                })
                ->editColumn('variable_name', function ($row) {
                    return convertInCamelCase($row->variable_name);

                })
                ->editColumn('subject', function ($row) {
                    $row->subject = str_replace('../../..', url('/'), $row->subject);
                    return "<td style='max-width:500px;'><div>" . $row->subject . "</div></td>";

                })
                ->editColumn('description', function ($row) {
                    $row->description = str_replace('../../..', url('/'), $row->description);
                    return "<td style='max-width:500px;'><div>" . $row->description . "</div></td>";

                })

                ->rawColumns(['action', 'variable_name' , 'subject','description'])
                ->make(true);
        }
        else{
            return Datatables::of($emailTemplate)
            ->addIndexColumn()
            ->make(true);
        }
    }
        return view('admin.emailTemplate.index')->with($data);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $var_name)
    {
        $pre = PermissionUser::checkpermission(Auth::user()->id, $this->subadmin_menu_id);
        if(!empty($pre) && $pre->is_modify == 'yes'){
        $data = EmailTemplate::where('variable_name', $var_name)->get()->keyBy('language');
        return view('admin.emailTemplate.edit', compact('var_name','data'));
        }
        return redirect('dashboard');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(EmailTemplateRequest $request, string $var_name)
    {
        // dd($var_name,$request->all());
        // Update the email template
        $languages = ['english'];
          
        foreach ($languages as $language) {
        EmailTemplate::updateOrCreate(
            ['variable_name' => $var_name,'language' => $language],
            [
                'subject' => $request->input("subject_{$language}"), 
                'description' => $request->input("description_{$language}")]
        );
        }
        return redirect()->route('email-template.index')->with('success', 'Data updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
