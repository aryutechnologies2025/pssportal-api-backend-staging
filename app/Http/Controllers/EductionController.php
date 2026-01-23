<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Eductions;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class EductionController extends Controller
{
    /** List */
    public function list()
    {
        $educations = Eductions::where('is_deleted', 0)
            ->select('id', 'eduction_name', 'status')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Education list fetched successfully',
            'data' => $educations,
        ]);
    }

    /** Insert */
    public function insert(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'eduction_name' => [
                'required',
                Rule::unique('eductions')
                    ->where(fn ($q) => $q->where('is_deleted', 0)),
            ],
            'status' => 'required|in:0,1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $education = Eductions::create([
            'eduction_name' => $request->eduction_name,
            'status'        => $request->status,
            'created_by'    => $request->created_by,
            'is_deleted'    => 0,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Education created successfully',
            'data' => $education,
        ], 201);
    }

    /** Edit form */
    public function edit_form($id)
    {
        $education = Eductions::findOrFail($id);

        return response()->json([
            'status' => true,
            'data' => $education,
        ]);
    }

    /** Update */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'eduction_name' => [
                'required',
                Rule::unique('eductions')
                    ->where(fn ($q) => $q->where('is_deleted', 0))
                    ->ignore($id),
            ],
            'status' => 'required|in:0,1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $education = Eductions::findOrFail($id);
        $education->update([
            'eduction_name' => $request->eduction_name,
            'status'        => $request->status,
            'updated_by'    => $request->updated_by,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Education updated successfully',
            'data' => $education,
        ]);
    }

    /** Soft delete */
    public function delete(Request $request)
    {
        $education = Eductions::find($request->record_id);

        if (!$education) {
            return response()->json([
                'status' => false,
                'message' => 'Record not found',
            ], 404);
        }

        $education->update(['is_deleted' => 1]);

        return response()->json([
            'status' => true,
            'message' => 'Education deleted successfully',
        ]);
    }
}
