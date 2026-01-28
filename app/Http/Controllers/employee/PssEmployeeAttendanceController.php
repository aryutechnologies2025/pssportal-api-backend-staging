<?php

namespace App\Http\Controllers\employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PssEmployeeAttendance;
use Carbon\Carbon;
use App\Models\Activities;
use App\Models\PssWorkShift;
use Illuminate\Support\Str;
use App\Models\Employee;

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

        // 🔹 Save attendance testing
        PssEmployeeAttendance::create([
            'employee_id'     => $employeeId,
            'attendance_date' => $today,
            'attendance_time' => $nowTime,
            'shift_id'        => $shiftId,
            'reason'          => $reason,
            'photo'           => $photoPath,
            'latitude'        => $request->latitude,
            'longitude'       => $request->longitude,
            'location_details' => $request->location_details,
        ]);

        return response()->json([
            'success' => true,
            'message' => ucfirst($reason) . ' recorded successfully'
        ]);
    }

    public function empAttendance(Request $request)
     {
        $employee_id = $request->employee_id;
        $date = $request->date ?? Carbon::now()->format('Y-m-d');

        $records = PssEmployeeAttendance::with(['employee', 'shift'])
            ->where('attendance_date', $date)
            ->when($employee_id !== 'all', function ($q) use ($employee_id) {
                $q->where('employee_id', $employee_id);
            })
            ->orderBy('attendance_time')
            ->get()
            ->groupBy('employee_id');

        $report = [];
        $presentCount = 0;
        $absentCount  = 0;

        $pssemployees = Employee::where('status', 1)
            ->where('is_deleted', 0)
            ->where('id', '!=', 1)
            ->when($employee_id !== 'all', function ($q) use ($employee_id) {
                $q->where('id', $employee_id);
            })
            ->select('id', 'full_name', 'gen_employee_id')
            ->get();

        foreach ($pssemployees as $employee) {

            $logs = $records->get($employee->id);


            $attendanceDetails = [];

            if ($logs) {
                foreach ($logs as $log) {
                    $attendanceDetails[] = [
                        'reason'           => $log->reason,
                        'attendance_time'  => $log->attendance_time,
                        'created_at'       => $log->created_at->format('Y-m-d H:i:s'),
                        'location_details' => $log->location_details,
                        'profile_photo'    => $log->photo,
                    ];
                }
            }


            $login = null;
            $logout = null;
            $totalSeconds = 0;
            $breakSeconds = 0;
            $payableSeconds = 0;
            $breakCount = 0; // ✅ NEW

            if ($logs) {

                $login  = $logs->where('reason', 'login')->first();
                $logout = $logs->where('reason', 'logout')->last();

                $breakIns  = $logs->where('reason', 'breakin')->values();
                $breakOuts = $logs->where('reason', 'breakout')->values();

                // ✅ Break time + break count
                foreach ($breakIns as $index => $breakIn) {
                    if (isset($breakOuts[$index])) {
                        $breakSeconds += Carbon::parse($breakOuts[$index]->attendance_time)
                            ->diffInSeconds(Carbon::parse($breakIn->attendance_time));
                        $breakCount++; // ✅ COUNT
                    }
                }

                // if ($login || $logout) {
                //     $totalSeconds = Carbon::parse($logout->attendance_time)
                //         ->diffInSeconds(Carbon::parse($login->attendance_time));

                //     $payableSeconds = max($totalSeconds - $breakSeconds, 0);
                //     $status = 'Present';
                //     $presentCount++;
                // } else {
                //     $status = 'Absent';
                //     $absentCount++;
                // }

                if ($login) {

                    $status = 'Present';
                    $presentCount++;

                    if ($logout) {
                        $totalSeconds = Carbon::parse($logout->attendance_time)
                            ->diffInSeconds(Carbon::parse($login->attendance_time));
                    } else {
                        $totalSeconds = 0; // login but no logout
                    }

                    $payableSeconds = max($totalSeconds - $breakSeconds, 0);
                } else {

                    $status = 'Absent';
                    $absentCount++;
                    $totalSeconds = 0;
                    $payableSeconds = 0;
                }
            } else {
                $status = 'Absent';
                $absentCount++;
            }

            $report[] = [
                'employee_name' => $employee->full_name,
                'employee_id'   => $employee->gen_employee_id,
                'date'          => Carbon::parse($date)->format('d/m/Y'),
                'status'        => $status,
                'login_time'    => $login?->attendance_time,
                'logout_time'   => $logout?->attendance_time,

                // ✅ NEW FIELD FOR RECORD PAGE
                'break_count'   => $breakCount,

                'break_time'    => gmdate('H:i:s', $breakSeconds),
                'total_hours'   => gmdate('H:i:s', $totalSeconds),
                'payable_time' => gmdate('H:i:s', $payableSeconds),
                // ✅ FULL LOGIN DETAILS ARRAY
                'attendance_details' => $attendanceDetails,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $report,
            'summary' => [
                'present' => $presentCount,
                'absent'  => $absentCount,
                'total'   => $presentCount + $absentCount,
            ],
        ]);
    }

    public function empMonthlyAttendance(Request $request)
    {
        $employee_id = $request->employee_id ?? 'all';
        $month = $request->month ?? Carbon::now()->format('Y-m');

        // 🔹 Month range
        $startDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $endDate   = Carbon::createFromFormat('Y-m', $month)->endOfMonth();

        $pssemployees = Employee::where('status', 1)
            ->where('is_deleted', 0)
            ->where('id', '!=', 1)
            ->when($employee_id !== 'all', function ($q) use ($employee_id) {
                $q->where('id', $employee_id);
            })
            ->select('id', 'full_name')
            ->get();


        // 🔹 Attendance query
        $query = PssEmployeeAttendance::with(['employee', 'shift'])
            ->whereBetween('attendance_date', [$startDate, $endDate]);

        if ($employee_id !== 'all') {
            $query->where('employee_id', $employee_id);
        }

        $records = $query
            ->orderBy('attendance_date')
            ->orderBy('attendance_time')
            ->get()
            ->groupBy('attendance_date');

        $report = [];
        $presentDays = 0;
        $absentDays  = 0;

        foreach ($records as $date => $logs) {

            // ✅ get employee from first record
            $employee = $logs->first()->employee;
            $shift = $logs->first()->shift;

            $login  = $logs->where('reason', 'login')->first();
            $logout = $logs->where('reason', 'logout')->last();

            $totalSeconds = 0;
            $breakSeconds = 0;


            $breakIns  = $logs->where('reason', 'breakin')->values();
            $breakOuts = $logs->where('reason', 'breakout')->values();

            foreach ($breakIns as $index => $breakIn) {
                if (isset($breakOuts[$index])) {
                    $breakSeconds += Carbon::parse($breakOuts[$index]->attendance_time)
                        ->diffInSeconds(Carbon::parse($breakIn->attendance_time));
                }
            }

            // if ($login || $logout) {
            //     $presentDays++;

            //     $totalSeconds = Carbon::parse($logout->attendance_time)
            //         ->diffInSeconds(Carbon::parse($login->attendance_time));

            //     $payableSeconds = $totalSeconds - $breakSeconds;

            //     $status = 'Present';
            // } else {
            //     $absentDays++;
            //     $status = 'Absent';
            //     $payableSeconds = 0;
            // }


            if ($login) {

                $presentDays++;
                $status = 'Present';

                if ($logout) {
                    $totalSeconds = Carbon::parse($logout->attendance_time)
                        ->diffInSeconds(Carbon::parse($login->attendance_time));
                } else {
                    $totalSeconds = 0; // login exists but logout missing
                }

                $payableSeconds = max($totalSeconds - $breakSeconds, 0);
            } else {

                $absentDays++;
                $status = 'Absent';
                $totalSeconds = 0;
                $payableSeconds = 0;
            }


            $report[] = [
                'employee_name' => $employee?->full_name,
                'employee_id'   => $employee?->gen_employee_id,
                'shift_name'    => $shift?->shift_name,
                'date'          => Carbon::parse($date)->format('d/m/Y'),
                'status'        => $status,
                'login_time'    => $login?->attendance_time,
                'logout_time'   => $logout?->attendance_time,
                'break_time'    => gmdate('H:i:s', $breakSeconds),
                'total_hours'   => gmdate('H:i:s', $totalSeconds),
                'payable_time' => gmdate('H:i:s', max($payableSeconds, 0)),
            ];
        }


        return response()->json([
            'success' => true,
            'summary' => [
                'total_working_days' => $startDate->diffInDays($endDate) + 1,
                'present_days'       => $presentDays,
                'absent_days'        => $absentDays,
            ],
            'data' => $report
        ]);
    }
}
