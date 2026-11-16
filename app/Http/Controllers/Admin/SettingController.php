<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::all()->pluck('value', 'key')->toArray();
        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'geofence_enabled'   => 'sometimes|in:0,1',
            'geofence_latitude'  => 'nullable|required_if:geofence_enabled,1|numeric|between:-90,90',
            'geofence_longitude' => 'nullable|required_if:geofence_enabled,1|numeric|between:-180,180',
            'geofence_radius_m'  => 'nullable|integer|between:20,50000',
        ], [
            'geofence_latitude.required_if'  => 'Enter the office latitude to turn the geofence on.',
            'geofence_longitude.required_if' => 'Enter the office longitude to turn the geofence on.',
        ]);

        $data = $request->except(['_token', '_method', 'company_logo']);

        // Handle logo upload
        if ($request->hasFile('company_logo')) {
            $request->validate(['company_logo' => 'image|max:2048']);
            $old = Setting::where('key', 'company_logo')->value('value');
            if ($old) Storage::disk('public')->delete($old);
            $path = $request->file('company_logo')->store('settings', 'public');
            Setting::updateOrCreate(['key' => 'company_logo'], ['value' => $path]);
        }

        foreach ($data as $key => $value) {
            // Encrypt SMTP password before storing
            if ($key === 'smtp_password' && $value) {
                $value = encrypt($value);
            }
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        return redirect()->back()->with('success', 'Settings saved successfully.');
    }
}
