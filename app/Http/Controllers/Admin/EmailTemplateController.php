<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EmailTemplate;
use Yajra\Datatables\Datatables;
use App\Http\Requests\Admin\EmailTemplateRequest;
use App\Models\PermissionUser;
use App\Services\School\CredentialsWorkbook;
use App\Services\TeacherSignupEmail;
use Auth;
class EmailTemplateController extends Controller
{
    private $content = 5;
    private $subadmin_menu_id = 6;

    /**
     * School templates that shipped alongside the contracted work but were not
     * part of it, hidden from the panel until billed. See config/scope.php.
     *
     * signup_school_user and signup_teacher are deliberately NOT here - those
     * are the contracted parent and teacher registration emails and must stay
     * configurable.
     */
    private const GATED_TEMPLATES = [
        'school_onboarded',
        'school_parent_invited',
        'school_offroster_attempt',
        'school_seat_threshold',
        'school_import_summary',
    ];

    /**
     * @return array<int, string> Templates to hide from the panel right now.
     */
    private function gatedTemplates(): array
    {
        return config('scope.school_extras') ? [] : self::GATED_TEMPLATES;
    }
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
            ->when($this->gatedTemplates(), function ($query, $hidden) {
                return $query->whereNotIn('variable_name', $hidden);
            })
            ->groupBy('variable_name')
            ->get();
            return DataTables::of($emailTemplate)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    $btn = "";
                    $btn .= '<a href="' . url("email-template/" . $row->variable_name . "/edit") . '" title="Edit" style="margin-left:5px;font-size:20px"><i class="mdi mdi-pencil""></i></a>&nbsp;';
                    $btn .= '<a href="' . route('email-template.preview', $row->variable_name) . '" title="Preview / send test" style="margin-left:5px;font-size:20px"><i class="mdi mdi-eye"></i></a>&nbsp;';
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
        // Hiding a row from the list does not close the edit URL behind it.
        if (in_array($var_name, $this->gatedTemplates(), true)) {
            return redirect()->route('email-template.index');
        }

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
        if (in_array($var_name, $this->gatedTemplates(), true)) {
            return redirect()->route('email-template.index');
        }

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

    /**
     * Placeholder values for every token the seeded templates use. A token with
     * no entry here renders as "[token]" so a gap is obvious in the preview
     * instead of silently blank.
     *
     * @return array<string,string>
     */
    private function sampleValues(): array
    {
        return [
            'name' => 'Sample Name',
            'user_name' => 'Sample Name',
            'username' => 'sample_username',
            'email' => 'sample@example.com',
            'password' => 'Sample@1234',
            'school_name' => 'Sample School',
            'school_code' => 'SCH-123456',
            'student_count' => '25',
            'reset_days' => (string) \App\Http\Controllers\Admin\SchoolStudentController::RESET_LINK_DAYS,
            'app_link' => (string) config('deeplink.web_fallback_url', 'https://empoweredhealth.asia/'),
            'year' => (string) date('Y'),
        ];
    }

    /**
     * @return array<string,mixed> data for the template, plus sample attachments
     */
    private function sampleData(string $var_name, ?string $variables): array
    {
        $sample = $this->sampleValues();
        $data = [];
        foreach (explode(',', (string) $variables) as $token) {
            $key = trim(str_replace(['{', '}'], '', $token));
            if ($key !== '') {
                $data[$key] = $sample[$key] ?? '[' . $key . ']';
            }
        }

        if ($var_name === 'school_student_credentials') {
            $data['attachments'] = [[
                'name' => 'student-credentials.xlsx',
                'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'content' => CredentialsWorkbook::build([
                    ['Name', 'Email', 'Username', 'Temporary Password', 'Password Reset Link'],
                    ['Sample Student', 'student@example.com', 'sample_student', 'Stu@Sample1', url('/reset-password/sample-token')],
                ]),
            ]];
        }

        return $data;
    }

    private function authorizeTemplate(string $var_name): EmailTemplate
    {
        $pre = PermissionUser::checkpermission(Auth::user()->id, $this->subadmin_menu_id);
        abort_unless(!empty($pre) && $pre->is_modify == 'yes', 403);
        abort_if(in_array($var_name, $this->gatedTemplates(), true), 404);

        return EmailTemplate::where('variable_name', $var_name)->where('language', 'english')->firstOrFail();
    }

    /** Render the template with sample data, exactly as a recipient would see it. */
    public function preview(string $var_name)
    {
        $template = $this->authorizeTemplate($var_name);
        $rendered = ___render_email_template($var_name, $this->sampleData($var_name, $template->variables), 'english');

        $body = $rendered['body'];
        if (TeacherSignupEmail::appliesTo($var_name)) {
            $body = app(TeacherSignupEmail::class)->previewHtml($body);
        }

        return view('admin.emailTemplate.preview', [
            'var_name' => $var_name,
            'subject' => $rendered['subject'],
            'body' => $body,
            'hasAttachment' => $var_name === 'school_student_credentials',
        ]);
    }

    /** Send the sample email to one address so it can be checked in a real inbox. */
    public function testSend(Request $request, string $var_name)
    {
        $template = $this->authorizeTemplate($var_name);
        $request->validate(['to' => 'required|email|max:255']);

        $sent = ___mail_sender($request->input('to'), $var_name, $this->sampleData($var_name, $template->variables), 'english');

        return redirect()->route('email-template.preview', $var_name)->with(
            $sent ? 'success' : 'error',
            $sent ? 'Test email sent to ' . $request->input('to') . '.' : 'The test email could not be sent. Check the mail settings and the log.'
        );
    }
}
