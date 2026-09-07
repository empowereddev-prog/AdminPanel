<?php

namespace App\Http\Controllers;

use App\Models\AgeGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator as FacadesValidator;
use Yajra\Datatables\datatables;
use Validator;
class AgeGroupController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('admin.age-group.index');
    }


    public function getAgeGroup(Request $request)
    {
      
        if ($request->ajax()) {
            $ageGroup = AgeGroup::select('*')->get();
            return DataTables::of($ageGroup)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    $btn = "";
                    $btn .= '<a href="' . url("age-group/" . $row->id . "/edit") . '" title="Edit" style="margin-left:5px;font-size:20px"><i class="mdi mdi-pencil""></i></a>&nbsp;';
                    $btn .= '<a href="' . url("delete-age-group/" . $row->id) . '" class="delete" title="Delete" data-id="' . $row->id . '" style="margin-left:5px;font-size:20px"><span class="mdi mdi-trash-can"></span></a>&nbsp;';
                    return $btn;
                })
                ->editColumn('name', function ($row) {
                    return "<span class='plan $row->name'>" . ucfirst($row->name) . "</span>";
                })
                ->editColumn('start_age', function ($row) {
                    return "<span class='plan $row->start_age'>" . $row->start_age . "</span>";
                })
                ->editColumn('end_age', function ($row){
                    return "<span class='plan $row->end_age'>" .ucfirst($row->end_age). "</span>";
                })
                ->rawColumns(['action','name','start_age', 'end_age'])
                ->make(true);
        }
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.age-group.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
      
        $request->validate([
            'name' => 'required|string|max:255',
            'start_age' => 'required|integer|min:0',
            'end_age' => 'required|integer|min:0|gt:start_age',
        ]);

        AgeGroup::create($request->all());
        return redirect()->route('age-group.index')->with('success', 'Age Group created successfully.');
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
        $data = AgeGroup::where('id', $id)->first();
        return view('admin.age-group.edit', compact('data'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'start_age' => 'required|integer|min:0',
            'end_age' => 'required|integer|min:0|gt:start_age',
            
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $ageGroup = AgeGroup::findOrFail($id);

        $ageGroup->update($request->all());


        return redirect()->route('age-group.index')->with('success', 'Age Group updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $ageData = AgeGroup::where('id', ($id))->first();
       
        $ageData->delete();
       
        return redirect()->back()->with('success', 'School Deleted Successfully.');
    }

    public function ageRange() {
        $ageGroup = AgeGroup::select('id','name', 'start_age', 'end_age', 
                    DB::raw("CONCAT(start_age, '-', end_age) as age_range"))
                    ->get();
    
        return $ageGroup; 
    }
}
