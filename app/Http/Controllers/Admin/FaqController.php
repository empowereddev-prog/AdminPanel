<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FaqRequest;
use App\Models\Faq;
use Illuminate\Http\Request;
use Yajra\Datatables\Datatables;
use App\Models\PermissionUser;
use Auth;

class FaqController extends Controller
{
    private $content = 5;
    private $subadmin_menu_id = 8;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $data['pre'] = $pre = PermissionUser::checkpermission(Auth::user()->id, $this->subadmin_menu_id);
        if (!isset($pre) || empty($pre)) {
            return redirect('dashboard');
        }
        if ($request->ajax()) {
            if (!empty($pre) && $pre->is_modify == 'yes') {
                $faq = Faq::orderBy('type', 'ASC')->get();

                return DataTables::of($faq)
                    ->addIndexColumn()
                    ->addColumn('action', function ($row) {
                        $btn = "";
                        $btn .= '<a href="' . url("faq/" . $row->id . "/edit") . '" title="Edit" style="margin-left:5px;font-size:20px"><i class="mdi mdi-pencil""></i></a>&nbsp;';
                        $btn .= '<a href="' . url("faq/" . $row->id) . '" class="delete" title="Delete" data-id="' . $row->id . '" style="margin-left:5px;font-size:20px"><span class="mdi mdi-trash-can"></span></a>&nbsp;';
                        return $btn;
                    })
                    ->editColumn('language', function ($row) {
                        return ucfirst($row->language); // Transform to CamelCase
                    })
                    ->editColumn('type', function ($row) {
                        return ucfirst($row->type); // Transform to CamelCase
                    })
                    ->editColumn('answer', function ($row) {
                        $answer = ucfirst($row->answer);
                        return strlen($answer) > 100 ? substr($answer, 0, 100) . '...' : $answer;
                    })
                    ->editColumn('question', function ($row) {
                        $question = ucfirst($row->question);
                        return strlen($question) > 100 ? substr($question, 0, 100) . '...' : $question;
                    })
                    // ->addColumn('status', function ($row) {
                    //     $status = "";
                    //     $btn = "";
                    //     if ($row->status == "active") {
                    //         $status = "checked";
                    //     } else {
                    //         $status = "";
                    //     }

                    //     $btn = '<label class="switch switch-primary switch-pill form-control-label mr-2" title="' . ucfirst($row->status) . '">
                    // <input type="checkbox" class="switch-input form-check-input" ' . $status . ' data-id="' . $row->id . '" data-key="' . $row->status . '">
                    // <span class="switch-label"></span>
                    // <span class="switch-handle"></span>
                    // </label>';
                    //     return $btn;
                    // })
                    ->editColumn('status', function ($row) {
                        return "<span class='sts $row->status'>" . ucfirst($row->status) . "</span>";
                    })

                    ->rawColumns(['action', 'type', 'status', 'answer', 'question'])
                    ->make(true);
            } else {
                return Datatables::of($faq)
                    ->addIndexColumn()
                    ->make(true);
            }
        }
        return view('admin.faq.index')->with($data);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $pre = PermissionUser::checkpermission(Auth::user()->id, $this->subadmin_menu_id);
        if (!empty($pre) && $pre->is_modify == 'yes') {
            return view('admin.faq.create');
        }
        return redirect('dashboard');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(FaqRequest $request)
    {
        $faq = array();
        $faq['question'] = $request->question;
        $faq['answer'] = $request->answer;
        $faq['language'] = 'english';
        $faq['type'] = $request->type;
        Faq::create($faq);
        return redirect('faq')->with('success', 'Faq Added Successfully.');
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
    public function edit(string $id)
    {
        $pre = PermissionUser::checkpermission(Auth::user()->id, $this->subadmin_menu_id);
        if (!empty($pre) && $pre->is_modify == 'yes') {
            $data = Faq::find($id);
            return view('admin.faq.edit', compact('data'));
        }
        return redirect('dashboard');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(FaqRequest $request, string $id)
    {
        Faq::where('id', $id)->update(['status'=> $request->status,'question' => $request->question, 'type' => $request->type, 'answer' => $request->answer]);
        return redirect()->route('faq.index')->with('success', 'Data updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $faqData = Faq::where('id', ($id))->first();
        $faqData->delete();
        return redirect()->route('faq.index')->with('success', 'Faq Deleted Successfully.');
    }
    public function changeFaqStatus($id)
    {
        $faq = Faq::where('id', $id)->first();
        if ($faq) {
            if ($faq->status == 'active') {
                $faq->status = 'inactive';
            } else {
                $faq->status = 'active';
            }
            $faq->save();
            return redirect('faq')->with('success', 'Status updated successfully.');
        }
    }
}
