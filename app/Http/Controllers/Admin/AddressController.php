<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AddressRequest;
use App\Models\Address;
use Illuminate\Http\Request;
use Yajra\Datatables\Datatables;

class AddressController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Address::orderBy('id', 'DESC')->get();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    $btn = "";
                    $btn .= '<a href="' . url("admin/addresses/" . $row->id . "/edit") . '" title="Edit" style="margin-left:5px;font-size:20px"><i class="mdi mdi-pencil""></i></a>&nbsp;';
                    $btn .= '<a href="' . url("admin/addresses/" . $row->id) . '" class="delete" title="Delete" data-id="' . $row->id . '" style="margin-left:5px;font-size:20px"><span class="mdi mdi-trash-can"></span></a>&nbsp;';
                    return $btn;
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

                ->rawColumns(['action', 'status'])
                ->make(true);
        }
        return view('admin.address.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.address.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AddressRequest $request)
    {
        $address = [
            'title' => $request->title,
            'description' => $request->description,
            'contact_no' => $request->contact_no,
        ];
        Address::create($address);
        return redirect('admin/addresses')->with('success', 'Address Added Successfully.');
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
        $data = Address::where('id', $id)->first();
        return view('admin.address.edit', compact('data'));

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(AddressRequest $request, string $id)
    {

        $address = [
            'title' => $request->title,
            'description' => $request->description,
            'contact_no' => $request->contact_no,

        ];
        // Address::create($address);
        Address::find($id)->update($address);

        return redirect('admin/addresses')->with('success', 'Address updated Successfully.');

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {

        $addressData = Address::where('id', ($id))->first();
        $addressData->delete();
        return redirect()->back()->with('success', 'Address Deleted Successfully.');
    }

    public function status($id)
    {
        $address = Address::where('id', ($id))->first();
        if ($address) {
            // Toggle the status
            $address->status = ($address->status === 'active') ? 'inactive' : 'active';

            $address->save();

            return redirect()->route('addresses.index')->with('success', 'Status updated successfully.');
        }
        return redirect()->back()->with('error', 'Status  not updated.');

    }
}
