<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use Illuminate\Http\Request;

class HolidayController extends Controller
{
    public function index() {
        $holidays = Holiday::where('is_deleted', 0)->get();

        return response()->json([
            'Sucess' => true,
            'data' => $holidays
        ]);
    }

    public function show($id) {
        $holiday = Holiday::where('id', $id)
            ->where('is_deleted', 0)
            ->first();

        if (!$holiday) {
            return response()->json([
                'success' => false,
                'message' => 'Holiday Not Found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $holiday
        ]);
    }

    public function store(Request $request) {
        $holiday = Holiday::create([
            'date' => $request->date,
            'title' => $request->title,
            'status' => $request->status ?? 1,
            'is_deleted' => 0,
            'created_by' => $request->created_by,
        ]);

        return response()->json([
            'Success' => true,
            "data" => $holiday
        ]);
    }

    public function update(Request $request, $id) {
        $holiday = Holiday::find($id);

        if(!$holiday) {
            return response()->json([
                'Status' => false,
                'Message' => 'Holiday not found'
            ], 404);
        }

        $holiday->update($request->only(['date', 'title', 'status', 'is_deleted', 'updated_by']));

        return response()->json([
            'status' => true,
            'message' => 'Holiday Updated Sucessfully',
            'date' => $holiday
        ]);
    } 

    public function destroy($id) {
        $holiday = Holiday::find($id);

        if(!$holiday) {
            return response()->json([
                'Success' => false,
                'Message' => 'Record not found'
            ], 404);
        }

        $holiday->is_deleted = '1';
        $holiday->save();

        return response()->json([
            'Success' => true,
            'Message' => 'Holiday deleted Successfully'
        ]);
    }
}
