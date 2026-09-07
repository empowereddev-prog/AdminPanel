<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use Yajra\Datatables\Datatables;
use Illuminate\Http\Request;
use App\Models\Feature;

class FeaturesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Feature::orderBy('id', 'DESC')->get()->unique('key');
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('title', function ($row) {
                    $dataTitle = Feature::where('key',$row->key)->orderBy('id', 'Asc')->pluck('title')->toArray();
                    return  $dataTitle;
                })
                ->addColumn('action', function ($row) {
                    $btn = "";
                    $btn .= '<a href="' . url("admin/features/" . $row->key . "/edit") . '" title="Edit" style="margin-left:5px;font-size:20px"><i class="mdi mdi-pencil""></i></a>';
                    return $btn;
                })
                ->editColumn('description', function ($row) {
                    $row->contdescriptionent = str_replace('../../..', url('/'), $row->description);
                    $row->description = str_replace('../../..', url('/'), $row->description);
                    return "<td style='max-width:500px;'><div>" . $row->description . "</div></td>";
                })
                ->addColumn('icon', function ($row) {
                    return $row->icon ? '<a href="' . url(asset('uploads/feature-icon/' . $row->icon)) . '" target="_blank"><img src="' . url('uploads/feature-icon/' . $row->icon) . '" style="width:80px;height:60px;"></a>' : 'N/A';
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
                    <input type="checkbox" class="switch-input form-check-input" ' . $status . ' data-id="' . $row->key . '" data-key="' . $row->status . '">
                    <span class="switch-label"></span>
                    <span class="switch-handle"></span>
                    </label>';
                    return $btn;
                })

                ->rawColumns(['action', 'status', 'icon', 'description'])
                ->make(true);
        }
        return view('admin.feature.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $key)
    {
        $features = Feature::where('key', $key)->get();

        return view('admin.feature.edit', compact('features'));
    }

    /**
     * Update the specified resource.
     */
    public function update(Request $request, string $key)
    {
        $img = '';
        if ($request->hasFile('icon')) {
            $img = 'feature-icon' . time() . '-' . rand(0, 99) . '.' . $request->icon->extension();
            $request->icon->move(public_path('uploads/feature-icon/'), $img);

            $oldIcon = Feature::where('key', $key)->pluck('icon')->first();

            if ($oldIcon && file_exists(public_path('uploads/feature-icon/' . $oldIcon))) {
                File::delete(public_path('uploads/feature-icon/' . $oldIcon));
            }
        }

        $features = Feature::where('key', $key)->get();

        foreach ($features as $feature) {
            $data = [
                'title' => $request->input("title.{$feature->id}", $feature->title),
                'description' => $request->input("description.{$feature->id}", $feature->description),
                'icon' => $img ?: $feature->icon,
            ];

            Feature::where([
                'id' => $feature->id,
                'language' => $request->input("language.{$feature->id}", $feature->language)
            ])->update($data);
        }

        return redirect('admin/features')->with('success', 'Feature updated successfully.');
    }

    /**
     * Update the specified resource status.
     */
    public function toggleStatus(string $key)
    {
        $feature = Feature::where('key', $key)->first();
        if (!$feature) {
            return response()->json(['error' => 'Feature not found.'], 404);
        }
        $newStatus = ($feature->status === 'active') ? 'inactive' : 'active';
        Feature::where('key', $key)->update(['status' => $newStatus]);
        return response()->json(['success' => 'Feature status updated successfully.'], 200);
    }
}
