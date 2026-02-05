<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LeadManagement;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LeadDashboardController extends Controller
{
    public function categoryWiseCount(Request $request)
    {
        $from = $request->filled('from_date')
            ? Carbon::parse($request->from_date)->startOfDay()
            : null;

        $to = $request->filled('to_date')
            ? Carbon::parse($request->to_date)->endOfDay()
            : null;

        $query = LeadManagement::where('is_deleted', '0');

        if ($from && $to) {
            $query->whereBetween('created_at', [$from, $to]);
        }

        $rows = $query
            ->select('lead_category_id', DB::raw('COUNT(*) as total'))
            ->groupBy('lead_category_id')
            ->with('category:id,name')
            ->get();

        $data = $rows->map(function ($row) {
            return [
                'category' => optional($row->category)->name ?? 'Unknown',
                'count' => (int) $row->total,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'total_leads' => (int) $rows->sum('total'),
            'data' => $data,
        ]);
    }

    public function statusWiseCount(Request $request)
    {
        $request->validate([
            'lead_category_id' => ['required', 'integer'],
            'from_date' => ['nullable', 'date_format:Y-m-d'],
            'to_date'   => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from_date'],
        ]);

        $from = $request->filled('from_date')
            ? Carbon::createFromFormat('Y-m-d', $request->from_date)->startOfDay()
            : null;

        $to = $request->filled('to_date')
            ? Carbon::createFromFormat('Y-m-d', $request->to_date)->endOfDay()
            : null;

        $query = LeadManagement::where('is_deleted', '0')
            ->where('lead_category_id', $request->lead_category_id);

        if ($from && $to) {
            $query->whereBetween('created_at', [$from, $to]);
        } elseif ($from) {
            $query->where('created_at', '>=', $from);
        } elseif ($to) {
            $query->where('created_at', '<=', $to);
        }

        $rows = $query
            ->select('lead_status', DB::raw('COUNT(*) as total'))
            ->groupBy('lead_status')
            ->get();

        $knownStatuses = [
            'open',
            'Interested',
            'Interested / scheduled',
            'Joined',
        ];

        $counts = $rows->mapWithKeys(function ($row) {
            return [$row->lead_status => (int) $row->total];
        });

        $data = collect($knownStatuses)->map(function ($status) use ($counts) {
            return [
                'status' => $status,
                'count' => $counts->get($status, 0),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $data,
            'total' => (int) $rows->sum('total'),
        ]);
    }
}
