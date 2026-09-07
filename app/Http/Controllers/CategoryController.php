<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Color;
use Illuminate\Http\Request;
use Yajra\Datatables\datatables;
use Validator;
use Stichoza\GoogleTranslate\GoogleTranslate;
use App\Models\PermissionUser;
use App\Models\VideoContent;
use App\Models\KnowledgeSession;
use Auth;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{

    private $category = 14;
    private $subadmin_menu_id = 16;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data['pre'] = $pre = PermissionUser::checkpermission(Auth::user()->id, $this->subadmin_menu_id);
        if (!isset($pre) || empty($pre)) {
            return redirect('dashboard');
        }
        return view('admin.category.index', compact('pre'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $data['pre'] = $pre = PermissionUser::checkpermission(Auth::user()->id, $this->subadmin_menu_id);
        if (!isset($pre) || empty($pre)) {
            return redirect('dashboard');
        }
        $colors = Color::latest()->get(); 
        return view('admin.category.create', compact('colors'));
    }

    public function getCategory(Request $request)
    {
        $data['pre'] = $pre = PermissionUser::checkpermission(Auth::user()->id, $this->subadmin_menu_id);
        if (!isset($pre) || empty($pre)) {
            return redirect('dashboard');
        }
        if ($request->ajax()) {
            $ageGroup = Category::orderBy(DB::raw('CAST(priority AS UNSIGNED)'), 'asc')->get();
            if (!empty($pre) && $pre->is_modify == 'yes') {
                return DataTables::of($ageGroup)
                    ->addIndexColumn()
                    ->addColumn('action', function ($row) {
                        $btn = "";
                        $btn .= '<a href="' . url("category/" . $row->id . "/edit") . '" title="Edit" style="margin-left:5px;font-size:20px"><i class="mdi mdi-pencil""></i></a>&nbsp;';
                        // $btn .= '<a href="' . url("delete-category/" . $row->id) . '" class="delete" title="Delete" data-id="' . $row->id . '" style="margin-left:5px;font-size:20px"><span class="mdi mdi-trash-can"></span></a>&nbsp;';
                        return $btn;
                    })
                    ->editColumn('category_name', function ($row) {
                        return "<span class='plan $row->category_name'>" . ucfirst($row->category_name) . "</span>";
                    })
                    ->editColumn('status', function ($row) {
                        return "<span class='sts $row->status'>" . ucfirst($row->status) . "</span>";
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
                    ->addColumn('priority', function ($row) {
                        return '<input type="text" name="priority"
                                    class="form-control priority-input"
                                    data-id="' . $row->id . '"
                                    value="' . ($row->priority ?? 0) . '" 
                                    placeholder="Enter Priority"
                                    min="1" style="width: 60px;">';
                    })
                    ->rawColumns(['action', 'category_name',  'status','priority','color','title_color'])
                    ->make(true);
            } else {
                return Datatables::of($ageGroup)
                    ->editColumn('category_name', function ($row) {
                        return "<span class='plan $row->category_name'>" . ucfirst($row->category_name) . "</span>";
                    })
                    ->editColumn('status', function ($row) {
                        return "<span class='sts $row->status'>" . ucfirst($row->status) . "</span>";
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
                    ->addColumn('priority', function ($row) {
                        return '<input type="text" name="priority"
                                    class="form-control priority-input"
                                    data-id="' . $row->id . '"
                                    value="' . ($row->priority ?? 0) . '" 
                                    placeholder="Enter Priority"
                                    min="1" style="width: 60px;" disabled>';
                    })
                    ->rawColumns(['category_name', 'status','priority','color','title_color'])
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

        $request->validate([
            'category_name' => [
                'required',
                'string',
                'min:3',
                'max:255',
                'not_regex:/<[^>]*>/u',
                \Illuminate\Validation\Rule::unique('category')
            ],
            'color' => 'required',
            'title_color' => 'required',
            // 'category_name_chinese' => ['required',
            //                     'string',
            //                     'min:3','max:255',
            //                     \Illuminate\Validation\Rule::unique('category')
            //                     ],
        ]);
        $translator = new GoogleTranslate('zh'); // 'zh' is the language code for Chinese
        $categoryChinese = $translator->translate($request->category_name);

        Category::create([
            'category_name' => $request->category_name,
            'color' => $request->color,
            'title_color' => $request->title_color
            // 'category_name_chinese' => $request->category_name_chinese ?? $categoryChinese
        ]);


        return redirect()->route('category.index')->with('success', 'Category created successfully.');
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
        if (!empty($pre) && $pre->is_modify === 'yes') {
            $data = Category::findOrFail($id);
            $colors = Color::latest()->get(); // ✅ Needed in the view
            return view('admin.category.edit', compact('data', 'colors'));
        }
        return redirect('dashboard');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'category_name' => [
                'required',
                'string',
                'min:3',
                'max:255',
                'not_regex:/<[^>]*>/u',
                \Illuminate\Validation\Rule::unique('category')
                    ->ignore($id)
            ],
            'color' => 'required',
            'title_color' => 'required',
            // 'category_name_chinese' => ['required',
            //                     'string',
            //                     'min:3','max:255', 
            //                     \Illuminate\Validation\Rule::unique('category')
            //                     ->ignore($id)],
        ]);


        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $category = Category::findOrFail($id);
        $translator = new GoogleTranslate('zh'); // 'zh' is the language code for Chinese
        $categoryChinese = $translator->translate($request->category_name);
        $category->update([
            'category_name' => $request->category_name,
            // 'category_name_chinese' => $request->category_name_chinese ?? $categoryChinese,
            'color' => $request->color,
            'title_color' => $request->title_color,
            'status' => $request->status ?? 'active'
        ]);
        // ✅ Update all related video content
        VideoContent::where('category_id', $id)->update([
            'color' => $request->color,
            'title_color' => $request->title_color
        ]);

        return redirect()->route('category.index')->with('success', 'Category updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $category = Category::find($id);

        if ($category) {
            $category->status = 'inactive';
            $category->save();
            KnowledgeSession::where('category_id', $category->id)->delete();
            VideoContent::where('category_id', $category->id)->delete();
            $category->delete();
        }

        return redirect()->back()->with('success', 'Category has been marked as inactive and deleted.');
    }

    public function categories()
    {
        $ageGroup = Category::select('*')->get();
        return response()->json([
            'data' => $ageGroup,
            'message' => 'Category Deleted Successfully.',
            'status' => true,
        ]);
    }

    public function updatePriority(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:quiz_categories,id',
            'priority' => 'required|integer|min:1',
        ]);

        try {
            $id = $request->id;
            $newPriority = $request->priority;
            // Update selected category
            $quizCategory = Category::findOrFail($id);
            $quizCategory->priority = $newPriority;
            $quizCategory->save();

            return response()->json([
                'message' => 'Priority updated successfully.',
                'priority' => $quizCategory->priority
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error updating priority: ' . $e->getMessage()
            ], 500);
        }
    }
}
