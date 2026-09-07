<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AvatarImage;
use App\Models\BatterySetting;
use App\Models\Mood;
use Illuminate\Http\Request;

class BatterySettingController extends Controller
{
    public function index()
    {
        // Sare battery settings fetch karo
        $batterySettings = BatterySetting::all();
        return view('admin.battery_setting.index', compact('batterySettings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'option_key'   => 'required|array',
            'option_value' => 'required|array',
        ]);
    
        foreach ($request->option_key as $index => $key) {
            $value = $request->option_value[$index] ?? null;
    
            if ($value !== null) {
                BatterySetting::where('option_key', $key)->update([
                    'option_value' => $value,
                ]);
            }
            if ($key === 'avtar_battery_percentage') {
                AvatarImage::query()->update([
                    'points' => $value
                ]);
                Mood::query()->update([
                    'points' => $value
                ]);
            }
        }
        return redirect()->back()->with('success', 'Change settings updated successfully!');
    }
}
