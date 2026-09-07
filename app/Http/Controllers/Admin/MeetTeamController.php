<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MeetTeamRequest;
use App\Models\MeetTeam;
use App\Models\PermissionUser;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\File;
use Auth;
use Illuminate\Support\Facades\DB;

class MeetTeamController extends Controller
{
    private $subadmin_menu_id = 27;
    public function index(Request $request)
    {
        // $permission['pre'] = $pre = PermissionUser::checkpermission(Auth::user()->id, $this->subadmin_menu_id);
        // if (!isset($pre) || empty($pre)) {
        //     return redirect('dashboard');
        // }
        if ($request->ajax()) {
            // $data = MeetTeam::orderBy(DB::raw('CAST(priority AS UNSIGNED)'), 'asc')->orderBy('title', 'asc')->get();
            // $data = MeetTeam::orderBy(DB::raw('CAST(priority AS UNSIGNED)'), 'asc')
            //     ->orderByRaw("TRIM(LEADING 'Dr. ' FROM TRIM(LEADING 'Dr ' FROM title)) ASC")
            //     ->get();
            $data = MeetTeam::orderBy(DB::raw('CAST(priority AS UNSIGNED)'), 'asc')
                ->orderByRaw("
        LTRIM(
            REPLACE(
                REPLACE(
                    REPLACE(
                        REPLACE(title, 'Dr. ', ''), 
                    'Dr.', ''), 
                '  ', ' '),
            '.','')
        ) ASC
    ")
                ->get();


            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    // $pre = PermissionUser::checkpermission(Auth::user()->id, $this->subadmin_menu_id);
                    // if ($pre->is_modify == 'yes') {
                    $btn  = '<a href="' . route("meet-team.edit", $row->id) . '" title="Edit" style="margin-left:5px; font-size:20px;">';
                    $btn .= '<i class="mdi mdi-pencil"></i></a>';

                    $btn .= '<a href="' . route("meet-team.destroy", $row->id) . '" class="delete" title="Delete" data-id="' . $row->id . '" style="margin-left:5px; font-size:20px;">';
                    $btn .= '<span class="mdi mdi-trash-can"></span></a>';

                    $btn .= '<a href="' . route("meet-team.show", $row->id) . '" title="View Detail" style="margin-left:5px; font-size:20px;">';
                    $btn .= '<span class="mdi mdi-eye"></span></a>';
                    return $btn;
                    // }
                })
                ->addColumn('status', function ($row) {
                    if ($row->status == 'active') {
                        return '<span class="badge border border-info text-info">Active</span>';
                    } else {
                        return '<span class="badge border border-danger text-danger">Inactive</span>';
                    }
                })
                ->addColumn('description', function ($row) {
                    $description = $row->description;
                    return strlen($description) > 100
                        ? substr($description, 0, 100) . '...'
                        : $description;
                })
                ->addColumn('priority', function ($row) {
                    return '<input type="text" name="priority"
                                class="form-control priority-input"
                                data-id="' . $row->id . '"
                                value="' . ($row->priority ?? 0) . '" 
                                placeholder="Enter Priority"
                                min="1" style="width: 60px;">';
                })
                // ->addColumn('image', function ($row) {
                //     $image = '';
                //     $image .= '<a href="' . asset($row->image) . '" target="_blank"><img src="' . asset($row->image) . '" style="width:50px;height:40px;"></a>';
                //     return $image ?: 'N/A';
                // })

                ->addColumn('image', function ($row) {
                    if (!$row->image) {
                        return 'N/A';
                    }
                    $url = getImageUrl($row->image);
                    return '<a href="' . $url . '" target="_blank">
                                <img src="' . $url . '" style="width:50px;height:50px;">
                            </a>';
                })
                ->rawColumns(['action', 'priority', 'image', 'status', 'description'])
                ->make(true);
        }

        return view('admin.meetTeam.index');
    }

    public function create()
    {
        return view('admin.meetTeam.create');
    }

    public function store(MeetTeamRequest $request)
    {
        $validatedData = $request->validated();
        $dataToSave = [
            'title' => $validatedData['title'],
            'description' => $validatedData['description'],
            'profession' => $validatedData['profession'],
            'url' => $validatedData['url'],
            'designation' => $validatedData['designation'],
            'priority' => 2,
            //  'color' => $validatedData['color'],
            // 'status' => $request->status,
        ];
        // if ($request->hasFile('image')) {
        //     $image = $request->file('image');
        //     $imageName = time() . '.' . $image->getClientOriginalExtension();
        //     $image->move(public_path('uploads/meetTeam'), $imageName);
        //     $dataToSave['image'] = 'uploads/meetTeam/' . $imageName;
        // }

        if ($request->hasFile('image')) {
            $image = uploadFile($request->file('image'), 'uploads/meetTeam');
            if (!$image) {
                return back()->with('error', 'Failed to upload meet team image.');
            }
            $dataToSave['image'] = 'uploads/meetTeam/' . $image;
        }

        MeetTeam::create($dataToSave);
        return redirect()->route('meet-team.index')
            ->with('success', 'Meet Team added successfully!');
    }

    public function show($id)
    {
        $meetTeam = MeetTeam::findOrFail($id);
        return view('admin.meetTeam.view', compact('meetTeam'));
    }
    public function edit($id)
    {
        $meetTeam = MeetTeam::findOrFail($id);

        return view('admin.meetTeam.edit', compact('meetTeam'));
    }

    public function update(MeetTeamRequest $request, $id)
    {
        $meetTeam = MeetTeam::findOrFail($id);
        $validatedData = $request->validated();
        // if ($request->hasFile('image')) {
        //     $imagePath = public_path('uploads/meetTeam/');
        //     if ($meetTeam->image && File::exists(public_path($meetTeam->image))) {
        //         File::delete(public_path($meetTeam->image));
        //     }
        //     $image = $request->file('image');
        //     $imageName = time() . '.' . $image->getClientOriginalExtension();
        //     $image->move($imagePath, $imageName);
        //     $validatedData['image'] = 'uploads/meetTeam/' . $imageName;
        // }

        if ($request->hasFile('image')) {
            $oldFileName = $meetTeam->image;
            $newFileName = uploadFile($request->file('image'), 'uploads/meetTeam', $oldFileName);
            if (!$newFileName) {
                return back()->with('error', 'Failed to upload team member image.');
            }
            $validatedData['image'] = 'uploads/meetTeam/' . $newFileName;
        }

        $validatedData['status'] = $request['status'];
        $meetTeam->update($validatedData);
        return redirect()->route('meet-team.index')
            ->with('success', 'Meet Team updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $meetTeam = MeetTeam::findOrFail($id);
        if ($meetTeam->image && File::exists(public_path($meetTeam->image))) {
            File::delete(public_path($meetTeam->image));
        }
        $meetTeam->delete();
        return response()->json(['success' => 'Meet Team deleted successfully!']);
    }

    public function updatePriority(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'priority' => 'required|integer|min:1',
        ]);

        try {
            $id = $request->id;
            $newPriority = $request->priority;
            // Update selected category
            $meetTeam = MeetTeam::findOrFail($id);
            $meetTeam->priority = $newPriority;
            $meetTeam->save();

            return response()->json([
                'message' => 'Priority updated successfully.',
                'priority' => $meetTeam->priority
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error updating priority: ' . $e->getMessage()
            ], 500);
        }
    }
}
