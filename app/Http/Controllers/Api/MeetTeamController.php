<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MeetTeamResource;
use App\Models\MeetTeam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MeetTeamController extends Controller
{
    // public function index(){
    //     // $product = MeetTeam::where('status', 'active')->orderBy(DB::raw('CAST(priority AS UNSIGNED)'), 'asc')->latest()->paginate(10);
    //     $product = MeetTeam::where('status', 'active')
    //     ->orderBy(DB::raw('CAST(priority AS UNSIGNED)'), 'asc')
    //     ->orderBy('title', 'asc')  
    //     ->paginate(10);
    //     $data = MeetTeamResource::collection($product);
    //     return response()->json([
    //         'status' => true,
    //         'message' => "Get Meet Team data successfully done",
    //         'data' => $data
    //     ]);
    // }

    public function index()
    {
        // Fetches ALL active records, sorted by priority and then title
        $product = MeetTeam::where('status', 'active')
            ->orderBy(DB::raw('CAST(priority AS UNSIGNED)'), 'asc')
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
            ->paginate(30);

        $data = MeetTeamResource::collection($product);

        return response()->json([
            'status' => true,
            'message' => "Get Meet Team data successfully done (Showing ALL records)",
            'data' => $data
        ]);
    }
}
