<?php

namespace App\Http\Controllers;

use App\Models\WorkReport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\PssEmployeeAttendance;

class WorkReportController extends Controller
{
    public function index(Request $request)
    {
        $query = WorkReport::with('employee');

        if ($request->from_date && $request->to_date) {
            $query->whereBetween('report_date', [
                $request->from_date,
                $request->to_date
            ]);
        } elseif ($request->date) {
            $query->whereDate('report_date', $request->date);
        }

        if ($request->employee_id) {
            $query->where('created_by', $request->employee_id);
        }

        $reports = $query->latest()->get();

        $pssemployees = Employee::where('status', '1')
            ->where('is_deleted', 0)
            ->where('id', '!=', 1)
            // ->where('job_form_referal', 1)
            ->select('full_name', 'id')
            ->get();

        return response()->json([
            'status' => true,
            'data' => $reports,
            'employees' => $pssemployees
        ]);
    }

    public function show($id)
    {
        $report = WorkReport::find($id);

        if (!$report) {
            return response()->json([
                'status' => false,
                'message' => 'Work report not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $report
        ]);
    }

    public function store(Request $request)
    {
        $report = WorkReport::create([
            'report' => $request->report,
            'report_date' => $request->report_date ?? Carbon::today(),
            'created_by' => $request->created_by
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Work report created successfully',
            'data' => $report
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $report = WorkReport::find($id);

        if (!$report) {
            return response()->json([
                'status' => false,
                'message' => 'Work report not found'
            ], 404);
        }

        $report->update($request->only(['report', 'report_date', 'created_by']));

        return response()->json([
            'status' => true,
            'message' => 'Work report updated successfully',
            'data' => $report
        ]);
    }

    public function filterByDate(Request $request)
    {
        // $query = WorkReport::query();
        $query = WorkReport::with('employee');

        if ($request->from_date && $request->to_date) {
            $query->whereBetween('report_date', [
                $request->from_date,
                $request->to_date
            ]);
        } elseif ($request->date) {
            $query->whereDate('report_date', $request->date);
        }

        if ($request->employee_id) {
            $query->where('created_by', $request->employee_id);
        }

        $reports = $query->latest()->get();

        $pssemployees = Employee::where('status', '1')
        ->where('is_deleted', 0)
        ->where('id', '!=', 1)
        // ->where('job_form_referal', 1)
        ->select('full_name', 'id', 'gen_employee_id')
        ->get();

        return response()->json([
            'status' => true,
            'data' => $reports,  
        ]);
    }

    public function employeeWorkReport(Request $request)
    {
        $employeeId = $request->employee_id;
        $month = $request->month ?? Carbon::now()->format('Y-m');

        $startDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $endDate = Carbon::createFromFormat('Y-m', $month)->endOfMonth();
        $totalDaysInMonth = $startDate->daysInMonth;

        // Fetch Work Reports for the month
        $reports = WorkReport::with('employee')
            ->where('created_by', $employeeId)
            ->whereBetween('report_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->latest()
            ->get();

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
                    // Login exists but no logout - depending on business rules, could count as less than 8
                    $lessThan8Hours++;
                }
            }
        }

        $absentDays = max(0, $totalDaysInMonth - $holidaysCount - $presentDays);

        $data = $reports->map(function ($report) use ($attendanceRecords) {
            $logs = $attendanceRecords->get($report->report_date);

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

            return [
                'id' => $report->id,
                'report' => $report->report,
                'report_date' => $report->report_date,
                'employee_id' => $report->employee->gen_employee_id ?? $report->employee->id,
                'login_time' => $login ? $login->attendance_time : null,
                'logout_time' => $logout ? $logout->attendance_time : null,
                'total_hours' => $totalSeconds > 0 ? gmdate('H:i:s', $totalSeconds) : '00:00:00',
                'employee_name' => $report->employee->full_name,
            ];
        });

        $pssemployees = Employee::where('status', '1')
            ->where('is_deleted', 0)
            ->where('id', '!=', 1)
            ->select('full_name', 'id')
            ->get();

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
            'employees' => $pssemployees,
        ]);
    }
}
