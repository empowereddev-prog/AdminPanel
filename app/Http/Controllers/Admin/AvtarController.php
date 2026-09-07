<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AgeGroup;
use App\Models\Category;
use App\Models\KnowledgeBase;
use App\Models\School;
use App\Models\AvatarImage;
use App\Models\BatterySetting;
use Illuminate\Http\Request;
use Yajra\Datatables\datatables;
use App\Models\PermissionUser;
use App\Models\Setting;
use Auth;
use Validator;
use DB;

class AvtarController extends Controller
{

    private $avatar = 13;
    private $subadmin_menu_id = 13;
    public function avtarType()
    {
        $data['pre'] = $pre = PermissionUser::checkpermission(Auth::user()->id, $this->subadmin_menu_id);
        if (!isset($pre) || empty($pre)) {
            return redirect('dashboard');
        }

        return view('admin.avtar.type_index', compact('pre'));
    }

    public function getavtarType(Request $request)
    {
        $data['pre'] = $pre = PermissionUser::checkpermission(Auth::user()->id, $this->subadmin_menu_id);
        if (!isset($pre) || empty($pre)) {
            return redirect('dashboard');
        }
        if ($request->ajax()) {
            $avtar = AvatarImage::select(
                'body_part',
                DB::raw('GROUP_CONCAT(id) as ids'),
                DB::raw('GROUP_CONCAT(points) as points_list'),
                DB::raw('GROUP_CONCAT(status) as statuses'),
                DB::raw('MIN(preview_image) as preview_image') // Choose the first image as preview
            )
                ->groupBy('body_part')
                ->get();

            if (!empty($pre) && $pre->is_modify == 'yes') {

                return DataTables::of($avtar)
                    ->addIndexColumn()
                    ->addColumn('action', function ($row) {
                        $btn = '<a href="' . url("avtar/" . $row->body_part) . '" title="Add Avatar">
                <i class="mdi mdi-plus"></i>
                <span>Add Avatar</span>
            </a>';
                        return $btn;
                    })

                    ->addColumn('status', function ($row) {
                        $statusesArray = explode(',', $row->statuses);
                        $currentStatus = isset($statusesArray[0]) ? trim($statusesArray[0]) : 'inactive';

                        $checked = ($currentStatus === 'active') ? 'checked' : '';

                        return '<label class="switch switch-primary switch-pill form-control-label mr-2">
                            <input type="checkbox" class="switch-input form-check-input change-status" ' . $checked . ' 
                                data-id="' . $row->body_part . '" data-status="' . $currentStatus . '">
                            <span class="switch-label"></span>
                            <span class="switch-handle"></span>
                        </label>';
                    })
                    ->rawColumns(['action', 'status'])
                    ->make(true);
            } else {
                return Datatables::of($avtar)
                    ->addColumn('status', function ($row) {
                        $statusesArray = explode(',', $row->statuses);
                        $currentStatus = isset($statusesArray[0]) ? trim($statusesArray[0]) : 'inactive';

                        $checked = ($currentStatus === 'active') ? 'checked' : '';

                        return '<label class="switch switch-primary switch-pill form-control-label mr-2">
                            <input type="checkbox" class="switch-input form-check-input change-status" ' . $checked . ' 
                                data-id="' . $row->body_part . '" data-status="' . $currentStatus . '">
                            <span class="switch-label"></span>
                            <span class="switch-handle"></span>
                        </label>';
                    })
                    ->rawColumns(['status'])
                    ->addIndexColumn()
                    ->make(true);
            }
        }
    }

