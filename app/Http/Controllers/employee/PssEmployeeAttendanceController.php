<?php

namespace App\Http\Controllers\employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PssEmployeeAttendance;
use Carbon\Carbon;
use App\Models\Activities;
use App\Models\PssWorkShift;
use Illuminate\Support\Str;

class PssEmployeeAttendanceController extends Controller
{
    public function index(Request $request)
    {
        $employeeId = $request->employee_id;
        $attendance_date = $request->attendance_date;

        $attendance = PssEmployeeAttendance::with('shift')->where('employee_id', $employeeId)
            ->where('attendance_date', $attendance_date)
            ->orderBy('attendance_date', 'desc')
            ->orderBy('attendance_time', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $attendance
        ], 200);
    }
    // public function store(Request $request)
    // {
    //     $employeeId = $request->employee_id;
    //     $today      = $request->attendance_date;
    //     $nowTime    = $request->attendance_time;
    //     $reason     = $request->reason;
    //     $shiftId    = $request->shift;

    //     // 🔹 Fetch shift details
    //     $shift = PssWorkShift::where('id', $shiftId)
    //         ->where('is_deleted', 0)
    //         ->first();

    //     if (!$shift) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Invalid shift selected'
    //         ], 422);
    //     }
    //     /**
    //      * ✅ SHIFT TIME VALIDATION (LOGIN ONLY)
    //      */
    //     // 🔹 Time objects
    //     $shiftStart = Carbon::createFromFormat('H:i', $shift->start_time);
    //     $shiftEnd   = Carbon::createFromFormat('H:i', $shift->end_time);
    //     $current    = Carbon::createFromFormat('H:i:s', $nowTime);

    //     if ($reason === 'login') {

    //         $allowed = false;

    //         // 🌙 NIGHT SHIFT (cross midnight)
    //         if ($shiftStart->gt($shiftEnd)) {
    //             $allowed = $current->gte($shiftStart) || $current->lte($shiftEnd);
    //         }
    //         // 🌞 DAY SHIFT
    //         else {
    //             $allowed = $current->betweenIncluded($shiftStart, $shiftEnd);
    //         }

    //         if (!$allowed) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'This time is not allowed to login for this shift'
    //             ], 422);
    //         }
    //     }

    //     // 🔹 Last attendance record for today
    //     $lastEntry = PssEmployeeAttendance::where('employee_id', $employeeId)
    //         ->where('attendance_date', $today)
    //         ->orderBy('attendance_time', 'desc')
    //         ->first();

    //     /**
    //      * ✅ LOGIN
    //      */
    //     if ($reason === 'login') {

    //         Activities::create([
    //             'reason'     => 'login',
    //             'created_by' => $employeeId,
    //             'type'       => 'pss_emp'
    //         ]);

    //         if ($lastEntry && $lastEntry->reason !== 'logout') {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Already logged in. Please logout first.'
    //             ], 422);
    //         }
    //     }

    //     /**
    //      * ✅ LOGOUT
    //      */
    //     if ($reason === 'logout') {

    //         Activities::create([
    //             'reason'     => 'logout',
    //             'created_by' => $employeeId,
    //             'type'       => 'pss_emp'
    //         ]);

    //         if (!$lastEntry || $lastEntry->reason === 'logout') {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Please login before logout.'
    //             ], 422);
    //         }
    //     }

    //     // ✅ BREAK IN
    //     if ($reason === 'breakin') {
    //         if (!$lastEntry) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Please login before break in.'
    //             ], 422);
    //         }

    //         if (!in_array($lastEntry->reason, ['login', 'breakout'])) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Cannot break in now. Must be after login or previous break out.'
    //             ], 422);
    //         }
    //     }

    //     // ✅ BREAK OUT
    //     if ($reason === 'breakout') {
    //         if (!$lastEntry || $lastEntry->reason !== 'breakin') {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Please break in before break out.'
    //             ], 422);
    //         }
    //     }

    //     // ✅ Save attendance
    //     PssEmployeeAttendance::create([
    //         'employee_id'     => $employeeId,
    //         'attendance_date' => $today,
    //         'attendance_time' => $nowTime,
    //         'shift_id'           => $shiftId,
    //         'reason'          => $reason,
    //     ]);

    //     return response()->json([
    //         'success' => true,
    //         'message' => ucfirst($reason) . ' recorded successfully'
    //     ]);
    // }

    public function store(Request $request)
    {
        $employeeId = $request->employee_id;
        $today      = $request->attendance_date;
        $nowTime    = $request->attendance_time;
        $reason     = $request->reason;
        $shiftId    = $request->shift;

        // // 🔹 Fetch shift
        // $shift = PssWorkShift::where('id', $shiftId)
        //     ->where('is_deleted', 0)
        //     ->first();

        // if (!$shift) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Invalid shift selected'
        //     ], 422);
        // }

        // /**
        //  * ✅ SHIFT TIME CHECK (LOGIN ONLY)
        //  */
        // if ($reason === 'login') {

        //     $shiftStart = Carbon::createFromFormat('H:i', $shift->start_time);
        //     $shiftEnd   = Carbon::createFromFormat('H:i', $shift->end_time);
        //     $current    = Carbon::createFromFormat('H:i:s', $nowTime);

        //     $allowed = false;

        //     // 🌙 Night shift
        //     if ($shiftStart->gt($shiftEnd)) {
        //         $allowed = $current->gte($shiftStart) || $current->lte($shiftEnd);
        //     }
        //     // 🌞 Day shift
        //     else {
        //         $allowed = $current->betweenIncluded($shiftStart, $shiftEnd);
        //     }

        //     if (!$allowed) {
        //         return response()->json([
        //             'success' => false,
        //             'message' => 'This time is not allowed to login for this shift'
        //         ], 422);
        //     }
        // }

        // 🔹 Get last attendance
        $lastEntry = PssEmployeeAttendance::where('employee_id', $employeeId)
            ->where('attendance_date', $today)
            ->orderBy('id', 'desc')
            ->first();

        /**
         * ✅ ACTION VALIDATION
         */
        switch ($reason) {

            case 'login':
                if ($lastEntry && $lastEntry->reason !== 'logout') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Already logged in. Please logout first.'
                    ], 422);
                }
                break;

            case 'logout':
                if (!$lastEntry || !in_array($lastEntry->reason, ['login', 'breakin'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Logout allowed only after login or break in.'
                    ], 422);
                }
                break;

            case 'breakout':
                if (!$lastEntry || !in_array($lastEntry->reason, ['login', 'breakin'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Break out allowed only after login or break in.'
                    ], 422);
                }
                break;

            case 'breakin':
                if (!$lastEntry || $lastEntry->reason !== 'breakout') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Break in allowed only after break out.'
                    ], 422);
                }
                break;

            default:
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid action'
                ], 422);
        }

        // 🔹 Log activity
        Activities::create([
            'reason'     => $reason,
            'created_by' => $employeeId,
            'type'       => 'pss_emp'
        ]);

        /* ============================
            PROFILE PHOTO UPLOAD
        ============================ */

        // $photoPath = null;

        if ($request->hasFile('profile_picture')) {

            $photo = $request->file('profile_picture'); // ✅ UploadedFile

            $photoDir = public_path('uploads/attendance/selfies');
            if (!file_exists($photoDir)) {
                mkdir($photoDir, 0755, true);
            }

            $photoName = now()->format('YmdHis') . '_' . rand(10000, 99999) . '.' .
                $photo->getClientOriginalExtension();

            $photo->move($photoDir, $photoName);

            $photoPath = 'uploads/attendance/selfies/' . $photoName;
        }

        // 🔹 Save attendance
        PssEmployeeAttendance::create([
            'employee_id'     => $employeeId,
            'attendance_date' => $today,
            'attendance_time' => $nowTime,
            'shift_id'        => $shiftId,
            'reason'          => $reason,
            'photo'           => $photoPath,
            'latitude'        => $request->latitude,
            'longitude'       => $request->longitude,
        ]);

        return response()->json([
            'success' => true,
            'message' => ucfirst($reason) . ' recorded successfully'
        ]);
    }
}
