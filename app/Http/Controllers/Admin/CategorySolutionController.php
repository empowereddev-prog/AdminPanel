<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ServiceCategorySolution;
use App\Models\Service;
use App\Models\ServiceCategories;
use Yajra\Datatables\Datatables;

class CategorySolutionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {       
        if ($request->ajax()) {
            $solutionDetails = ServiceCategorySolution::getSolutionDetails();
            $dataArray = [];
            foreach ($solutionDetails as $solutionDetail) {
                $service_title = Service::where('id', $solutionDetail->service_id)->value('title');
                $service_category = ServiceCategories::where('id', $solutionDetail->service_category_id)->value('service_category');
                $dataObject = (object)[
                    'id' => $solutionDetail->id,
                    'service_category' => $service_category,
                    'service_id' => $service_title,
                    'category_solution' => $solutionDetail->category_solution
                ];

                $dataArray[] = $dataObject;
            }
            $data = $dataArray;
            return DataTables::of($dataArray)
            ->addIndexColumn()
            ->addColumn('action', function ($row) {
                $btn = '';
                $btn .= '<a href="' . url("admin/category-solution/" . $row->id . "/edit") . '" title="Edit" style="margin-left:5px;font-size:20px"><i class="mdi mdi-pencil"></i></a>&nbsp;';
                $btn .= '<a href="' . url("admin/category-solution/" . $row->id) . '" class="delete" title="Delete" data-id="' . $row->id . '" style="margin-left:5px;font-size:20px"><span class="mdi mdi-trash-can"></span></a>&nbsp;';
                return $btn;
            })
            ->addColumn('service_id', function ($row) {
                return $row->service_id;
            })
            ->addColumn('service_category', function ($row) {
                return $row->service_category; // Assuming 'type' is a column in your 'product_categories' table
            })
             ->addColumn('category_solution', function ($row) {
                return $row->category_solution; // Assuming 'type' is a column in your 'product_categories' table
            })
            ->rawColumns(['action'])
            ->make(true);
        }
        return view('admin.service_category_solution.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $allServices = Service::getAllServices();
        $getAllServiceCategories = ServiceCategories::getAllServiceCategories();
        return view('admin.service_category_solution.create' , compact('allServices', 'getAllServiceCategories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'service_id' => 'required',
            'service_category_id' => 'required',
            'category_solution' => 'required|max:100|min:4',
        ]);
        $data = ServiceCategorySolution::createServiceCategory($validatedData);
        return redirect('admin/category-solution')->with('success', 'Product Category Added Successfully.');
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
        $allServices = Service::getAllServices();
        $getAllServiceCategories = ServiceCategories::getAllServiceCategories();
        $data = ServiceCategorySolution::getCategorysolutionById($id);
        return view('admin.service_category_solution.edit', compact('allServices', 'getAllServiceCategories', 'data'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validatedData = $request->validate([
            'service_id' => 'required',
            'service_category_id' => 'required',
            'category_solution' => 'required|max:100|min:4',
        ]);
        ServiceCategorySolution::updateData($validatedData, $id);
        return redirect('admin/category-solution')->with('success', 'Service Category Solution Updated Successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $data = ServiceCategorySolution::deleteCategorySolution($id);
        if ($data) {
            return response()->json(['success' => 'Product Category Deleted Successfully.']);
        } else {
            return response()->json(['error' => 'Error deleting Product Category.'], 500);
        }
    }
}
