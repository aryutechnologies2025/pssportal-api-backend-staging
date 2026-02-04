<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use App\Models\Setting;
// use phpseclib\Crypt\Hash;
use Illuminate\Support\Facades\Hash;

class SettingController extends Controller
{
    /**
     * Create or Update Settings (Single API)
     */
    public function store(Request $request)
    {

        // Only ONE settings row
        $setting = Setting::first();

        $data = $request->only([
            'admin_email',
            'address',
            'gst_number',
            'date_format',
        ]);

        $uploadPath = public_path('uploads/setting');

        if (!File::exists($uploadPath)) {
            File::makeDirectory($uploadPath, 0755, true);
        }

        // SITE LOGO UPLOAD
        if ($request->hasFile('site_logo')) {
            if ($setting && $setting->site_logo && File::exists(public_path($setting->site_logo))) {
                File::delete(public_path($setting->site_logo));
            }

            $logoName = 'site_logo_' . time() . '.' . $request->site_logo->extension();
            $request->site_logo->move($uploadPath, $logoName);
            $data['site_logo'] = 'uploads/setting/' . $logoName;
        }

        // FAV ICON UPLOAD
        if ($request->hasFile('fav_icon')) {
            if ($setting && $setting->fav_icon && File::exists(public_path($setting->fav_icon))) {
                File::delete(public_path($setting->fav_icon));
            }

            $iconName = 'fav_icon_' . time() . '.' . $request->fav_icon->extension();
            $request->fav_icon->move($uploadPath, $iconName);
            $data['fav_icon'] = 'uploads/setting/' . $iconName;
        }

        // CREATE OR UPDATE
        if ($setting) {
            $setting->update($data);
            $message = 'Settings updated successfully';
        } else {
            $data['created_by'] = auth()->id();
            $setting = Setting::create($data);
            $message = 'Settings created successfully';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $setting
        ]);
    }

    /**
     * Get Settings
     */
    public function index()
    {
        // dd(Hash::make('pss@2026'));
        return response()->json([
            'success' => true,
            'data'    => Setting::first()
        ]);
    }


    public function loginsetting()
    {
        return response()->json([
            'success' => true,
            'data' => Setting::select('site_logo', 'fav_icon')->first()
        ]);
    }
}
