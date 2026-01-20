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
        $validator = Validator::make($request->all(), [

            'gst_number' => [
                Rule::unique('companies', 'gst_number')
                    ->where(fn($q) => $q->where('is_deleted', 0)),
            ],

            // 'website_url' => [
            //     'required',
            //     Rule::unique('companies', 'website_url')
            //         ->where(fn($q) => $q->where('is_deleted', 0)),
            // ],

            'support_email' => [
                'required',
                'email',
                Rule::unique('companies', 'support_email')
                    ->where(fn($q) => $q->where('is_deleted', 0)),
            ],

            'billing_email' => [
                'required',
                'email',
                Rule::unique('companies', 'billing_email')
                    ->where(fn($q) => $q->where('is_deleted', 0)),
            ],
        ], [
            'gst_number.unique'    => 'GST number already exists.',
            'support_email.unique' => 'Support email already exists.',
            'billing_email.unique' => 'Billing email already exists.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }


        $company = Company::create($request->all());

        // Notes
        // if ($request->filled('notes')) {
        //     NoteAttachment::create([
        //         'parent_id'   => $company->id,
        //         'parent_type' => 'company',
        //         'notes'       => $request->notes,
        //     ]);
        // }

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

        //shifts

        // if (is_array($request->shiftdetails)) {
        //     foreach ($request->shiftdetails as $shift) {
        //         CompanyShifts::create([
        //             'parent_id'   => $company->id,
        //             'shift_name'        => $shift['shift_name'],
        //             'start_time'        => $shift['start_time'] ?? null,
        //             'end_time' => $shift['end_time'],
        //             'created_by' => $request->created_by,
        //             'company_shift_id' => 'PSSCC'.$company->id.'001'
        //         ]);
        //     }
        // }

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
                    'start_time'        => $shift['start_time'] ?? null,
                    'end_time'          => $shift['end_time'],
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
            ->with(['contacts', 'shifts'])
            ->latest()
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

        $validator = Validator::make($request->all(), [

            'gst_number' => [
                Rule::unique('companies', 'gst_number')
                    ->ignore($id)
                    ->where(fn($q) => $q->where('is_deleted', 0)),
            ],

            // 'website_url' => [
            //     'required',
            //     Rule::unique('companies', 'website_url')
            //         ->ignore($id)
            //         ->where(fn($q) => $q->where('is_deleted', 0)),
            // ],

            'support_email' => [
                'required',
                'email',
                Rule::unique('companies', 'support_email')
                    ->ignore($id)
                    ->where(fn($q) => $q->where('is_deleted', 0)),
            ],

            'billing_email' => [
                'required',
                'email',
                Rule::unique('companies', 'billing_email')
                    ->ignore($id)
                    ->where(fn($q) => $q->where('is_deleted', 0)),
            ],

        ], [
            'gst_number.unique'    => 'GST number already exists.',
            // 'website_url.unique'   => 'Website URL already exists.',
            'support_email.unique' => 'Support email already exists.',
            'billing_email.unique' => 'Billing email already exists.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $company = Company::findOrFail($id);

        $request->validate([
            'gst_number'    => 'unique:companies,gst_number,' . $id,
            // 'website_url'   => 'unique:companies,website_url,' . $id,
            'support_email' => 'unique:companies,support_email,' . $id,
            'billing_email' => 'unique:companies,billing_email,' . $id,
        ]);

        $company->update($request->all());

        // // Notes
        // if ($request->filled('notes')) {
        //     NoteAttachment::create([
        //         'parent_id'   => $id,
        //         'parent_type' => 'company',
        //         'notes'       => $request->notes,
        //     ]);
        // }

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


        //shifts
        // CompanyShifts::where('parent_id', $id)
        //     ->delete();
        // if (is_array($request->shiftdetails)) {
        //     foreach ($request->shiftdetails as $shift) {
        //         CompanyShifts::create([
        //             'parent_id'   => $company->id,
        //             'shift_name'        => $shift['shift_name'],
        //             'start_time'        => $shift['start_time'] ?? null,
        //             'end_time' => $shift['end_time'],
        //             'created_by' => $request->created_by
        //         ]);
        //     }
        // }

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
                    'start_time'       => $shift['start_time'] ?? null,
                    'end_time'         => $shift['end_time'],
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
