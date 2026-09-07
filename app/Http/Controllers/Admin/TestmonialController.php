<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Testmonial;
use File;
use Illuminate\Http\Request;
use Validator;
use Yajra\Datatables\Datatables;
use App\Rules\ReviewProfleImageSize;


class TestmonialController extends Controller
{
    public function index(Request $request)
    {
 // $data = BannerImage::orderBy('id', 'DESC')->get();
 // dd($data->toArray());
        if ($request->ajax()) {
            $data = Testmonial::orderBy('id', 'DESC')->get();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    $btn = "";
                    $btn .= '<a href="' . url("admin/testimonial/" . $row->id . "/edit") . '" title="Edit" style="margin-left:5px;font-size:20px"><i class="mdi mdi-pencil""></i></a>&nbsp;';
                    $btn .= '<a href="' . url("admin/testimonial/" . $row->id) . '" class="delete" title="Delete" data-id="' . $row->id . '" style="margin-left:5px;font-size:20px"><span class="mdi mdi-trash-can"></span></a>&nbsp;';
                    return $btn;
                })

                ->addColumn('image', function ($row) {
                    $image = '';
                            $image .= '<a href="' . url('uploads/testmonial/' . $row->image) . '" target="_blank"><img src="' . url('uploads/testmonial/' . $row->image) . '" style="width:80px;height:60px;"></a>';
                       
                    // }
                    return $image ?: 'N/A'; // Return 'N/A' if neither image nor video is available
                })
                

                ->rawColumns(['action', 'image'])
                ->make(true);
        }
        return view('admin.testmonial.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.testmonial.add');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
    
            $validator = Validator::make($request->all(), [
            'name' => 'required|min:3|max:15|regex:/^[a-zA-Z.\s]+$/',
            'about' => 'required',
            'image' => ['required', 'image','mimes:jpeg,png,jpg', new ReviewProfleImageSize()],

            'review' => 'required|min:2|max:500',
           'zh_CN_review' => 'required|min:2|max:500',
           'zh_TW_review' => 'required|min:2|max:500',

            ],[
                'zh_CN_review.required' => 'The reviews field in Simplified chinese language is required',
                'zh_CN_review.min' => 'Please Enter minimum 2 characters',
                'zh_CN_review.max' => 'Please Enter maximum 500 characters',
                'zh_TW_review.required' => 'The reviews field in Traditional chinese language is required',
                'zh_TW_review.min' => 'Please Enter minimum 2 characters',
                'zh_TW_review.max' => 'Please Enter maximum 500 characters',
            ]);
            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }else{
            $img = '';
            if ($request->hasFile('image')) {
                $img = 'profile-image-' . time() . '-' . rand(0, 99) . '.' . $request->image->extension();
                $request->image->move(public_path('uploads/testmonial/'), $img);
            }
        
        // $title = $request->banner_title;
        // echo($title);
        // exit;
        $testmonial = [
            'image' => $img,
            'name' => $request->name,
            'about' => $request->about,
            'review_comments' => $request->review,
            'zh_CN_review' =>$request->zh_CN_review,
            'zh_TW_review' =>$request->zh_TW_review,
        ];
        Testmonial::create($testmonial);
        return redirect('admin/testimonial')->with('success', 'Testmonial Added Successfully.');
    }
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
        $data = Testmonial::where('id', $id)->first();
        // dd($data);
        return view('admin.testmonial.edit', compact('data'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // dd($request->all());

            $validator = Validator::make($request->all(), [
                'name' => 'required|min:3|max:15|regex:/^[a-zA-Z.\s]+$/',
                'about' => 'required',
                'image' => [ 'nullable','image','mimes:jpeg,png,jpg', new ReviewProfleImageSize()],
                'review' => 'required|min:2|max:500',
               'zh_CN_review' => 'required|min:2|max:500',
               'zh_TW_review' => 'required|min:2|max:500',
    
                ],[
                    'zh_CN_review.required' => 'The reviews field in Simplified chinese language is required',
                    'zh_CN_review.min' => 'Please Enter minimum 2 characters',
                    'zh_CN_review.max' => 'Please Enter maximum 500 characters',
                    'zh_TW_review.required' => 'The reviews field in Traditional chinese language is required',
                    'zh_TW_review.min' => 'Please Enter minimum 2 characters',
                    'zh_TW_review.max' => 'Please Enter maximum 500 characters',
                ]);
            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }
            // Handle image update
            if ($request->hasFile('image')) {
                $oldImage = Testmonial::find($id)->image;
                $img = 'profile-image-' . time() . '-' . rand(0, 99) . '.' . $request->image->extension();
                $request->image->move(public_path('uploads/testmonial/'), $img);
    
                // Delete old image if it exists
                if (!empty($oldImage)) {
                    $oldImagePath = public_path('uploads/testmonial/' . $oldImage);
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }
            //    dd($id,$img);
                Testmonial::where('id', $id)->update(['image' => $img]);
            }
            $testmonial = [
                'name' => $request->name,
                'about' => $request->about,
                'review_comments' => $request->review,
                'zh_CN_review' =>$request->zh_CN_review,
                'zh_TW_review' =>$request->zh_TW_review,
            ];
        Testmonial::find($id)->update($testmonial);
        return redirect('admin/testimonial')->with('success', 'Testmonial Updated Successfully.');
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $testmonialData = Testmonial::where('id', ($id))->first();
        $testmonialData->delete();
        return redirect()->back()->with('success', 'Testmonial Deleted Successfully.');
    }


    
}
