<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BoardingPoint;
use App\Models\Company;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class BoardingPointController extends Controller
{
    /** List */
    public function list()
    {
        $boardingPoints = BoardingPoint::where('is_deleted', 0)
            ->select('id', 'point_name', 'company_id', 'status')
            ->get();

        $pssCompanies = Company::where('status', 1)
            ->where('is_deleted', 0)
            ->select('id', 'name')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Boarding points fetched successfully',
            'data' => $boardingPoints,
            'psscompany' => $pssCompanies,
        ]);
    }

    /** Insert */
    public function insert(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'point_name' => [
                'required',
                Rule::unique('boarding_points')
                    ->where(fn ($q) => $q->where('is_deleted', 0)),
            ],
            'company_id' => 'required|exists:pss_companies,id',
            'status'     => 'required|in:0,1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $boardingPoint = BoardingPoint::create([
            'point_name' => $request->point_name,
            'company_id' => $request->company_id,
            'status'     => $request->status,
            'created_by' => $request->created_by,
            'is_deleted' => 0,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Boarding point created successfully',
            'data' => $boardingPoint,
        ], 201);
    }

    /** Edit form */
    public function edit_form($id)
    {
        $boardingPoint = BoardingPoint::findOrFail($id);

        return response()->json([
            'status' => true,
            'data' => $boardingPoint,
        ]);
    }

    /** Update */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'point_name' => [
                'required',
                Rule::unique('boarding_points')
                    ->where(fn ($q) => $q->where('is_deleted', 0))
                    ->ignore($id),
            ],
            'company_id' => 'required|exists:pss_companies,id',
            'status'     => 'required|in:0,1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $boardingPoint = BoardingPoint::findOrFail($id);
        $boardingPoint->update([
            'point_name' => $request->point_name,
            'company_id' => $request->company_id,
            'status'     => $request->status,
            'updated_by' => $request->updated_by,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Boarding point updated successfully',
            'data' => $boardingPoint,
        ]);
    }

    /** Soft delete */
    public function delete(Request $request)
    {
        $boardingPoint = BoardingPoint::find($request->record_id);

        if (!$boardingPoint) {
            return response()->json([
                'status' => false,
                'message' => 'Record not found',
            ], 404);
        }

        $boardingPoint->update(['is_deleted' => 1]);

        return response()->json([
            'status' => true,
            'message' => 'Boarding point deleted successfully',
        ]);
    }
}
