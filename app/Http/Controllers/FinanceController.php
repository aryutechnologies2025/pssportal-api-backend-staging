<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Finance;
use App\Models\PssCompany;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FinanceController extends Controller
{

  private function buildFinanceInvoiceFilename(Request $request, $uploadedFile): string
    {
        // Prefer session employee_id (set in EmployeeAuthController), fallback to request
        // $employeeId = session('employee_id') ?? $request->employee_id ?? 0;
        $employeeId = $request->employee_id ?? 0;
        $employeeId = (int) $employeeId;

        // Format: pssYYYYMM###_RANDOM_originalname.ext
        // Example: pss202405001_12345_bill.pdf
        $prefix = 'pss' . now()->format('Ym') . str_pad((string) $employeeId, 3, '0', STR_PAD_LEFT);
        $rand = rand(10000, 99999);
        $base = Str::slug(pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME));
        $ext = $uploadedFile->getClientOriginalExtension();

        return $prefix . '_' . $rand . '_' . $base . '.' . $ext;
    }

    public function index(Request $request)
    {
        // Get finance list
          $finances = Finance::with([
            'company:id,name',
            'branch:id,branch_name'
        ])
            ->where('is_deleted', 0)
            ->when($request->company_id, fn ($q) => $q->where('company_id', $request->company_id)
            )
            ->when($request->branch_id, fn ($q) => $q->where('branch_id', $request->branch_id)
            )
            ->latest()
            ->get();

        // Get companies for dropdown
        $companies = PssCompany::where('status', '1')
            ->where('is_deleted', '0')
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        // Get all branches for dropdown
        $branches = Branch::where('is_deleted', '0')
            ->where('status', '1')
            ->select('id', 'branch_name', 'company_id')
            ->orderBy('branch_name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $finances,
        ]);
    }

    public function store(Request $request)
    {
        $photoDir = public_path('uploads/finance');
        if (! file_exists($photoDir)) {
            mkdir($photoDir, 0755, true);
        }

        $bill = null;
        if ($request->hasFile('invoice_file')) {
            $photo = $request->file('invoice_file');
            // $photoName = now()->format('YmdHis').'_'.rand(10000, 99999).'_'.
            //     Str::slug(pathinfo($photo->getClientOriginalName(), PATHINFO_FILENAME)).
            //     '.'.$photo->getClientOriginalExtension();
            $photoName = $this->buildFinanceInvoiceFilename($request, $photo);

            $photo->move($photoDir, $photoName);
            $bill = 'uploads/finance/'.$photoName;
        }

        $finance = Finance::create([
            'company_id' => $request->company_id,
            'branch_id' => $request->branch_id,
            'date' => $request->date,
            'amount' => $request->amount,
            'description' => $request->description,
            'bill' => $bill,
            'status' => $request->status ?? 1,
            'is_deleted' => 0,
            'created_by' => $request->created_by,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'finance created successfully',
            'data' => $finance,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $finance = Finance::findOrFail($id);

        if ($finance->is_deleted == 1) {
            return response()->json([
                'success' => false,
                'message' => 'Record not found',
            ], 404);
        }

        $bill = $finance->bill;

        if ($request->hasFile('invoice_file')) {
            $photoDir = public_path('uploads/finance');
            if (! file_exists($photoDir)) {
                mkdir($photoDir, 0755, true);
            }

            $photo = $request->file('invoice_file');
            // $photoName = now()->format('YmdHis').'_'.rand(10000, 99999).'_'.
            //     Str::slug(pathinfo($photo->getClientOriginalName(), PATHINFO_FILENAME)).
            //     '.'.$photo->getClientOriginalExtension();
            $photoName = $this->buildFinanceInvoiceFilename($request, $photo);

            $photo->move($photoDir, $photoName);
            $bill = 'uploads/finance/'.$photoName;
        }

        $finance->update([
            'company_id' => $request->company_id,
            'branch_id' => $request->branch_id,
            'date' => $request->date,
            'amount' => $request->amount,
            'description' => $request->description,
            'status' => $request->status ?? $finance->status,
            'bill' => $bill,
            'updated_by' => $request->updated_by,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'finance updated successfully',
            'data' => $finance,
        ]);
    }
}
