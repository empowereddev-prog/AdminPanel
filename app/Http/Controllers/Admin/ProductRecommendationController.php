<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRecommendationRequest;
use App\Models\Color;
use App\Models\DeviceToken;
use App\Models\PermissionUser;
use App\Models\ProductRecommendation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\File;
use Auth;

class ProductRecommendationController extends Controller
{

    private $subadmin_menu_id = 26;
    public function index(Request $request)
    {
        $permission['pre'] = $pre = PermissionUser::checkpermission(Auth::user()->id, $this->subadmin_menu_id);
        if (!isset($pre) || empty($pre)) {
            return redirect('dashboard');
        }
        if ($request->ajax()) {
            $data = ProductRecommendation::orderBy(DB::raw('CAST(priority AS UNSIGNED)'), 'asc')->get();

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    $permission['pre'] = $pre = PermissionUser::checkpermission(Auth::user()->id, $this->subadmin_menu_id);
                    if ($pre->is_modify == 'yes') {
                        $btn  = '<a href="' . route("product.edit", $row->id) . '" title="Edit" style="margin-left:5px; font-size:20px;">';
                        $btn .= '<i class="mdi mdi-pencil"></i></a>';

                        $btn .= '<a href="' . route("product.destroy", $row->id) . '" class="delete" title="Delete" data-id="' . $row->id . '" style="margin-left:5px; font-size:20px;">';
                        $btn .= '<span class="mdi mdi-trash-can"></span></a>';

                        $btn .= '<a href="' . route("product.show", $row->id) . '" title="View Detail" style="margin-left:5px; font-size:20px;">';
                        $btn .= '<span class="mdi mdi-eye"></span></a>';

                        return $btn;
                    }
                })
                ->addColumn('status', function ($row) {
                    if ($row->status == 'active') {
                        return '<span class="badge border border-info text-info">Active</span>';
                    } else {
                        return '<span class="badge border border-danger text-danger">Inactive</span>';
                    }
                })


                ->addColumn('title', function ($row) {
                    $title = $row->title;
                    return strlen($title) > 50
                        ? substr($title, 0, 50) . '...'
                        : $title;
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
                //     $image .= '<a href="' . asset($row->image) . '" target="_blank"><img src="' . asset($row->image) . '" style="width:50px;height:50px;"></a>';
                //     return $image ?: 'N/A';
                // })
                ->addColumn('image', function ($row) {
                    if (!$row->image) {
                        return 'N/A';
                    }
                    $url = getImagePathUrl($row->image, 'uploads/product');

                    return '<a href="' . $url . '" target="_blank">
                                <img src="' . $url . '" style="width:50px;height:50px;">
                            </a>';
                })
                ->rawColumns(['action', 'priority', 'image', 'description', 'status'])
                ->make(true);
        }

        return view('admin.productRecommendation.index')->with($permission);
    }

    public function create()
    {
        $colors = Color::select('id', 'color', 'title_color')->get();
        return view('admin.productRecommendation.create', compact('colors'));
    }


    public function store(ProductRecommendationRequest $request)
    {

        $validatedData = $request->validated();
        $dataToSave = [
            'title' => $validatedData['title'],
            'description' => $validatedData['description'],
            'url' => $request->url,
            // 'color' => $validatedData['color'],
            'color'       => $request->color,
            'title_color' => $request->color_title,

        ];
        // if ($request->hasFile('image')) {
        //     $image = $request->file('image');
        //     $imageName = time() . '.' . $image->getClientOriginalExtension();
        //     $image->move(public_path('uploads/product'), $imageName);
        //     $dataToSave['image'] = 'uploads/product/' . $imageName;
        // }
        if ($request->hasFile('image')) {
            $filename = uploadFile($request->file('image'), 'uploads/product');
            if (!$filename) {
                return back()->with('error', 'Failed to upload product image.');
            }
            $validatedData['image'] = $filename;
        }

        $product =   ProductRecommendation::create($dataToSave);

        $userIds = User::where([
            'user_type'      => 'parent',
            'status'         => 'active',
            'is_notification' => 'true'
        ])->pluck('id')->toArray();

        $devices = DeviceToken::whereIn('user_id', $userIds)
            ->whereNotNull('token')
            ->get();

        $content = getNotificationContent('add_product', [
            'title' => $product->title,
        ]);

        $notification_type = 'add_product';
        $user_type = "user";

        $userData = [
            'title' => $product->title,
            'id'    => $product->id,
            'color' => $product->color,
            'type'  => $notification_type,
        ];

        foreach ($devices as $user) {
            sendNotificationSender(
                $user->user_id,
                $content['title'],
                $content['body'],
                $notification_type,
                $userData,
                $user_type
            );
        }
        return redirect()->route('product.index')
            ->with('success', 'Resource added successfully!');
    }

    public function show($id)
    {
        $product = ProductRecommendation::findOrFail($id);
        $colors = Color::select('color', 'title_color')->get();
        return view('admin.productRecommendation.view', compact('product', 'colors'));
    }
    public function edit($id)
    {
        $product = ProductRecommendation::findOrFail($id);

        $colors = Color::select('color', 'title_color')->get();
        return view('admin.productRecommendation.edit', compact('product', 'colors'));
    }

    public function update(ProductRecommendationRequest $request, $id)
    {
        $product = ProductRecommendation::findOrFail($id);

        $validatedData = $request->validated();
        // if ($request->hasFile('image')) {
        //     $imagePath = public_path('uploads/product/');
        //     if ($product->image && File::exists(public_path($product->image))) {
        //         File::delete(public_path($product->image));
        //     }
        //     $image = $request->file('image');
        //     $imageName = time() . '.' . $image->getClientOriginalExtension();
        //     $image->move($imagePath, $imageName);
        //     $validatedData['image'] = 'uploads/product/' . $imageName;
        // }
        if ($request->hasFile('image')) {
            $filename = uploadFile(
                $request->file('image'),
                'uploads/product',
                $product->image
            );
            if (!$filename) {
                return back()->with('error', 'Failed to upload product image.');
            }
            $validatedData['image'] = $filename;
        }

        $validatedData['status'] = $request['status'];
        $validatedData['title_color'] = $request['color_title'];

        $product->update($validatedData);
        return redirect()->route('product.index')
            ->with('success', 'Resource updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $product = ProductRecommendation::findOrFail($id);
        if ($product->image && File::exists(public_path($product->image))) {
            File::delete(public_path($product->image));
        }
        $product->delete();
        return response()->json(['success' => 'Resource deleted successfully!']);
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
            $product = ProductRecommendation::findOrFail($id);
            $product->priority = $newPriority;
            $product->save();

            return response()->json([
                'message' => 'Priority updated successfully.',
                'priority' => $product->priority
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error updating priority: ' . $e->getMessage()
            ], 500);
        }
    }
}
