<?php

namespace App\Http\Controllers;

use App\Models\ContractCanEmp;
use Illuminate\Http\Request;

class RelievedController extends Controller
{
    public function list()
    {
        $employees = ContractCanEmp::where('status', 0)
            // ->where('is_deleted', 0)
            ->select(
                'id',
                'name as employee_name',
                'company_id',
                'joining_date',
                'relieving_date',
                'aadhar_number',
                'status'
            )
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Relieved list fetched successfully',
            'data' => $employees,
        ]);
    }

    public function edit_form($id)
    {
        $employee = ContractCanEmp::where('id', $id)
            ->where('status', 0)
            // ->where('is_deleted', 0)
            ->select(
                'id',
                'name as employee_name',
                'company_id',
                'joining_date',
                'relieving_date',
                'aadhar_number',
                'status'
            )
            ->firstOrFail();

        return response()->json([
            'status' => true,
            'data' => $employee,
        ]);
    }

    public function update(Request $request, $id)
    {
        $employee = ContractCanEmp::where('id', $id)
            ->firstOrFail();

        $payload = $request->only([
            'employee_name',
            'company_id',
            'joining_date',
            'relieving_date',
            'aadhar_number',
            'status',
        ]);

        if (array_key_exists('employee_name', $payload)) {
            $payload['name'] = $payload['employee_name'];
            unset($payload['employee_name']);
        }

        $payload = array_filter($payload, function ($value) {
            return !is_null($value);
        });

        if (!empty($payload)) {
            $employee->update($payload);
        }

        return response()->json([
            'status' => true,
            'message' => 'Relieved info updated successfully',
        ]);
    }

    public function delete($id)
    {
        $employee = ContractCanEmp::find($id);

        if (!$employee) {
            return response()->json([
                'status' => false,
                'message' => 'Record not found',
            ], 404);
        }

        $employee->update([
            'status' => 1
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Employee restored to active',
        ]);
    }
}
