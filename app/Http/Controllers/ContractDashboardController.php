<?php

namespace App\Http\Controllers;

use App\Models\ContractCanEmp;
use App\Models\ContractEmployee;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContractDashboardController extends Controller
{
    public function index(Request $request)
    {
        $start_date = $request->start_date;
        $end_date = $request->end_date;

        // 1. Interview Candidate (Count by Company)
        $interviews = ContractEmployee::select(
            'company_id',
            DB::raw('COUNT(id) as count')
        )
            ->with('company:id,company_name')
            ->where('is_deleted', 0)
            ->when($start_date && $end_date, function ($q) use ($start_date, $end_date) {
                $q->whereBetween('created_at', [
                    Carbon::parse($start_date)->startOfDay(),
                    Carbon::parse($end_date)->endOfDay(),
                ]);
            })
            ->groupBy('company_id')
            ->get()
            ->map(function ($item) {
                return [
                    'company_id' => $item->company_id,
                    'company_name' => optional($item->company)->company_name ?? 'Unknown',
                    'count' => $item->count,
                ];
            });

        // 2. No of Candidate Joining (Count by Company and Reference Name)
        $joining = ContractCanEmp::select(
            'contract_can_emps.company_id',
            'employees.id as reference_id',
            'employees.full_name as reference_name',
            DB::raw('COUNT(contract_can_emps.id) as count'),
            'companies.company_name'
        )
            ->leftJoin('employees', 'contract_can_emps.created_by', '=', 'employees.id')
            ->join('companies', 'contract_can_emps.company_id', '=', 'companies.id')
            ->where('contract_can_emps.is_deleted', 0)
            ->when($start_date && $end_date, function ($q) use ($start_date, $end_date) {
                $q->whereBetween('contract_can_emps.created_at', [
                    Carbon::parse($start_date)->startOfDay(),
                    Carbon::parse($end_date)->endOfDay(),
                ]);
            })
            // ->groupBy('contract_can_emps.company_id', 'employees.full_name', 'companies.company_name')
            ->groupBy('contract_can_emps.company_id', 'employees.id', 'employees.full_name', 'companies.company_name')
            ->get()
            ->map(function ($item) {
                return [
                    'company_id' => $item->company_id, 
                    'reference_id' => $item->reference_id, 
                    'company_name' => $item->company_name,
                    'reference_name' => $item->reference_name ?? 'Self/Direct',
                    'count' => $item->count,
                ];
            });

        // 3. No of Candidate Relieved (Count by Company where status = 0)
        $relieved = ContractCanEmp::select(
            'company_id',
            DB::raw('COUNT(id) as count')
        )
            ->with('company:id,company_name')
            ->where('status', 0)
            ->when($start_date && $end_date, function ($q) use ($start_date, $end_date) {
                $q->whereBetween('updated_at', [
                    Carbon::parse($start_date)->startOfDay(),
                    Carbon::parse($end_date)->endOfDay(),
                ]);
            })
            ->groupBy('company_id')
            ->get()
            ->map(function ($item) {
                return [
                    'company_id' => $item->company_id,
                    'company_name' => optional($item->company)->company_name ?? 'Unknown',
                    'count' => $item->count,
                ];
            });

        return response()->json([
            'success' => true,
            'message' => 'Contract dashboard data fetched successfully',
            'data' => [
                'interviews' => $interviews,
                'joining' => $joining,
                'relieved' => $relieved,
            ],
        ]);
    }
}
