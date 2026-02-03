<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Company;
use App\Models\ContactDetail;
use App\Models\CompanyShifts;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;


class CompanyController extends Controller
{
    public function store(Request $request)
    {

        $company = Company::create($request->all());

        // Contacts
        if (is_array($request->contact_details)) {
            foreach ($request->contact_details as $contact) {
                ContactDetail::create([
                    'parent_id'   => $company->id,
                    'parent_type' => 'company',
                    'name'        => $contact['name'],
                    'role'        => $contact['role'] ?? null,
                    'phone_number' => $contact['phone_number'],
                ]);
            }
        }

        if (is_array($request->shiftdetails)) {

            foreach ($request->shiftdetails as $shift) {

                // 🔹 Get last shift for this company
                $lastShift = CompanyShifts::where('parent_id', $company->id)
                    ->orderBy('id', 'desc')
                    ->first();

                if ($lastShift && $lastShift->company_shift_id) {
                    // Extract last 3 digits
                    $lastNumber = (int) substr($lastShift->company_shift_id, -3);
                    $nextNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
                } else {
                    // First shift for this company
                    $nextNumber = '001';
                }

                $companyShiftId = 'PSSCC_' . $company->id . '_' . $nextNumber;

                CompanyShifts::create([
                    'parent_id'         => $company->id,
                    'shift_name'        => $shift['shift_name'],
                    'start_time' => isset($shift['start_time']) ? (string) $shift['start_time'] : null,
                    'end_time'   => isset($shift['end_time']) ? (string) $shift['end_time'] : null,
                    'created_by'        => $request->created_by,
                    'company_shift_id'  => $companyShiftId,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Company created successfully'
        ]);
    }

    public function index()
    {
        $companies = Company::where('is_deleted', 0)
            // ->with(['contacts', 'shifts'])
            ->latest()
            ->select('id', 'company_name', 'website_url', 'support_email', 'billing_email', 'gst_number', 'status', 'created_at', 'target', 'phone_number')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $companies
        ]);
    }

    public function show($id)
    {
        $company = Company::where('id', $id)
            ->where('is_deleted', 0)
            ->with(['contacts', 'shifts'])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $company
        ]);
    }

    public function update(Request $request, $id)
    {

        $company = Company::findOrFail($id);

        $company->update($request->all());


        // Replace contacts
        ContactDetail::where('parent_id', $id)
            ->where('parent_type', 'company')
            ->delete();

        if (is_array($request->contact_details)) {
            foreach ($request->contact_details as $contact) {
                ContactDetail::create([
                    'parent_id'   => $id,
                    'parent_type' => 'company',
                    'name'        => $contact['name'],
                    'role'        => $contact['role'] ?? null,
                    'phone_number' => $contact['phone_number'],
                ]);
            }
        }

        // 🔹 Delete old shifts
        CompanyShifts::where('parent_id', $id)->delete();

        // 🔹 Re-create shifts with new sequential company_shift_id
        if (is_array($request->shiftdetails)) {

            $counter = 1; // start from 001 for this company

            foreach ($request->shiftdetails as $shift) {

                $companyShiftId = 'PSSCC_' . $company->id . '_' . str_pad($counter, 3, '0', STR_PAD_LEFT);

                CompanyShifts::create([
                    'parent_id'        => $company->id,
                    'shift_name'       => $shift['shift_name'],
                    'start_time' => isset($shift['start_time']) ? (string) $shift['start_time'] : null,
                    'end_time'   => isset($shift['end_time']) ? (string) $shift['end_time'] : null,
                    'created_by'       => $request->created_by,
                    'company_shift_id' => $companyShiftId,
                ]);

                $counter++;
            }
        }


        return response()->json([
            'success' => true,
            'message' => 'Company updated successfully'
        ]);
    }

    public function destroy($id)
    {
        Company::where('id', $id)->update(['is_deleted' => 1]);

        return response()->json([
            'success' => true,
            'message' => 'Company deleted successfully'
        ]);
    }


    public function companylist()
    {
        $companies = Company::where('status', 1)->where('is_deleted', 0)
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $companies
        ]);
    }
}
