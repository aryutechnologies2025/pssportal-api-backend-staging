<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Announcement;
use Illuminate\Support\Facades\Validator;

class AnnouncementController extends Controller
{
    /** List */
    public function list(Request $request)
    {
        $announcements = Announcement::where('is_deleted', 0)->orderBy('id', 'desc')->get();

        return response()->json([
            'status'  => true,
            'message' => 'Announcements fetched successfully',
            'data'    => $announcements,
        ]);
    }

    /** Insert */
    public function insert(Request $request)
    {

        $announcement = Announcement::create([
            'start_date'           => $request->start_date,
            'expiry_date'          => $request->expiry_date,
            'announcement_details' => $request->announcement_details,
            'visible_to'           => $request->visible_to,
            'status'               => $request->status,
            'created_by'           => $request->created_by,
            'is_deleted'           => 0,
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Announcement created successfully',
            'data'    => $announcement,
        ], 201);
    }

    /** Edit form / Show */
    public function show($id)
    {
        $announcement = Announcement::where('id', $id)
            ->where('is_deleted', 0)
            ->firstOrFail();

        return response()->json([
            'status' => true,
            'data'   => $announcement,
        ]);
    }

    /** Update */
    public function update(Request $request, $id)
    {
        
        $announcement = Announcement::findOrFail($id);

        $announcement->update([
            'start_date'           => $request->start_date,
            'expiry_date'          => $request->expiry_date,
            'announcement_details' => $request->announcement_details,
            'visible_to'           => $request->visible_to,
            'status'               => $request->status,
            'updated_by'           => $request->updated_by,
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Announcement updated successfully',
            'data'    => $announcement,
        ]);
    }

    /** Soft Delete */
    public function delete(Request $request)
    {
        $announcement = Announcement::find($request->record_id);

        if (!$announcement) {
            return response()->json([
                'status'  => false,
                'message' => 'Record not found',
            ], 404);
        }

        $announcement->update(['is_deleted' => 1]);

        return response()->json([
            'status'  => true,
            'message' => 'Announcement deleted successfully',
        ]);
    }
}
