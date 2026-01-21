<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ContractCanEmp;
use App\Models\NoteAttachment;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Models\Company;
use App\Models\Employee;
use Illuminate\Support\Facades\Hash;
use App\Models\ContractEmployeeDocument;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ContractEmployeeController extends Controller
{
    public function store(Request $request)
    {
        // $request->validate([
        //     // 'phone_number' => 'required|unique:contract_employees,phone_number',
        //     'aadhar_number' => 'required|digits:12|unique:contract_can_emps,aadhar_number',
        //     'employee_id' => 'unique:contract_can_emps,employee_id'
        // ]);
        $validator = Validator::make(
            $request->all(),
            [
                'aadhar_number' => [
                    'required',
                    'digits:12',
                    Rule::unique('contract_can_emps', 'aadhar_number')
                        ->where(function ($q) use ($request) {
                            return $q->where('is_deleted', 0);
                        }),
                ],

                'employee_id' => [
                    'required',
                    Rule::unique('contract_can_emps', 'employee_id')
                        ->where(function ($q) use ($request) {
                            return $q->where('is_deleted', 0);
                        }),
                ],
            ],
            [
                'aadhar_number.required' => 'Aadhar number is required.',
                'aadhar_number.digits'   => 'Aadhar number must be exactly 12 digits.',
                'aadhar_number.unique'   => 'This Aadhar number is already registered for this company.',

                'employee_id.required'   => 'Employee ID is required.',
                'employee_id.unique'     => 'This Employee ID already exists for this company.',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors'  => $validator->errors()
            ], 422);
        }

        $data = $request->all();

        if ($request->reference === 'other') {
            $data['other_reference'] = $request->other_reference;
        }

        /* ============================
       PROFILE PHOTO UPLOAD
        ============================ */
        $photoDir = public_path('uploads/contract_employee/profile');
        if (!file_exists($photoDir)) {
            mkdir($photoDir, 0755, true);
        }

        if ($request->hasFile('profile_picture')) {
            $photo = $request->file('profile_picture');
            $employeeId = $data['employee_id'] ?? 'emp'; // fallback safety
            $extension  = $photo->getClientOriginalExtension();
            $photoName = $employeeId . '_' . rand(10000, 99999) . '_' . now()->format('YmdHis') . '.' . $extension;
            $photo->move($photoDir, $photoName);

            $data['profile_picture'] = 'uploads/contract_employee/profile/' . $photoName;
        }

        // Create employee
        $emp = ContractCanEmp::create($data);

        /* ============================
       MULTIPLE DOCUMENT UPLOAD
        ============================ */
        // if ($request->hasFile('documents')) {

        //     $docDir = public_path('uploads/contract_employee/documents');
        //     if (!file_exists($docDir)) {
        //         mkdir($docDir, 0755, true);
        //     }

        //     foreach ($request->file('documents') as $doc) {

        //         $originalName = $doc->getClientOriginalName();
        //         $employeeId = $data['employee_id'] ?? 'emp';
        //         $docName = $employeeId . '_' . now()->format('YmdHis') . '_' . rand(10000, 99999) . '_' .
        //             Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) .
        //             '.' . $doc->getClientOriginalExtension();

        //         $doc->move($docDir, $docName);

        //         ContractEmployeeDocument::create([
        //             'employee_id'   => $emp->id,
        //             'original_name' => $originalName,
        //             'document_path' => 'uploads/contract_employee/documents/' . $docName,
        //         ]);
        //     }
        // }

        if ($request->hasFile('documents')) {

            $docDir = public_path('uploads/contract_employee/documents');
            if (!file_exists($docDir)) {
                mkdir($docDir, 0755, true);
            }

            foreach ($request->file('documents') as $doc) {

                $originalName = $doc->getClientOriginalName();

                $docName = now()->format('YmdHis') . '_' . rand(10000, 99999) . '_' .
                    Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) .
                    '.' . $doc->getClientOriginalExtension();

                $doc->move($docDir, $docName);

                ContractEmployeeDocument::create([
                    'employee_id'   => $emp->id,
                    'original_name' => $originalName,
                    'document_path' => 'uploads/contract_employee/documents/' . $docName,
                ]);
            }
        }

        if (is_array($request->notes_details)) {
            foreach ($request->notes_details as $note) {
                NoteAttachment::create([
                    'parent_id' => $emp->id,
                    'parent_type' => 'contract_emp',
                    'notes' => $note['notes'],
                    'note_status' => $note['note_status'] ?? 1
                ]);
            }
        }

        return response()->json(['success' => true, 'message' => 'Contract Employee created successfully']);
    }

    public function index(Request $request)
    {
        // EMPLOYEES (Contract)
        $employees = ContractCanEmp::where('is_deleted', 0)
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
            })
            ->with(['notes', 'company'])
            ->orderByDesc('id')
            ->get();

        // COMPANIES



        $companies = Company::where('status', '1')->where('is_deleted', 0)
            ->select('id', 'company_name', 'company_emp_id')
            ->latest()
            ->get();

        $pssemployees = Employee::where('status', '1')->where('is_deleted', 0)
            ->where('id', '!=', 1)
            ->where('job_form_referal', 1)
            ->select('full_name', 'id')
            ->get();

        return response()->json(['success' => true, 'data' => [
            'employees'         => $employees,
            'companies' => $companies,
            'pssemployees' => $pssemployees,
            // 'educations' => $educations
        ]]);
    }

    public function show($id)
    {
        $emp = ContractCanEmp::where('id', $id)
            ->where('is_deleted', 0)
            ->firstOrFail();

        $notes = collect();

        /**
         * ✅ INTERVIEW STATUS NOTES
         */
        if (in_array($emp->interview_status, ['rejected', 'hold', 'waiting'])) {
            $interviewNote = NoteAttachment::where('parent_id', $emp->id)
                ->where('parent_type', 'contract_emp')
                ->whereIn('notes_status', ['rejected', 'hold', 'waiting'])
                ->latest('id') // OR created_at
                ->first();

            if ($interviewNote) {
                $notes->push($interviewNote);
            }
        }

        /**
         * ✅ JOINING STATUS NOTES
         */
        if ($emp->joining_status === 'not_joined') {
            $joiningNote = NoteAttachment::where('parent_id', $emp->id)
                ->where('parent_type', 'contract_emp')
                ->where('notes_status', 'not_joined')
                ->latest('id')
                ->first();

            if ($joiningNote) {
                $notes->push($joiningNote);
            }
        }

        // Attach filtered notes manually
        $emp->setRelation('notes', $notes);

        return response()->json([
            'success' => true,
            'data'    => $emp
        ]);
    }

    public function update(Request $request, $id)
    {
        $emp = ContractCanEmp::where('id', $id)->where('is_deleted', 0)->firstOrFail();

        // $request->validate([
        //     // 'phone_number' => 'unique:contract_employees,phone_number,' . $id,
        //     'aadhar_number' => 'digits:12|unique:contract_can_emps,aadhar_number,' . $id,
        // ]);
        $validator = Validator::make(
            $request->all(),
            [
                'aadhar_number' => [
                    'required',
                    'digits:12',
                    Rule::unique('contract_can_emps', 'aadhar_number')
                        ->ignore($id)
                        ->where(function ($q) use ($request) {
                            return $q->where('is_deleted', 0);
                        }),
                ],

                'employee_id' => [
                    'required',
                    Rule::unique('contract_can_emps', 'employee_id')
                        ->ignore($id)
                        ->where(function ($q) use ($request) {
                            return $q->where('is_deleted', 0);
                        }),
                ],
            ],
            [
                'aadhar_number.unique' => 'This Aadhaar number is already registered for this company.',
                'employee_id.unique'   => 'This Employee ID already exists for this company.',
            ]
        );


        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors'  => $validator->errors()
            ], 422);
        }

        $data = $request->all();
        if ($request->reference === 'other') {
            $data['other_reference'] = $request->other_reference;
        }

        /* ============================
       UPDATE PROFILE PHOTO
     ============================ */
        $photoDir = public_path('uploads/contract_employee/profile');
        if (!file_exists($photoDir)) {
            mkdir($photoDir, 0755, true);
        }

        if ($request->hasFile('profile_picture')) {

            // ❌ delete old photo
            if (!empty($emp->profile_picture) && file_exists(public_path($emp->profile_picture))) {
                unlink(public_path($emp->profile_picture));
            }

            $photo = $request->file('profile_picture');
            $employeeId = $data['employee_id'] ?? 'emp'; // fallback safety
            $extension  = $photo->getClientOriginalExtension();
            $photoName = $employeeId . '_' . rand(10000, 99999) . '_' . now()->format('YmdHis') . '.' . $extension;

            $photo->move($photoDir, $photoName);

            $data['profile_picture'] = 'uploads/contract_employee/profile/' . $photoName;
        }


        $emp->update($request->all());

        /* ============================
       ADD NEW DOCUMENTS
        ============================ */
        // if ($request->hasFile('documents')) {

        //     $docDir = public_path('uploads/contract_employee/documents');
        //     if (!file_exists($docDir)) {
        //         mkdir($docDir, 0755, true);
        //     }

        //     foreach ($request->file('documents') as $doc) {

        //         $originalName = $doc->getClientOriginalName();
        //         $employeeId = $data['employee_id'] ?? 'emp';
        //         $docName = $employeeId . '_' . now()->format('YmdHis') . '_' . rand(10000, 99999) . '_' .
        //             Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) .
        //             '.' . $doc->getClientOriginalExtension();

        //         $doc->move($docDir, $docName);

        //         ContractEmployeeDocument::create([
        //             'employee_id'   => $emp->id,
        //             'original_name' => $originalName,
        //             'document_path' => 'uploads/contract_employee/documents/' . $docName,
        //         ]);
        //     }
        // }

        /* ============================
           DOCUMENT SYNC (IMPORTANT)
        ============================ */

        $incoming = $request->input('documents', []);
        $existingIds = [];
        $newFiles = [];

        // Separate IDs & files
        foreach ($incoming as $item) {
            if (is_numeric($item)) {
                $existingIds[] = (int)$item;
            }
        }

        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $file) {
                if ($file instanceof \Illuminate\Http\UploadedFile) {
                    $newFiles[] = $file;
                }
            }
        }

        /* 🔥 DELETE REMOVED DOCUMENTS */
        $toDelete = ContractEmployeeDocument::where('employee_id', $emp->id)
            ->whereNotIn('id', $existingIds)
            ->get();

        foreach ($toDelete as $doc) {
            if (!empty($doc->document_path) && file_exists(public_path($doc->document_path))) {
                unlink(public_path($doc->document_path));
            }
            $doc->delete();
        }

        /* ➕ ADD NEW DOCUMENT FILES */
        if (!empty($newFiles)) {

            $docDir = public_path('uploads/contract_employee/documents');
            if (!file_exists($docDir)) {
                mkdir($docDir, 0755, true);
            }

            foreach ($newFiles as $file) {

                $originalName = $file->getClientOriginalName();

                $docName = now()->format('YmdHis') . '_' . rand(10000, 99999) . '_' .
                    Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) . '.' .
                    $file->getClientOriginalExtension();

                $file->move($docDir, $docName);

                ContractEmployeeDocument::create([
                    'employee_id'   => $emp->id,
                    'original_name' => $originalName,
                    'document_path' => 'uploads/contract_employee/documents/' . $docName,
                ]);
            }
        }

        if (is_array($request->notes_details)) {
            foreach ($request->notes_details as $note) {
                // if (!empty($note['_id'])) {
                //     NoteAttachment::find($note['_id'])->update([
                //         'notes' => $note['notes'],
                //         'note_status' => $note['note_status'] ?? 1
                //     ]);
                // } else {
                NoteAttachment::create([
                    'parent_id' => $id,
                    'parent_type' => 'contract_emp',
                    'notes' => $note['notes'],
                    'note_status' => $note['note_status'] ?? 1
                ]);
                // }
            }
        }

        return response()->json(['success' => true, 'message' => 'Updated successfully', 'data' => $emp]);
    }

    public function destroy($id)
    {
        $emp = ContractCanEmp::where('id', $id)->where('is_deleted', 0)->firstOrFail();
        $emp->update(['is_deleted' => 1]);

        return response()->json(['success' => true, 'message' => 'Deleted successfully']);
    }

    public function import(Request $request)
    {

        // dd($request->all());
        // 1️⃣ Validate CSV file only
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,txt'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid file format. Only CSV allowed.',
                'errors'  => $validator->errors()
            ], 422);
        }

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');

        $header = fgetcsv($handle); // CSV header
        $inserted = 0;
        $skipped  = 0;
        $errors   = [];

        while (($row = fgetcsv($handle)) !== false) {

            $data = array_combine($header, $row);

            $exists = ContractCanEmp::where('aadhar_number', $data['aadhar_number'])
                ->where('is_deleted', 0)
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }


            //    dd($aadhar);
            /* 🔹 INSERT */

            $date_of_birth = $this->parseDate($data['date_of_birth'] ?? null);
            $joining_date  = $this->parseDate($data['joining_date'] ?? null);

            // ✅ Default: take employee_id from CSV if exists
            $employee_id = $data['employee_id'] ?? null;

            // ✅ Auto-generate ONLY if CSV employee_id is empty
            if (empty($employee_id) && $joining_date && $request->company_id) {
                $employee_id = $this->generateEmployeeId(
                    $request->company_id,
                    $joining_date
                );
            }


            ContractCanEmp::create([
                'employee_id'      => $employee_id,
                'name'             => $data['name'] ?? null,
                'date_of_birth'    => $date_of_birth,
                'father_name'      => $data['father_name'] ?? null,
                'joining_date'     => $joining_date,
                'aadhar_number'    => $data['aadhar_number'] ?? null,
                'gender'           => $data['gender'] ?? null,
                'address'          => $data['address'] ?? null,
                'phone_number'     => $data['phone_number'] ?? null,
                'acc_no'           => $data['acc_no'] ?? null,
                'account_number'   => $data['account_number'] ?? null,
                'ifsc_code'        => $data['ifsc_code'] ?? null,
                'uan_number'       => $data['uan_number'] ?? null,
                'esic'             => $data['esic'] ?? null,

                'company_id'       => $request->company_id ?? null,
                'status'           => 1,
                'is_deleted'       => 0,
                'created_by'       => $request->created_by ?? null,
                'role_id'       => $request->role_id ?? null,
            ]);

            $inserted++;
        }

        fclose($handle);

        return response()->json([
            'success'   => true,
            'message'   => 'CSV import completed',
            'inserted'  => $inserted,
            'skipped'   => $skipped,
            'errors'    => $errors // optional
        ], 200);
    }

    private function generateEmployeeId($company_id, $joining_date)
    {
        $company = Company::where('id', $company_id)
            ->where('company_emp_id', 'automatic')
            ->select('company_emp_id', 'prefix')
            ->first();

        // ❌ If company not automatic → return null
        if (!$company) {
            return null;
        }

        // Date format: YYYYMMDD
        $dateOfJoining = Carbon::parse($joining_date)->format('Ym');

        // Prefix example: PSS20250112
        $prefix = $company->prefix;

        // Get last employee for same prefix
        $lastEmployee = ContractCanEmp::where('employee_id', 'like', $prefix . '%')
            ->orderBy('employee_id', 'desc')
            ->first();

        if ($lastEmployee) {
            $lastNumber = (int) substr($lastEmployee->employee_id, -3);
            $nextNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = '001';
        }

        return $prefix . $dateOfJoining . $nextNumber;
    }


    private function parseDate($date)
    {
        if (empty($date)) {
            return null;
        }

        $formats = [
            'd-m-Y',
            'd/m/Y',
            'Y-m-d',
            'Y/m/d',
            'd.m.Y',
        ];

        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, trim($date))->format('Y-m-d');
            } catch (\Exception $e) {
                // try next
            }
        }

        // Final fallback
        try {
            return Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
    public function getEmpidGenearate(Request $request)
    {
        $company_id = $request->company_id;
        $dateOfJoining = Carbon::parse($request->date_of_joining)->format('Ym');

        $company =  Company::where('id', $company_id)->where('company_emp_id', 'automatic')->select('company_emp_id', 'prefix')->first();
        $prefix = $company->prefix;

        /**
         * Get last employee_id for same date
         * Example: pss20250112005
         */
        $lastEmployee = ContractCanEmp::where('employee_id', 'like', $prefix . '%')
            ->orderBy('employee_id', 'desc')
            ->first();

        if ($lastEmployee) {
            // Extract last 3 digits
            $lastNumber = (int) substr($lastEmployee->employee_id, -3);
            $nextNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        } else {
            // First employee for this date
            $nextNumber = '001';
        }

        $newEmployeeId = $prefix . $dateOfJoining . $nextNumber;

        return response()->json([
            'success' => true,
            'employee_id' => $newEmployeeId
        ]);
    }
}
