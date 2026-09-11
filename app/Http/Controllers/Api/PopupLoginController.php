<?php

namespace App\Http\Controllers\Api;

use App\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\BatteryEvent;
use App\Models\PopupContent;
use App\Models\StaticContent;
use App\Models\User;
use Illuminate\Http\Request;
use Auth;

class PopupLoginController extends Controller
{
    public function index()
    {
        $popups = StaticContent::whereIn('slug', ['popup1', 'popup2'])->get()->keyBy('slug');
        $popup1 = PopupContent::where('type', 'popup1')->get();
        $popup2 = PopupContent::where('type', 'popup2')->get();
        return ApiResponse::success([
                'popup1' => $popup1,
                'popup2' => $popup2,
            ], 'Get Static Popup data', 200);
    }

    public function store(Request $request)
    {
        try {
            $user = Auth::user();

            if($request->popup1){
                $user->update([
                    'popup_1' => $request->popup1 ?? 'no',
                    'popup_1_updated_at' => now(),
                ]);
            }
            if($request->popup2){
                $user->update([
                    'popup_2' => $request->popup2 ?? 'no',
                    'popup_2_updated_at' => now(),
                ]);
            }
            return ApiResponse::success(null, 'User popup updated successfully.');
        } catch (\Exception $e) {
            \Log::error('popup-login update failed: ' . $e->getMessage());

            return ApiResponse::error('Something went wrong.', 200);
        }
    }


}
