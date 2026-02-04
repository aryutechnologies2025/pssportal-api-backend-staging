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

        $reports = WorkReport::where('created_by', $employeeId)
            ->whereBetween('report_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get()
            ->groupBy('report_date');

        $loopEndDate = $endDate->copy()->min(Carbon::now()->endOfDay());

        $data = [];
        if (!$startDate->isFuture()) {
            $period = CarbonPeriod::create($startDate, $loopEndDate);

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

                $data[] = [
                    'date' => $dateString,
                    'day' => $date->format('l'),
                    'messages' => $messages,
                ];
            }
        }

        $data = array_reverse($data);

        return response()->json($data);
    }
}
