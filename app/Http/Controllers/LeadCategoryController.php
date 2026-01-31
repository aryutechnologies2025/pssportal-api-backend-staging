<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LeadManagementCategory;

class LeadCategoryController extends Controller
{
    // Controller methods will go here  
    public function store(Request $request)
    {
        $lead_category = LeadManagementCategory::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Lead Management Category created successfully'
        ]);
    }

    public function index()
    {
        $lead_categories = LeadManagementCategory::where('is_deleted', '0')->get();

        return response()->json([
            'success' => true,
            'data' => $lead_categories
        ]);
    }

    public function edit_form($id)
    {
        $lead_category = LeadManagementCategory::findorFail($id);

        return response()->json([
            'status' => true,
            'data' => $lead_category
        ]);
    }

    public function update(Request $request, $id)
    {
        $lead_category = LeadManagementCategory::findOrFail($id);

        $lead_category->name = $request->name;
        $lead_category->status = $request->status;
        $lead_category->updated_by = $request->updated_by;

        $lead_category->save();
        return response()->json([
            'status' => true,
            'message' => 'Lead Management Category updated successfully',
            'data' => $lead_category
        ]);
    }

    public function delete(Request $request)
    {
        $lead_category = LeadManagementCategory::find($request->record_id);

        if (!$lead_category) return response()->json([
            'status' => false,
            'message' => 'Record not found'
        ], 404);

        $lead_category->is_deleted = '1';
        $lead_category->save();

        return response()->json([
            'status' => true,
            'message' => 'Lead Management Category deleted successfully'
        ]);
    }
}
