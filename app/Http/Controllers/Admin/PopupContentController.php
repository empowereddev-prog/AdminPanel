<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PopupContent;
use App\Models\PermissionUser;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Auth;

class PopupContentController extends Controller
{
    private $subadmin_menu_id = 37;

    public function index(Request $request)
    {
        $pre = PermissionUser::checkpermission(Auth::user()->id, $this->subadmin_menu_id);
        if (!$pre) return redirect('dashboard');

        if ($request->ajax()) {
            $popupContents = PopupContent::orderBy('id', 'DESC')->get();
            return DataTables::of($popupContents)
                ->addIndexColumn()
                ->addColumn('action', function ($row) use ($pre) {
                    if ($pre->is_modify == 'yes') {
                        $btn = '<a href="' . url("popup-content/" . $row->id . "/edit") . '" title="Edit"><i class="mdi mdi-pencil" style="font-size:20px"></i></a>&nbsp;';
                        $btn .= '<a href="#" class="delete" data-id="' . $row->id . '" title="Delete"><i class="mdi mdi-trash-can" style="font-size:20px"></i></a>';
                        return $btn;
                    }
                    return '';
                })
                ->editColumn('description', function ($row) {
                    $description = strip_tags($row->description); // Remove HTML
                    $description = ucfirst($description);
                    return strlen($description) > 100 ? substr($description, 0, 100) . '...' : $description;
                })
                ->editColumn('type', function ($row) {
                   
                    $type = ucfirst($row->type);
                    return  $type;
                })
                
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('admin.popup_content.index', compact('pre'));
    }

    public function create()
    {
        $pre = PermissionUser::checkpermission(Auth::user()->id, $this->subadmin_menu_id);
        if ($pre && $pre->is_modify == 'yes') {
            return view('admin.popup_content.create');
        }
        return redirect('dashboard');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required',
            'description' => 'required|string',
        ]);

        PopupContent::create($request->only('title', 'description','type'));
        return redirect()->route('popup-content.index')->with('success', 'Popup Content Added Successfully.');
    }

    public function edit($id)
    {
        $pre = PermissionUser::checkpermission(Auth::user()->id, $this->subadmin_menu_id);
        if ($pre && $pre->is_modify == 'yes') {
            $data = PopupContent::findOrFail($id);
            return view('admin.popup_content.edit', compact('data'));
        }
        return redirect('dashboard');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required',
            'description' => 'required|string',
        ]);

        PopupContent::where('id', $id)->update($request->only('title', 'description','type'));
        return redirect()->route('popup-content.index')->with('success', 'Popup Content Updated Successfully.');
    }

    public function destroy($id)
    {
        $popup = PopupContent::findOrFail($id);
        $popup->delete();
        return response()->json(['success' => 'Popup Content Deleted Successfully.']);
    }
}
