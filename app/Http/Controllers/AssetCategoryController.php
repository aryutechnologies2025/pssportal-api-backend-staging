<?php

namespace App\Http\Controllers;

use App\Models\AssetCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class AssetCategoryController extends Controller
{
     public function index()
    {
        $categories = AssetCategory::where('is_deleted', 0)->get();

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    public function show($id)
    {
        $category = AssetCategory::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $category
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $category = AssetCategory::create([
            'name'       => $request->name,
            'status'     => $request->status ?? 1,
            'created_by' => $request->created_by,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Asset category created successfully',
            'data' => $category
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $category = AssetCategory::findOrFail($id);

        $category->update([
            'name'       => $request->name,
            'status'     => $request->status ?? $category->status,
            'updated_by' => $request->updated_by,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Asset category updated successfully',
            'data' => $category
        ]);
    }

    public function destroy($id)
    {
        $category = AssetCategory::find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Record not found'
            ], 404);
        }

        $category->is_deleted = 1;
        $category->save();

        return response()->json([
            'success' => true,
            'message' => 'Asset category deleted successfully'
        ]);
    }
}
