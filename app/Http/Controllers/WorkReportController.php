<?php

namespace App\Http\Controllers;

use App\Models\WorkReport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Employee;

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
        $query = WorkReport::query();

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
            'employee' => $pssemployees
        ]);
    }

    public function employeeWorkReport(Request $request)
    {
        $query = WorkReport::with('employee')->where('created_by', $request->employee_id);

        if ($request->from_date && $request->to_date) {
            $query->whereBetween('report_date', [
                $request->from_date,
                $request->to_date
            ]);
        } elseif ($request->date) {
            $query->whereDate('report_date', $request->date);
        }

        // if ($request->employee_id) {
        //     $query->where('created_by', $request->employee_id);
        // }

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
}
