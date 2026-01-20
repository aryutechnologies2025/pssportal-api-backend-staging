<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Role;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\Department;
use App\Models\PssCompany;
use Illuminate\Support\Facades\Hash;

class RoleController extends Controller
{
    public function list(Request $request)
    {
        // dd(Hash::make('PSStamil@8687'));
        // $roles = Role::with('department')
        //     ->whereNotNull('department_id')
        //     ->where('is_deleted', '0')
        //     ->where('id', '!=', 1)
        //     ->get();

        // $departments = Department::where('status', '1')
        //     ->where('is_deleted', '0')
        //     ->get();

        // return response()->json([
        //     'status'  => true,
        //     'message' => 'Role list fetched successfully',
        //     'data'    => $roles,
        //     'departments' => $departments
        // ]);

        $roles = Role::with(['department', 'company'])
        ->whereNotNull('department_id')
        ->where('is_deleted', '0')
        ->where('id', '!=', 1)
        ->get();

    $departments = Department::where('status', '1')
        ->where('is_deleted', '0')
        ->get();

    $psscompanies = PssCompany::where('status', '1')
        ->where('is_deleted', '0')
        ->select('id', 'name')
        ->get();

    return response()->json([
        'status'      => true,
        'message'     => 'Role list fetched successfully',
        'data'        => $roles,
        'departments' => $departments,
        'psscompany'  => $psscompanies,
    ]);
    }


    public function insert(Request $request)
    {
        // $validator = Validator::make($request->all(), [
        //     'role_name' => [
        //         'required',
        //         Rule::unique('roles')->where(fn($q) => $q->where('is_deleted', '0')),
        //     ],
        //     'department_id' => 'required|exists:departments,id'
        // ]);


        // if ($validator->fails()) {
        //     return response()->json([
        //         'status' => false,
        //         'message' => 'Validation error',
        //         'errors' => $validator->errors()
        //     ]);
        // }

        // $role = new Role;
        // $role->role_name = $request->role_name;
        // $role->department_id = $request->department_id;
        // $role->status = $request->status;
        // $role->created_date = now();
        // $role->created_by = $request->created_by;
        // $role->is_deleted = '0';
        // $role->save();

        // return response()->json([
        //     'status' => true,
        //     'message' => 'Role created successfully',
        //     'data' => $role
        // ], 201);

        $validator = Validator::make($request->all(), [
        'role_name' => [
            'required',
            Rule::unique('roles')->where(fn($q) => $q->where('is_deleted', '0')),
        ],
        'department_id' => 'required|exists:departments,id',
        'company_id'    => 'required|exists:pss_company,id',
        ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => false,
            'message' => 'Validation error',
            'errors' => $validator->errors()
        ]);
    }

    $role = new Role;
    $role->role_name     = $request->role_name;
    $role->department_id = $request->department_id;
    $role->company_id    = $request->company_id; // ✅ important
    $role->status        = $request->status;
    $role->created_date  = now();
    $role->created_by    = $request->created_by;
    $role->is_deleted    = '0';
    $role->save();

    return response()->json([
        'status' => true,
        'message' => 'Role created successfully',
        'data' => $role
    ], 201);
    }


    public function edit_form(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        return response()->json([
            'status' => true,
            'data' => $role
        ]);
    }

    public function update(Request $request, $id)
    {
        // Validate (ignore unique check on the current record)
        // $validator = Validator::make($request->all(), [
        //     'role_name' => [
        //         'required',
        //         Rule::unique('roles', 'role_name')
        //             ->where(function ($query) {
        //                 $query->where('is_deleted', 0);
        //             })
        //             ->ignore($id),
        //     ],
        // ]);

        // if ($validator->fails()) {
        //     return response()->json([
        //         'status' => false,
        //         'message' => 'Validation error',
        //         'errors' => $validator->errors()
        //     ], 422);
        // }

        // // Find Role
        // $role = Role::findOrFail($id);
        // $role->role_name = $request->role_name;
        // $role->department_id = $request->department_id;
        // $role->status = $request->status;
        // $role->updated_by = $request->updated_by;
        // $role->save();

        // return response()->json([
        //     'status' => true,
        //     'message' => 'Role updated successfully',
        //     'data' => $role
        // ]);
         $validator = Validator::make($request->all(), [
        'role_name' => [
            'required',
            Rule::unique('roles', 'role_name')
                ->where(fn($q) => $q->where('is_deleted', 0))
                ->ignore($id),
        ],
        'department_id' => 'required|exists:departments,id',
        'company_id'    => 'required|exists:pss_company,id',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => false,
            'message' => 'Validation error',
            'errors' => $validator->errors()
        ], 422);
    }

        $role = Role::findOrFail($id);
        $role->role_name     = $request->role_name;
        $role->department_id = $request->department_id;
        $role->company_id    = $request->company_id; // ✅ added
        $role->status        = $request->status;
        $role->updated_by    = $request->updated_by;
        $role->save();

    return response()->json([
        'status' => true,
        'message' => 'Role updated successfully',
        'data' => $role
        ]);
    }

    public function delete(Request $request)
    {
        $role = Role::find($request->record_id);

        if (!$role) {
            return response()->json([
                'status' => false,
                'message' => 'Record not found'
            ], 404);
        }

        $role->is_deleted = '1';
        $role->save();

        return response()->json([
            'status' => true,
            'message' => 'Role deleted successfully'
        ]);
    }
}
