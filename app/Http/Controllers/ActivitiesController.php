<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Activities;

class ActivitiesController extends Controller
{
    public function activities(Request $request)
    {
        $activities = Activities::with(['employee.role'])
            ->latest()
            ->get()
            ->map(function ($activity) {
                return [
                    'id'               => $activity->id,
                    'reason'           => $activity->reason,
                    'type'             => $activity->type,
                    'employee_name'    => $activity->employee->full_name ?? null,
                    'employee_id' => $activity->employee->gen_employee_id ?? null,
                    'profile_image' => $activity->attendance->photo ?? null,
                    'location_details' => $activity->attendance->location_details ?? null,
                    'employee_id' => $activity->employee->gen_employee_id ?? null,
                    'role_name'        => $activity->employee->role->role_name ?? null,
                    'created_at'       => $activity->created_at,
                ];
            });

        return response()->json([
            'success'   => true,
            'data'      => $activities

        ]);
    }

    public function empActivities(Request $request)
    {
        $employee_id = $request->employee_id;
        $activities = Activities::with(['employee.role'])
            ->where('created_by', $employee_id)
            ->latest()
            ->get()
            ->map(function ($activity) {
                return [
                    'id'            => $activity->id,
                    'reason'        => $activity->reason,
                    'type'          => $activity->type,
                    'profile_image' => $activity->attendance->photo ?? null,
                    'location_details' => $activity->attendance->location_details ?? null,
                    'employee_name' => $activity->employee->full_name ?? null,
                    'employee_id' => $activity->employee->gen_employee_id ?? null,
                    'role_name'     => $activity->employee->role->role_name ?? null,
                    'created_at'    => $activity->created_at,
                ];
            });

        return response()->json([
            'success'   => true,
            'data'      => $activities

        ]);
    }
}
