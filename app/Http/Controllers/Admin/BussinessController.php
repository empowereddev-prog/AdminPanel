<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BusinessRequest;
use App\Models\Bussiness;
use Illuminate\Http\Request;
use Yajra\Datatables\Datatables;
use Illuminate\Support\Facades\File;
use Validator;

class BussinessController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Bussiness::orderBy('id', 'DESC')->get();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    $btn = "";
                    $btn .= '<a href="' . url("admin/business/" . $row->id . "/edit") . '" title="Edit" style="margin-left:5px;font-size:20px"><i class="mdi mdi-pencil""></i></a>&nbsp;';
                    $btn .= '<a href="' . url("admin/business/" . $row->id) . '" class="delete" title="Delete" data-id="' . $row->id . '" style="margin-left:5px;font-size:20px"><span class="mdi mdi-trash-can"></span></a>&nbsp;';
                    return $btn;
                })

                ->addColumn('image', function ($row) {
                    $image_url = $row->icon ? '<a href="' . url('uploads/business_icon/' . $row->icon) . '" target="_blank"><img src="' . url('uploads/business_icon/' . $row->icon) . '" style="width:80px;height:60px;"></a>' : 'N/A';
                    return $image_url;
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

                ->rawColumns(['action', 'status', 'image'])
                ->make(true);
        }
        return view('admin.business.index');

    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.business.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'icon' => 'required|image|mimes:jpeg,png,jpg,gif',
            
        ],[
            'icon.required' => 'The image field is required.',
             'icon.image' => 'The file must be an image.',
            'icon.mimes' => 'The image must be a file of type: jpg, jpeg, png, svg.',
           
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                             ->withErrors($validator)
                             ->withInput();
        }
        $img = '';
        if ($request->hasFile('icon')) {
            $img = 'business-icon-' . time() . '-' . rand(0, 99) . '.' . $request->icon->extension();
            $request->icon->move(public_path('uploads/business_icon/'), $img);
        }
        $business = [
            'icon' => $img,
        ];
        Bussiness::create($business);
        return redirect('admin/business')->with('success', 'Business Partner Added Successfully.');

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
        $data = Bussiness::where('id', $id)->first();
        return view('admin.business.edit', compact('data'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'icon' => 'image|mimes:jpeg,png,jpg,gif',
            
        ],[
            'icon.required' => 'The image field is required.',
             'icon.image' => 'The file must be an image.',
            'icon.mimes' => 'The image must be a file of type: jpg, jpeg, png, svg.',
           
        ]);

        if ($validator->fails()) {
            return redirect()->back()
        ->withErrors($validator)
        ->withInput();
        }

        $img = '';
        if ($request->hasFile('icon')) {
            $img = 'business-icon-' . time() . '-' . rand(0, 99) . '.' . $request->icon->extension();
            $request->icon->move(public_path('uploads/business_icon/'), $img);
            $oldpic = Bussiness::find($id)->pluck('icon')[0];
            File::delete(public_path($oldpic));
            Bussiness::where('id', $id)->update(['icon' => $img]);
        }

        return redirect('admin/business')->with('success', 'Business Partner Updated Successfully.');

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $businessData = Bussiness::where('id', ($id))->first();
        $businessData->delete();
        return redirect()->back()->with('success', 'Business Deleted Successfully.');
    }

    public function status($id)
    {
        $business = Bussiness::where('id', ($id))->first();
        if ($business) {
            // Toggle the status
            $business->status = ($business->status === 'active') ? 'inactive' : 'active';

            $business->save();

            return redirect()->route('business.index')->with('success', 'Status updated successfully.');
        }
        return redirect()->back()->with('error', 'Status  not updated.');

    }

}
