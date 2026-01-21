<?php

namespace App\Http\Controllers\employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Employee;
use Illuminate\Support\Facades\Hash;
use App\Models\Permission;
use App\Models\Setting;

class EmployeeAuthController extends Controller
{
    public function login(Request $request)
    {

        // dd($request->all());
        // dd(Hash::make('Portal#123'));
        $employee = Employee::select('id', 'offical_email', 'password', 'company_id', 'role_id', 'full_name', 'photo')->where('id', '!=', 1)->where('offical_email', $request->email)->first();

        // dd($employee);

        if (!$employee || !Hash::check($request->password, $employee->password)) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }

        // dd($employee->id);
        // 2️⃣ FIRST: Check employee-level permission
        $permission = Permission::with('modules')
            ->where('privilege_for', 'employee')
            ->select('id','role_id','privilege_for')
            ->where('role_id', $employee->id) // employee id
            ->where('is_deleted', '0')
            ->first();
        // 3️⃣ FALLBACK: Role-level permission
        if (!$permission) {
            $permission = Permission::with('modules')
                ->where('privilege_for', 'role')
                ->where('role_id', $employee->role_id) // role id
                ->where('is_deleted', '0')
                ->first();
        }

        session(['employee_id' => $employee->id]);

        $setting = Setting::select('date_format')->first();

        return response()->json([
            'status' => true,
            'employee' => [
                'id' => $employee->id,
                'official_email' => $employee->offical_email,
                'role_id' => $employee->role_id,
                'company_id' => $employee->company_id,
                'full_name' => $employee->full_name,
                'photo' => $employee->photo,
            ],
            'setting' => $setting,
            'permission' => $permission
        ]);
    }

    public function logout()
    {
        session()->forget('employee_id');
        return response()->json(['status' => true]);
    }
}
