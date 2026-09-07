<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ColorRequest;
use App\Models\Category;
use App\Models\ChildMood;
use App\Models\Color;
use App\Models\Mood;
use App\Models\PermissionUser;
use App\Models\QuizCategory;
use App\Models\VideoContent;
use GuzzleHttp\Promise\Create;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Auth;

class ColorManagamentController extends Controller
{
    private $subadmin_menu_id = 35;
    public function index(Request $request)
    {
        $data['pre'] = $pre = PermissionUser::checkpermission(Auth::user()->id, $this->subadmin_menu_id);
        if (!isset($pre) || empty($pre)) {
            return redirect('dashboard');
        }
        if ($request->ajax()) {
            $data = Color::latest()->get();

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    $pre = PermissionUser::checkpermission(Auth::user()->id, $this->subadmin_menu_id);
                    if ($pre->is_modify == 'yes') {
                        $btn = '';
                        $btn .= '<a href="' . route("color.edit", $row->id) . '" title="Edit" style="margin-left:5px;font-size:20px"><i class="mdi mdi-pencil"></i></a>&nbsp;';

                        $btn .= '<a href="#" class="delete" title="Delete" data-id="' . $row->id . '" style="margin-left:5px;font-size:20px"><span class="mdi mdi-trash-can"></span></a>&nbsp;';
                        return $btn;
                    }
                })
                ->addColumn('color', function ($row) {
                    return '
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <div style="width: 30px; height: 30px; background-color: ' . $row->color . '; border: 1px solid #ccc;"></div>
                        <span>' . $row->color . '</span>
                    </div>';
                })
                ->addColumn('title_color', function ($row) {
                    return '
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <div style="width: 30px; height: 30px; background-color: ' . $row->title_color . '; border: 1px solid #ccc;"></div>
                        <span>' . $row->title_color . '</span>
                    </div>';
                })
                ->rawColumns(['action', 'color', 'title_color'])
                ->make(true);
        }
        return view('admin.color.index')->with($data);
    }


    public function create(Request $request)
    {
        return view('admin.color.create');
    }

    public function store(ColorRequest $request)
    {

        Color::create([
            'color' => $request['color'],
            'title_color' => $request['color_title']
        ]);

        // Redirect with success message
        return redirect()->route('color.index')->with('success', 'Color added successfully.');
    }


    public function edit($id)
    {
        $color = Color::findOrFail($id);
        return view('admin.color.edit', compact('color'));
    }
    public function update(Request $request, $id)
    {
        $request->validate([
            'color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'color_title' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        $color = Color::findOrFail($id);
        $color->update([
            'color' => $request->color,
            'title_color' => $request->color_title,
        ]);
        // Update category table
        Category::where('color', $request->old_color)->update([
            'color' => $request->color,
            'title_color' => $request->color_title,
        ]);

        // Update video content table based on related categories
        $categoryIds = Category::where('color', $request->color)->pluck('id');
        VideoContent::whereIn('category_id', $categoryIds)->update([
            'color' => $request->color,
            'title_color' => $request->color_title,
        ]);

        // Update quiz categories
        QuizCategory::where('color', $request->old_color)->update([
            'color' => $request->color,
            'title_color' => $request->color_title,
        ]);

        Mood::where('color', $request->old_color)->update([
            'color' => $request->color,

        ]);

        return redirect()->route('color.index')->with('success', 'Color updated successfully.');
    }

    public function delete($id)
    {
        $color = Color::findOrFail($id);

        try {
            $color->delete();

            return response()->json([
                'status' => true,
                'message' => 'Color deleted successfully.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete color.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
