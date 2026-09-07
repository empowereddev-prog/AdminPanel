<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\BannerImage;
use File;
use Illuminate\Http\Request;
use Validator;
use Yajra\Datatables\Datatables;
use App\Rules\BannerImageSize;


class BannerImageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
 // $data = BannerImage::orderBy('id', 'DESC')->get();
 // dd($data->toArray());
        if ($request->ajax()) {
            $data = BannerImage::orderBy('id', 'DESC')->get();
            
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    $btn = "";
                    $btn .= '<a href="' . url("admin/banners/" . $row->id . "/edit") . '" title="Edit" style="margin-left:5px;font-size:20px"><i class="mdi mdi-pencil""></i></a>&nbsp;';
                    $btn .= '<a href="' . url("admin/banners/" . $row->id) . '" class="delete" title="Delete" data-id="' . $row->id . '" style="margin-left:5px;font-size:20px"><span class="mdi mdi-trash-can"></span></a>&nbsp;';
                    return $btn;
                })

                ->addColumn('media', function ($row) {
                    $media = '';
                       

                            $media .= '<a href="' . url('uploads/banner_img/' . $row->banner_image) . '" target="_blank"><img src="' . url('uploads/banner_img/' . $row->banner_image) . '" style="width:80px;height:60px;"></a>';
                       
                    return $media ?: 'N/A'; // Return 'N/A' if neither image nor video is available
                })
                
                ->addColumn('status', function ($row) {
                    $status = "";
                    $btn = "";
                    if ($row->status == "active") {
                        $status = "checked";
                    } else {
                        $status = "";
                    }

                    $btn = '<label class="switch switch-primary switch-pill form-control-label mr-2" title="' . $row->status . '">
                    <input type="checkbox" class="switch-input form-check-input" ' . $status . ' data-id="' . $row->id . '" data-key="' . $row->status . '">
                    <span class="switch-label"></span>
                    <span class="switch-handle"></span>
                    </label>';
                    return $btn;
                })
                ->editColumn('type', function ($row) {
                    return  convertInCamelCase($row->type) ;
                })

                ->rawColumns(['action', 'status', 'media','type'])
                ->make(true);
        }
        return view('admin.banner.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $existingBannerTypes = BannerImage::pluck('type')->toArray();

        return view('admin.banner.create', compact('existingBannerTypes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if($request->check == 1){
            // dd($request);
            $validator = Validator::make($request->all(), [
                'banner_type' => 'required',
                'banner_title' => 'required|unique:banner_images',
                'video' => 'required|mimes:mp4',
            ], [
                'video.required' => 'The video file is required.',
                'video.mimes' => 'Please upload a valid MP4 video file.',
            ]);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }
        }else{
            $validator = Validator::make($request->all(), [
            'banner_type' => 'required',
            'banner_title' => 'required|unique:banner_images',
            // 'image' => 'required|image|mimes:jpeg,png,jpg,gif',
            'image' => ['required', 'image','mimes:jpeg,png,jpg,gif', new BannerImageSize($request->input('banner_type'))],

            ]);
            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }
        }
        $img = '';
        if ($request->hasFile('image')) {
            // dd($request);
            $img = 'banner-image-' . time() . '-' . rand(0, 99) . '.' . $request->image->extension();
            $request->image->move(public_path('uploads/banner_img/'), $img);
        }
      
        $title = $request->banner_title;
        $banner = [
            'banner_image' => $img,
            'banner_title' => $title,
            'type' => $request->banner_type,
        ];
        BannerImage::create($banner);
        return redirect('admin/banners')->with('success', 'Banner Added Successfully.');
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
        $data = BannerImage::where('id', $id)->first();
        // dd($data);
        return view('admin.banner.edit', compact('data'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {           
            $banner_type = BannerImage::where('id',$id)->value('type');
            $validator = Validator::make($request->all(), [
            // 'banner_type' => 'required',
            'banner_title' => 'required',
            'image' => [ new BannerImageSize($banner_type)],
            ]);
            // dd($request->all());
            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }
            // Handle image update
            if ($request->hasFile('image')) {
                $oldImage = BannerImage::find($id)->banner_image;
                $img = 'banner-image-' . time() . '-' . rand(0, 99) . '.' . $request->image->extension();
                $request->image->move(public_path('uploads/banner_img/'), $img);
    
                // Delete old image if it exists
                if (!empty($oldImage)) {
                    $oldImagePath = public_path('uploads/banner_img/' . $oldImage);
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }
    
                BannerImage::where('id', $id)->update(['banner_image' => $img]);
            }
        
        $banner = [
            // 'type' => $request->banner_type,
            'banner_title' => $request->banner_title,
        ];
        BannerImage::find($id)->update($banner);
        return redirect('admin/banners')->with('success', 'Banner Updated Successfully.');
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $bannerData = BannerImage::where('id', ($id))->first();
        $bannerData->delete();
        return redirect()->back()->with('success', 'Banner Deleted Successfully.');
    }

    // public function status($id)
    // {
    //     $banner = BannerImage::where('id', ($id))->first();
    //     if ($banner) {
    //     //     // Toggle the status
    //         $newStatus = ($banner->status === 'active') ? 'inactive' : 'active';
    //         BannerImage::where('id', ($id))->update(['status' => $newStatus]);
    //         return response()->json(['success' => 'Feature status updated successfully.'], 200);

    //     }
    //     return redirect()->back()->with('error', 'Status  not updated.');
       
    // }
    public function toggleStatus(string $id)
    {
        $banner = BannerImage::where('id', $id)->first();
        if (!$banner) {
            return response()->json(['error' => 'Banner not found.'], 404);
        }
        $newStatus = ($banner->status === 'active') ? 'inactive' : 'active';
        BannerImage::where('id', $id)->update(['status' => $newStatus]);
        return response()->json(['success' => 'Banner status updated successfully.'], 200);
    }

    public function update_banner_type(Request $request)
    { 
        $banner_type = $request->banner_type;
        $banner_id = $request->getid;

        BannerImage::where('id', $banner_id)->update(['type' => $banner_type]);
        // return response()->json($product);
    }

    

}
