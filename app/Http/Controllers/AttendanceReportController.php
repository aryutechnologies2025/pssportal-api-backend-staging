<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\PssEmployeeAttendance;
use App\Models\Employee;

class AttendanceReportController extends Controller
{
    public function index(Request $request)
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
            'data' => $report,
            'employees' => $pssemployees
        ]);
    }

    public function daywiseAttendanceReport(Request $request)
    {
        $employee_id = $request->employee_id ?? 'all';
        $date = $request->date ?? Carbon::now()->format('Y-m-d');

        $pssemployees = Employee::where('status', 1)
            ->where('is_deleted', 0)
            ->where('id', '!=', 1)
            ->when($employee_id !== 'all', function ($q) use ($employee_id) {
                $q->where('id', $employee_id);
            })
            ->select('id', 'full_name', 'gen_employee_id')
            ->get();

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
            'employees' => $pssemployees
        ]);
    }
}
