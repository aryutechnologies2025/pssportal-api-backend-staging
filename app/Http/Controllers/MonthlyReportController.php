<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use App\Models\Holiday;
use App\Models\PssEmployeeAttendance;
use App\Models\WorkReport;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class MonthlyReportController extends Controller
{
    // public function employeeMonthlyReport(Request $request)
    // {
    //     if (!$request->filled('employee_id')) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Employee ID is required.'
    //         ], 400);
    //     }
        
    //     $employeeId = $request->employee_id;
    //     $month = $request->month ?? Carbon::now()->format('Y-m');

    //     $startDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
    //     $endDate = Carbon::createFromFormat('Y-m', $month)->endOfMonth();
    //     $totalDaysInMonth = $startDate->daysInMonth;

    //     // Fetch Work Reports for the month
    //     $reports = WorkReport::with('employee')
    //         ->where('created_by', $employeeId)
    //         ->whereBetween('report_date', [$startDate->toDateString(), $endDate->toDateString()])
    //         ->latest()
    //         ->get();

    //     // Fetch Attendance for the month
    //     $attendanceRecords = PssEmployeeAttendance::where('employee_id', $employeeId)
    //         ->whereBetween('attendance_date', [$startDate->toDateString(), $endDate->toDateString()])
    //         ->get()
    //         ->groupBy('attendance_date');

    //     // Fetch Holidays for the month
    //     $holidaysCount = Holiday::whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
    //         ->where('is_deleted', 0)
    //         ->count();

    //     $presentDays = 0;
    //     $lessThan8Hours = 0;

    //     foreach ($attendanceRecords as $date => $logs) {
    //         $login = $logs->where('reason', 'login')->first();
    //         $logout = $logs->where('reason', 'logout')->last();

    //         if ($login) {
    //             $presentDays++;
    //             if ($logout) {
    //                 $hours = Carbon::parse($logout->attendance_time)->diffInHours(Carbon::parse($login->attendance_time));
    //                 if ($hours < 8) {
    //                     $lessThan8Hours++;
    //                 }
    //             } else {
    //                 // Login exists but no logout - depending on business rules, could count as less than 8
    //                 $lessThan8Hours++;
    //             }
    //         }
    //     }

    //     $absentDays = max(0, $totalDaysInMonth - $holidaysCount - $presentDays);

    //     $data = $reports->map(function ($report) use ($attendanceRecords) {
    //         $logs = $attendanceRecords->get($report->report_date);

    //         $login = null;
    //         $logout = null;
    //         $totalSeconds = 0;

    //         if ($logs) {
    //             $login = $logs->where('reason', 'login')->first();
    //             $logout = $logs->where('reason', 'logout')->last();

    //             if ($login && $logout) {
    //                 $totalSeconds = Carbon::parse($logout->attendance_time)
    //                     ->diffInSeconds(Carbon::parse($login->attendance_time));
    //             }
    //         }

    //         return [
    //             'id' => $report->id,
    //             'report' => $report->report,
    //             'report_date' => $report->report_date,
    //             'employee_id' => $report->employee->gen_employee_id ?? $report->employee->id,
    //             'login_time' => $login ? $login->attendance_time : null,
    //             'logout_time' => $logout ? $logout->attendance_time : null,
    //             'total_hours' => $totalSeconds > 0 ? gmdate('H:i:s', $totalSeconds) : '00:00:00',
    //             'employee_name' => $report->employee->full_name,
    //         ];
    //     });

    //     // $pssemployees = Employee::where('status', '1')
    //     //     ->where('is_deleted', 0)
    //     //     ->where('id', '!=', 1)
    //     //     ->select('full_name', 'id')
    //     //     ->get();

    //     return response()->json([
    //         'status' => true,
    //         'summary' => [
    //             'total_days' => $totalDaysInMonth,
    //             'present_days' => $presentDays,
    //             'absent_days' => $absentDays,
    //             'less_than_8hours' => $lessThan8Hours,
    //             'holidays' => $holidaysCount,
    //         ],
    //         'data' => $data,
    //         // 'employees' => $pssemployees,
    //     ]);
    // }

    public function employeeMonthlyReport(Request $request)
    {
        if (!$request->filled('employee_id')) {
            return response()->json([
                'status' => false,
                'message' => 'Employee ID is required.'
            ], 400);
        }

        $employeeId = $request->employee_id;
        $employee = Employee::find($employeeId);

        if (!$employee) {
            return response()->json([
                'status' => false,
                'message' => 'Employee not found.'
            ], 404);
        }

        $month = $request->month ?? Carbon::now()->format('Y-m');

        $startDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $endDate = Carbon::createFromFormat('Y-m', $month)->endOfMonth();
        $totalDaysInMonth = $startDate->daysInMonth;

        // Fetch Work Reports for the month, keyed by date
        // Note: keyBy will take the last item if duplicates exist, which works like 'latest' contextually
        $reports = WorkReport::with('employee')
            ->where('created_by', $employeeId)
            ->whereBetween('report_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get()
            ->keyBy('report_date');

        // Fetch Attendance for the month
        $attendanceRecords = PssEmployeeAttendance::where('employee_id', $employeeId)
            ->whereBetween('attendance_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get()
            ->groupBy('attendance_date');

        // Fetch Holidays for the month
        $holidaysCount = Holiday::whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->where('is_deleted', 0)
            ->count();

        $presentDays = 0;
        $lessThan8Hours = 0;

        // Calculate summary stats based on attendance
        foreach ($attendanceRecords as $date => $logs) {
            $login = $logs->where('reason', 'login')->first();
            $logout = $logs->where('reason', 'logout')->last();

            if ($login) {
                $presentDays++;
                if ($logout) {
                    $hours = Carbon::parse($logout->attendance_time)->diffInHours(Carbon::parse($login->attendance_time));
                    if ($hours < 8) {
                        $lessThan8Hours++;
                    }
                } else {
                    $lessThan8Hours++;
                }
            }
        }

        $absentDays = max(0, $totalDaysInMonth - $holidaysCount - $presentDays);

        // Limit the loop to "passed" days: min(End of Month, End of Today)
        $loopEndDate = $endDate->copy()->min(Carbon::now()->endOfDay());
        
        // However, if the requested month is entirely in the future, this range might be upside down or empty.
        // If requested month is "2099-01", startDate > now. min is now. startDate > loopEnd. Period invalid?
        // Let's handle generic case:
        if ($startDate->isFuture()) {
             $data = [];
        } else {
            $data = [];
            // Use CarbonPeriod to iterate
            $period = CarbonPeriod::create($startDate, $loopEndDate);

            foreach ($period as $date) {
                $dateString = $date->toDateString();
                
                $report = $reports->get($dateString);
                $logs = $attendanceRecords->get($dateString);

                $login = null;
                $logout = null;
                $totalSeconds = 0;

                if ($logs) {
                    $login = $logs->where('reason', 'login')->first();
                    $logout = $logs->where('reason', 'logout')->last();

                    if ($login && $logout) {
                        $totalSeconds = Carbon::parse($logout->attendance_time)
                            ->diffInSeconds(Carbon::parse($login->attendance_time));
                    }
                }

                $data[] = [
                    'id' => $report ? $report->id : null,
                    'date' => $dateString,
                    'employee_id' => $employee->gen_employee_id ?? $employee->id,
                    'login_time' => $login ? $login->attendance_time : null,
                    'logout_time' => $logout ? $logout->attendance_time : null,
                    'total_hours' => $totalSeconds > 0 ? gmdate('H:i:s', $totalSeconds) : '00:00:00',
                    'employee_name' => $employee->full_name,
                ];
            }
        }
        
        // Reverse array to show latest dates first if desired? The original code did `latest()` on reports.
        // Typically reports show most recent on top.
        $data = array_reverse($data);

        // $pssemployees = Employee::where('status', '1')
        //     ->where('is_deleted', 0)
        //     ->where('id', '!=', 1)
        //     ->select('full_name', 'id')
        //     ->get();

        return response()->json([
            'status' => true,
            'summary' => [
                'total_days' => $totalDaysInMonth,
                'present_days' => $presentDays,
                'absent_days' => $absentDays,
                'less_than_8hours' => $lessThan8Hours,
                'holidays' => $holidaysCount,
            ],
            'data' => $data,
            // 'employees' => $pssemployees,
        ]);
    }
}
