<?php

namespace App\Http\Controllers\Admin;

use App\Models\ContactUs;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DataTables;
use App\Models\PermissionUser;
use Auth;

class ContactUsController extends Controller
{
    private $content = 5;
    private $subadmin_menu_id = 11;

    public function index(Request $request)
    {
        $data['title'] = "Contact Us";
        $contacts  = ContactUs::orderBy('id', 'DESC')->get();
        // dd($contacts);
        // dd($request->ajax());

        $data['pre'] = $pre = PermissionUser::checkpermission(Auth::user()->id, $this->subadmin_menu_id);
        if (!isset($pre) || empty($pre)) {
            return redirect('dashboard');
        }
        if ($request->ajax()) {
            if (!empty($pre) && $pre->is_modify == 'yes') {
                return Datatables::of($contacts)
                    ->addIndexColumn()
                    ->addColumn('action', function ($contacts) {
                        $btn = '';
                        if ($contacts->reply == '') {
                            $btn .= '<a href="' . route('contact-us.edit', $contacts->id) . '" title="Reply" class="edit btnn"><i class="mdi mdi-reply" style="font-size: 20px;"></i></a> ';
                        } else {
                            $btn .= '<a href="' . route('contact-us.show', $contacts->id) . '" title="View Details" class="edit btnn"><i class="mdi mdi-eye" style="font-size: 20px;"></i></a> ';
                        }
                        $btn .= '<a href="' . url("contact-us/" . $contacts->id) . '" class="delete btnn" title="Delete" data-id="' . $contacts->id . '"><i class="mdi mdi-trash-can" style="font-size: 20px;"></i></a>';
                        return $btn;
                    })
                    
                    ->rawColumns(['action'])
                    ->make(true);
            } else {
                return Datatables::of($contacts)
                    ->addIndexColumn()
                    ->make(true);
            }
        }
        return view('admin.contact-us.index')->with($data);
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
        $data['title'] = 'Contact Details';
        $data['contact'] = ContactUs::where('id', $id)->first();
        return view('admin.contact-us.details')->with($data);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $pre = PermissionUser::checkpermission(Auth::user()->id, $this->subadmin_menu_id);
        if (!empty($pre) && $pre->is_modify == 'yes') {
            $data['title'] = 'Reply To User';
            $data['contact'] = ContactUs::where('id', $id)->first();
            return view('admin.contact-us.edit')->with($data);
        }
        return redirect('dashboard');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $language = 'english';
        $validators = \Validator::make($request->all(), [
            'reply' => 'required|min:10|regex:/^[^<>]*$/',
        ]);
        if ($validators->passes()) {
            $updateArr = [
                'reply' => $request['reply'],
            ];
            ContactUs::where('id', $id)->update($updateArr);
            $contact = ContactUs::where('id', $id)->first();
            $data = [
                'name' => $contact->name,
                'email' => $contact->email,
                'message' => $contact->message,
                'reply' => $contact->reply,
            ];
            ___mail_sender($data['email'], 'contact_reply', $data, $language);
            return redirect('contact-us')->with('updated', 'Replied Successfully!');
        } else {
            return \Redirect::back()->withErrors($validators->errors())->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        // $data = ContactUs::where('id',$id)->delete();
        // if($data){
        //     return response()->json([
        //     'data'      => $data,
        //     'status'    => 'success',
        //     ]);
        // }
        $data = ContactUs::where('id', ($id))->first();
        $data->delete();
        return redirect()->route('contact-us.index')->with('success', 'Contact Deleted Successfully.');
    }

    public function storeContact(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'name' => 'required|min:3|max:30|regex:/^[^-.]+$/',
            'email' => 'required |regex:/\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})/',
            'subject' => 'required',
            'message' => 'required|max:500',
        ], [
            'name.required' => 'Please enter your Name.',
            'name.regex' => 'Name should contain only characters.',
            'name.max' => 'Name can not be more than 30 characters.',
            'subject.required' => 'Please fill Subject.',
            'message.required' => 'Please enter Message.',
            'email.required' => 'Please Enter Email.',
            'email.regex' => 'Please Enter Valid Email Format.',
        ]);

        $status = User::where('email', $request->email)->where('user_role_id', 2)->value('status');
        // if($status == 'inactive'){
        //     return response()->json(['success' => false, 'message' =>$language == 'english' ? 'This account has been inactive, Please contact to admin.':'Esta conta está inativa. Entre em contato com o administrador.'], 404); 
        // }
        // if ($validator->fails()) {
        //     return response()->json(['success' => false, 'message' => $validator->errors()->first(), 'data' => (object) []], 422);
        // }
        $data = ContactUs::create([
            'message' => $request['message'],
            'subject' => $request['subject'],
            'name' => $request['name'],
            'email' => $request['email'],

        ]);
        // $success = [
        //     'message' => $request['message'],
        //     'subject' => $request['subject'],
        //     'name' => $request['name'],
        //     'email' => $request['email'],
        // ];

        // sendMail($request['email'],$request->name,'contact_us',$success); 
        return redirect('contact')->with('message', 'We got your message.We will get in touch with you as soon as possible.');
    }
}