    public function index($type)
    {
        $data['pre'] = $pre = PermissionUser::checkpermission(Auth::user()->id, $this->subadmin_menu_id);
        if (!isset($pre) || empty($pre)) {
            return redirect('dashboard');
        }
        return view('admin.avtar.index', compact('type', 'pre'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create($type)
    {
        $pre = PermissionUser::checkpermission(Auth::user()->id, $this->subadmin_menu_id);
        if (!empty($pre) && $pre->is_modify == 'yes') {
            $data['types'] =  AgeGroup::where('status', '1')->get();
            // $data['category'] =  Category::where('status','active')->get();
            // $data['school'] =  School::where('status','active')->get();
            $setting = BatterySetting::where('option_key', 'avtar_battery_percentage')->first();
            return view('admin.avtar.create', compact('data', 'type', 'setting'));
        }
        return redirect('dashboard');
    }


    public function getavtar(Request $request, $type)
    {
        $data['pre'] = $pre = PermissionUser::checkpermission(Auth::user()->id, $this->subadmin_menu_id);
        if (!isset($pre) || empty($pre)) {
            return redirect('dashboard');
        }
        if ($request->ajax()) {
            $avtar = AvatarImage::select('*')->where('body_part', $type)->get();
            if (!empty($pre) && $pre->is_modify == 'yes') {

                return DataTables::of($avtar)
                    ->addIndexColumn()
                    ->addColumn('action', function ($row) {
                        $btn = "";
                        $btn .= '<a href="' . url("avtar/" . $row->body_part . "/" . $row->id . "/edit") . '" title="Edit" style="margin-left:5px;font-size:20px"><i class="mdi mdi-pencil""></i></a>&nbsp;';
                        // $btn .= '<a href="javascript:void(0)" class="delete" 
                        //   data-id="' . $row->id . '" 
                        //   data-type="' . $row->body_part . '" 
                        //   style="margin-left:5px;font-size:20px"><i class="mdi mdi-trash-can"></i></a>';
                        return $btn;
                    })
                    // ->editColumn('preview_image', function ($row) {
                    //     $img = '<img src="' . asset('assets/avtar/' . $row['preview_image']) . '" alt="No Preview Image" width="80" height="80">';
                    //     return $img;
                    // })
                    // ->editColumn('apply_image', function ($row) {
                    //     $img = '<img src="' . asset('assets/avtar/' . $row['apply_image']) . '" alt="No Apply Image" width="80" height="80">';
                    //     return $img;
                    // })
                    ->editColumn('preview_image', function ($row) {
                        if ($row['preview_image']) {
                            $imgUrl = getImagePathUrl($row['preview_image'], 'assets/avtar');
                            return '<img src="' . $imgUrl . '" alt="No Preview Image" width="80" height="80">';
                        }
                        return 'N/A';
                    })
                    ->editColumn('apply_image', function ($row) {
                        if ($row['apply_image']) {
                            $imgUrl = getImagePathUrl($row['apply_image'], 'assets/avtar');
                            return '<img src="' . $imgUrl . '" alt="No Apply Image" width="80" height="80">';
                        }
                        return 'N/A';
                    })
                    

                    ->editColumn('status', function ($row) {
                        return "<span class='sts $row->status'>" . ucfirst($row->status) . "</span>";
                    })
                    ->rawColumns(['action', 'preview_image', 'apply_image', 'status'])
                    ->make(true);
            } else {
                return Datatables::of($avtar)
                    ->addIndexColumn()
                    ->make(true);
            }
        }
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // dd($request->type);
        $request->validate([
            // 'type' => 'required',
            // 'points' => 'required|numeric|min:1|max:100',
            'preview_image' => 'required|image|mimes:jpeg,png,jpg',
            'apply_image' => 'required|image|mimes:jpeg,png,jpg',
        ]);
        // $previewName = null;
        // if ($request->hasFile('preview_image')) {
        //     $previewName = $request->file('preview_image')->getClientOriginalName();
        //     $request->file('preview_image')->move(public_path('assets/avtar'), $previewName);
        // }

        // $applyName = null;
        // if ($request->hasFile('apply_image')) {
        //     $applyName = $request->file('apply_image')->getClientOriginalName();
        //     $request->file('apply_image')->move(public_path('assets/avtar'), $applyName);
        // }

        $previewName = null;
        if ($request->hasFile('preview_image')) {
            $previewName = uploadFile($request->file('preview_image'), 'assets/avtar', $oldPreviewFile ?? null);
            if (!$previewName) {
                return back()->with('error', 'Failed to upload preview image.');
            }
        }

        $applyName = null;
        if ($request->hasFile('apply_image')) {
            $applyName = uploadFile($request->file('apply_image'), 'assets/avtar', $oldApplyFile ?? null);
            if (!$applyName) {
                return back()->with('error', 'Failed to upload apply image.');
            }
        }



        AvatarImage::create([
            'body_part' => $request->type,
            'points' => $request->points,
            'preview_image' => $previewName,
            'apply_image' => $applyName,
        ]);

        return redirect()->route('avtar.index', ['type' => $request->type])->with('success', 'Avtar added successfully.');
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
    public function edit($type, string $id)
    {
        $pre = PermissionUser::checkpermission(Auth::user()->id, $this->subadmin_menu_id);
        if (!empty($pre) && $pre->is_modify == 'yes') {
            $data = AvatarImage::where('id', $id)->first();
            $setting = BatterySetting::where('option_key', 'avtar_battery_percentage')->first();
            return view('admin.avtar.edit', compact('data', 'type', 'setting'));
        }
        return redirect('dashboard');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $type, string $id)
    {
        $request->validate([
            // 'type' => 'required',
            // 'points' => 'required|numeric|min:1|max:100',
            'preview_image' => 'nullable|image|mimes:jpeg,png,jpg',
            'apply_image' => 'nullable|image|mimes:jpeg,png,jpg',
            'status' => 'required|in:active,inactive',
        ]);

        $avatarImage = AvatarImage::findOrFail($id);

        // // Handle preview image update
        // if ($request->hasFile('preview_image')) {
        //     // Delete old image if exists
        //     if (!empty($avatarImage->preview_image) && file_exists(public_path('assets/avtar' . $avatarImage->preview_image))) {
        //         unlink(public_path('assets/avtar' . $avatarImage->preview_image));
        //     }

        //     $previewName = $request->file('preview_image')->getClientOriginalName();
        //     $request->file('preview_image')->move(public_path('assets/avtar'), $previewName);
        //     $avatarImage->preview_image = $previewName;
        // }

        // // // Handle apply image update
        // if ($request->hasFile('apply_image')) {
        //     // Delete old image if exists
        //     if (!empty($avatarImage->apply_image) && file_exists(public_path('assets/avtar' . $avatarImage->apply_image))) {
        //         unlink(public_path('assets/avtar' . $avatarImage->apply_image));
        //     }

        //     $applyName = $request->file('apply_image')->getClientOriginalName();
        //     $request->file('apply_image')->move(public_path('assets/avtar'), $applyName);
        //     $avatarImage->apply_image = $applyName;
        // }

        // Handle preview image update
        if ($request->hasFile('preview_image')) {
            $previewName = uploadFile(
                $request->file('preview_image'),
                'assets/avtar',
                $avatarImage->preview_image ?? null
            );
            if (!$previewName) {
                return back()->with('error', 'Failed to upload preview image.');
            }
            $avatarImage->preview_image = $previewName;
        }

        // Handle apply image update
        if ($request->hasFile('apply_image')) {
            $applyName = uploadFile(
                $request->file('apply_image'),
                'assets/avtar',
                $avatarImage->apply_image ?? null
            );
            if (!$applyName) {
                return back()->with('error', 'Failed to upload apply image.');
            }
            $avatarImage->apply_image = $applyName;
        }


        // Update other fields
        $avatarImage->status = $request->status;
        $avatarImage->points = $request->points;

        $avatarImage->save();

        return redirect()->route('avtar.index', ['type' => $type])->with('success', 'Avatar updated successfully.');
    }
    public function destroy($type, string $id)
    {
        $avatar = AvatarImage::find($id);

        if (!$avatar) {
            return response()->json([
                'success' => false,
                'message' => 'Avatar not found.'
            ], 404);
        }

        // Us avatar ke body_part ke total records count karo
        $totalAvatars = AvatarImage::where('body_part', $avatar->body_part)->count();

        if ($totalAvatars <= 1) {
            return response()->json([
                'success' => false,
                'message' => "You cannot delete the last remaining avatar for {$avatar->body_part}."
            ], 400);
        }

        // Soft delete
        $avatar->status = 'inactive';
        $avatar->save();
        $avatar->delete();

        return response()->json([
            'success' => true,
            'message' => 'Avatar has been marked as inactive and soft deleted.'
        ]);
    }

    public function changeStatus(Request $request, $type)
    {
        $avatars = AvatarImage::where('body_part', $type)->get();

        foreach ($avatars as $avatar) {
            $avatar->status = $avatar->status === 'active' ? 'inactive' : 'active';
            $avatar->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'All avatars of type ' . ucfirst($type) . ' status updated successfully.',
        ]);
    }
}
