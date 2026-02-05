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
    public function employeeMonthlyReport(Request $request)
    {
        $employees = Employee::select('id', 'full_name as name')->get(); // change column if needed

        if (!$request->filled('employee_id')) {
            return response()->json([
                'status' => true,
                'employees' => $employees,
                'data' => []
            ]);
        }

        $employeeId = $request->employee_id;
        $employee = Employee::find($employeeId);

        if (!$employee) {
            return response()->json([
                'status' => false,
                'message' => 'Employee not found.',
                'employees' => $employees
            ], 404);
        }

        $month = $request->month ?? Carbon::now()->format('Y-m');
        $startDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $endDate = Carbon::createFromFormat('Y-m', $month)->endOfMonth();

        // If selected month is current month, limit to today
        $today = Carbon::now()->endOfDay();
        if ($startDate->isSameMonth($today)) {
            $endDate = $endDate->min($today);
        }

        // If month is in the future, return empty
        if ($startDate->isFuture()) {
            return response()->json([
                'status' => true,
                'employees' => $employees,
                'data' => []
            ]);
        }

        $reports = WorkReport::where('created_by', $employeeId)
            ->whereBetween('report_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get()
            ->groupBy('report_date');

        $attendance = PssEmployeeAttendance::where('employee_id', $employeeId)
            ->whereBetween('attendance_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get()
            ->groupBy('attendance_date');


        $period = CarbonPeriod::create($startDate, $endDate);
        $data = [];

        foreach ($period as $date) {
            $dateString = $date->toDateString();

            $messages = [];
            if ($reports->has($dateString)) {
                $messages = $reports->get($dateString)
                    ->pluck('report')
                    ->filter()
                    ->values()
                    ->toArray();
            }

            $attRows = $attendance->get($dateString, collect());

            $loginTime = $attRows->where('reason', 'login')->min('attendance_time');
            $logoutTime = $attRows->where('reason', 'logout')->max('attendance_time');
            $totalHours = '00:00:00';

            if ($loginTime && $logoutTime) {
                $start = Carbon::parse($loginTime);
                $end = Carbon::parse($logoutTime);

                if ($end->lessThan($start)) {
                    $end->addDay(); 
                }

                $totalSeconds = $start->diffInSeconds($end);
                $totalHours = gmdate('H:i:s', $totalSeconds);
            }

            $data[] = [
                'date' => $dateString,
                'day' => $date->format('l'),
                'messages' => $messages,
                'login_time' => $loginTime,
                'logout_time' => $logoutTime,
                'total_hours' => $totalHours,
            ];
        }

        $data = array_reverse($data);

        return response()->json([
            'status' => true,
            'employees' => $employees,
            'data' => $data
        ]);
    }
}
