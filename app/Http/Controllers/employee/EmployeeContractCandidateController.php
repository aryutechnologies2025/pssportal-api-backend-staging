<?php

namespace App\Http\Controllers\employee;

use App\Http\Controllers\ContractEmployeeController;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ContractEmployee;
use App\Models\Employee;
use App\Models\Attendance;
use App\Models\Company;
use App\Models\ContractCanEmp;
use Carbon\Carbon;

class EmployeeContractCandidateController extends Controller
{
    public function list(Request $request)
    {
        // Get company_id from request
        $companyIds = $request->company_id;

        // Convert "18,19" → [18, 19]
        if ($companyIds) {
            $companyIds = explode(',', $companyIds);
        }

        $employees = ContractEmployee::where('status', 1)
            ->where('is_deleted', 0)
            ->when($companyIds, function ($query) use ($companyIds) {
                $query->whereIn('company_id', $companyIds);
            })
            ->with('notes')
            ->select('id', 'company_id', 'reference', 'other_reference', 'interview_status', 'joining_status', 'education_id', 'status', 'created_at')
            ->orderByDesc('id')
            ->get();

        $pssemployees = Employee::where('status', '1')->where('is_deleted', 0)
            ->where('id', '!=', 1)
            ->where('job_form_referal', 1)
            ->select('full_name', 'id')
            ->get();

        // $companyIds = $request->emp_company_id;
        // $emp_id = $request->employee_id;

        // // Convert "18,19" → [18, 19]
        // if ($companyIds) {
        //     $companyIds = explode(',', $companyIds);
        // }

        $companies = Company::where('status', 1)
            ->when($companyIds, function ($query) use ($companyIds) {
                $query->whereIn('id', $companyIds);
            })
            ->where('is_deleted', 0)
            ->select('id', 'company_name')
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'employees' => $employees,
                'pssemployees' => $pssemployees,
                'companies' => $companies
            ]
        ]);
    }


    public function index(Request $request)
    {
        $query = Attendance::with([
            'company:id,company_name',
            'details'
        ])->where('is_deleted', 0);

        $companyIds = $request->emp_company_id;
        $emp_id = $request->employee_id;

        // Convert "18,19" → [18, 19]
        if ($companyIds) {
            $companyIds = explode(',', $companyIds);
        }

        // dd($companyIds);

        if ($companyIds) {
            $query->whereIn('company_id', $companyIds);
        }
        // Date filter
        if ($request->filled('from_date') && $request->filled('to_date')) {
            $from = Carbon::parse($request->from_date)->startOfDay();
            $to   = Carbon::parse($request->to_date)->endOfDay();

            $query->whereBetween('created_at', [$from, $to]);
        }

        // // Company filter
        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }


        // Created by / Updated by filter (proper grouping)
        if ($request->filled('created_by')) {
            $query->where(function ($q) use ($request) {
                $q->where('created_by', $request->created_by)
                    ->orWhere('updated_by', $request->created_by);
            });
        }

        $data = $query->latest()->get();

        // Companies dropdown
        $companies = Company::where('status', 1)
            ->when($companyIds, function ($query) use ($companyIds) {
                $query->whereIn('id', $companyIds);
            })
            ->where('is_deleted', 0)
            ->select('id', 'company_name')
            ->latest()
            ->get();

        // Created by dropdown
        $createdby = Employee::where('status', 1)
            ->where('is_deleted', 0)
            ->where('id', $emp_id)
            ->where('id', '!=', 1)
            ->select('id', 'full_name')
            ->latest()
            ->get();

        return response()->json([
            'success'   => true,
            'data'      => $data,
            'companies' => $companies,
            'createdby' => $createdby
        ]);
    }

    public function contractemplist(Request $request)
    {
        $companyIds = $request->emp_company_id; // "18,19"
        $emp_id     = $request->employee_id;

        // Convert "18,19" → [18, 19]
        $companyIds = $companyIds ? explode(',', $companyIds) : null;

        // 🔹 EMPLOYEES (Contract)
        $employeesQuery = ContractCanEmp::where('is_deleted', 0);

        // Multiple companies filter
        if (!empty($companyIds)) {
            $employeesQuery->whereIn('company_id', $companyIds);
        }

        // Optional filters
        $employeesQuery
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->when($request->filled('gender'), function ($q) use ($request) {
                $q->where('gender', $request->gender);
            })
            ->when($request->filled('company_id'), function ($q) use ($request) {
                $q->where('company_id', $request->company_id);
            })
            ->when($request->filled('from_date') && $request->filled('to_date'), function ($q) use ($request) {
                $from = Carbon::parse($request->from_date)->startOfDay();
                $to   = Carbon::parse($request->to_date)->endOfDay();
                $q->whereBetween('joining_date', [$from, $to]);
            });

        $employees = $employeesQuery
            ->with(['notes', 'company'])
             ->select('id', 'company_id', 'name', 'employee_id', 'phone_number', 'aadhar_number', 'joining_date', 'status', 'created_at','gender', 'date_of_birth')
            ->orderByDesc('id')
            ->get();

        // 🔹 COMPANIES
        // $companies = Company::where('status', 1)
        //     ->where('is_deleted', 0)
        //     ->select('id', 'company_name', 'company_emp_id')
        //     ->latest()
        //     ->get();


        $companies = Company::where('status', 1)
            ->when($companyIds, function ($query) use ($companyIds) {
                $query->whereIn('id', $companyIds);
            })
            ->where('is_deleted', 0)
            ->select('id', 'company_name', 'company_emp_id')
            ->latest()
            ->get();

        // 🔹 PSS EMPLOYEES
        $pssemployees = Employee::where('status', 1)
            ->where('is_deleted', 0)
            ->where('id', '!=', 1)
            ->where('job_form_referal', 1)
            ->select('full_name', 'id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'employees'    => $employees,
                'companies'    => $companies,
                'pssemployees' => $pssemployees
            ]
        ]);
    }

    public function companylist(Request $request)
    {
        $companyIds = $request->emp_company_id;
        $companyIds = $companyIds ? explode(',', $companyIds) : null;

        $companies = Company::where('status', 1)
            ->when($companyIds, function ($query) use ($companyIds) {
                $query->whereIn('id', $companyIds);
            })
            ->where('is_deleted', 0)
            ->select('id', 'company_name', 'company_emp_id')
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $companies
        ]);
    }
}
