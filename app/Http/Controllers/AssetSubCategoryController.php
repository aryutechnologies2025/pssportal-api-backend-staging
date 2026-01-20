<?php

namespace App\Http\Controllers;

use App\Models\AssetSubCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AssetSubCategoryController extends Controller
{
   public function index()
    {
        $subCategories = AssetSubCategory::where('is_deleted', 0)->get();

        return response()->json([
            'success' => true,
            'data' => $subCategories
        ]);
    }

    public function show($id)
    {
        $subCategory = AssetSubCategory::with('category')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $subCategory
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

        $subCategory = AssetSubCategory::create([
            'name'       => $request->name,
            'status'     => $request->status ?? 1,
            'created_by' => $request->created_by,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Asset sub-category created successfully',
            'data' => $subCategory
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

        $subCategory = AssetSubCategory::findOrFail($id);

        $subCategory->update([
            'name'       => $request->name,
            'status'     => $request->status ?? $subCategory->status,
            'updated_by' => $request->updated_by,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Asset sub-category updated successfully',
            'data' => $subCategory
        ]);
    }

    public function destroy($id)
    {
        $subCategory = AssetSubCategory::find($id);

        if (!$subCategory) {
            return response()->json([
                'success' => false,
                'message' => 'Record not found'
            ], 404);
        }

        $subCategory->is_deleted = 1;
        $subCategory->save();

        return response()->json([
            'success' => true,
            'message' => 'Asset sub-category deleted successfully'
        ]);
    }
}
