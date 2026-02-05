<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Activities;
use Illuminate\Support\Facades\Hash;
use App\Models\Setting;

class AdminController extends Controller
{
    public function login(Request $request)
    {
        // 1️⃣ Validate request
        $request->validate([
            'email' => 'required',
            'password' => 'required'
        ]);

        // 2️⃣ Find user by username
        $user = Employee::where('offical_email', $request->email)->where('status', '1')->where('is_deleted', 0)->first();


        // ❌ User not found
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid username or password'
            ], 401);
        }

        // ❌ Role check (ONLY role_id = 1 allowed)
        if ((int) $user->role_id !== 1) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        // 3️⃣ Check if user exists and password matches
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid username or password'
            ], 401);
        }

        Activities::create([
            'reason'          => 'login',
            'created_by'     => $user->id,
            'type' => 'admin'
        ]);

        $setting = Setting::select('date_format','site_logo','fav_icon')->first();
        // 4️⃣ Return success response (optionally add token later)
        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'user' => [
                'id' => $user->id,
                'email' => $user->offical_email,
                'role_id' => $user->role_id
            ],
            'setting' => $setting,
        ]);
    }

    public function logout(Request $request)
    {
        Activities::create([
            'reason'          => 'logout',
            'created_by'     => $request->id,
            'type' => 'admin'
        ]);
        // $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout successful'
        ]);
    }
}
