<?php

namespace App\Http\Controllers;

use App\Models\BoardingPoint;
use Illuminate\Http\Request;
use App\Models\ContractCanEmp;
use App\Models\NoteAttachment;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Eductions;
use App\Models\EmployeeRejoing;
use App\Models\ContactDetail;
use Illuminate\Support\Facades\Hash;
use App\Models\ContractEmployeeDocument;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Models\EmpRejoinLogs;

class ContractEmployeeController extends Controller
{
    public function store(Request $request)
    {
        // $validator = Validator::make(
        //     $request->all(),
        //     [
        //         'aadhar_number' => [
        //             Rule::unique('contract_can_emps', 'aadhar_number')
        //                 ->where(function ($q) use ($request) {
        //                     return $q->where('is_deleted', 0);
        //                 }),
        //         ]
        //     ],
        //     [
        //         'aadhar_number.unique'   => 'This Aadhar number is already registered.',
        //     ]
        // );

        // if ($validator->fails()) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Validation errors',
        //         'errors'  => $validator->errors()
        //     ], 422);
        // }

        $existingEmp = ContractCanEmp::where('aadhar_number', $request->aadhar_number)
            ->where('is_deleted', 0)
            ->first();

        if ($existingEmp) {
            return response()->json([
                'success' => false,
                'message' => 'This Aadhar number is already registered.',
                'existing_id' => $existingEmp->id,
            ], 409);
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

        if (is_array($request->contact_details)) {
            foreach ($request->contact_details as $contact) {
                ContactDetail::create([
                    'parent_id'   => $emp->id,
                    'parent_type' => 'contract_emp',
                    'name'        => $contact['name'],
                    'relationship'        => $contact['relationship'] ?? null,
                    'phone_number' => $contact['phone_number'],
                ]);
            }
        }

        //emp rejoing details
        $rejoing_data = [
            'parent_id' => $emp->id,
            'company_id' => $request->company_id,
            'boarding_point_id' => $request->boarding_point_id,
            'address' => $request->address,
            'joining_date' => $request->joining_date,
            'employee_id' => $request->employee_id,
            'rejoining_note' => $request->rejoining_note ?? null,
            'rejoin_status' => $request->rejoin_status ?? 0,
            'created_by' => $request->created_by
        ];

        $rejoingdetails = EmpRejoinLogs::create($rejoing_data);


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
            ->select('id', 'company_id', 'name', 'employee_id', 'phone_number', 'aadhar_number', 'joining_date', 'status', 'created_at','gender', 'date_of_birth')
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


        $educations = Eductions::where('status', '1')->where('is_deleted', 0)
            ->select('id', 'eduction_name')
            ->latest()
            ->get();

        $boardingpoints = BoardingPoint::where('status', '1')->where('is_deleted', 0)
            ->select('id', 'point_name')
            ->latest()
            ->get();

        return response()->json(['success' => true, 'data' => [
            'employees'         => $employees,
            'companies' => $companies,
            'pssemployees' => $pssemployees,
            'boardingpoints' => $boardingpoints,
            'educations' => $educations
        ]]);
    }

    public function show($id)
    {
        $emp = ContractCanEmp::with(['documents', 'boardingPoint', 'rejoingstatus', 'contacts'])->where('id', $id)
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

        $existingAadhar = ContractCanEmp::where('aadhar_number', $request->aadhar_number)
            ->where('is_deleted', 0)
            ->where('id', '!=', $id)
            ->first();

        if ($existingAadhar) {
            return response()->json([
                'success' => false,
                'message' => 'This Aadhaar number is already registered.',
                'existing_id' => $existingAadhar->id,
                'field' => 'aadhar_number'
            ], 409);
        }

        // $request->validate([
        //     // 'phone_number' => 'unique:contract_employees,phone_number,' . $id,
        //     'aadhar_number' => 'digits:12|unique:contract_can_emps,aadhar_number,' . $id,
        // ]);
        // $validator = Validator::make(
        //     $request->all(),
        //     [
        //         'aadhar_number' => [
        //             'required',
        //             'digits:12',
        //             Rule::unique('contract_can_emps', 'aadhar_number')
        //                 ->ignore($id)
        //                 ->where(function ($q) use ($request) {
        //                     return $q->where('is_deleted', 0);
        //                 }),
        //         ],

        //         'employee_id' => [
        //             'required',
        //             Rule::unique('contract_can_emps', 'employee_id')
        //                 ->ignore($id)
        //                 ->where(function ($q) use ($request) {
        //                     return $q->where('is_deleted', 0);
        //                 }),
        //         ],
        //     ],
        //     [
        //         'aadhar_number.unique' => 'This Aadhaar number is already registered for this company.',
        //         'employee_id.unique'   => 'This Employee ID already exists for this company.',
        //     ]
        // );


        // if ($validator->fails()) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Validation errors',
        //         'errors'  => $validator->errors()
        //     ], 422);
        // }

        $data = $request->all();
        if ($request->reference === 'other') {
            $data['other_reference'] = $request->other_reference;
        }

        /* ============================
       UPDATE PROFILE PHOTO
     ============================ */
        // $photoDir = public_path('uploads/contract_employee/profile');
        // if (!file_exists($photoDir)) {
        //     mkdir($photoDir, 0755, true);
        // }

        // if ($request->hasFile('profile_picture')) {

        //     // ❌ delete old photo
        //     if (!empty($emp->profile_picture) && file_exists(public_path($emp->profile_picture))) {
        //         unlink(public_path($emp->profile_picture));
        //     }

        //     $photo = $request->file('profile_picture');
        //     $employeeId = $data['employee_id'] ?? 'emp'; // fallback safety
        //     $extension  = $photo->getClientOriginalExtension();
        //     $photoName = $employeeId . '_' . rand(10000, 99999) . '_' . now()->format('YmdHis') . '.' . $extension;

        //     $photo->move($photoDir, $photoName);

        //     $data['profile_picture'] = 'uploads/contract_employee/profile/' . $photoName;
        // }

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



        $emp->update($data);

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


        // Replace contacts
        ContactDetail::where('parent_id', $id)
            ->where('parent_type', 'contract_emp')
            ->delete();

        if (is_array($request->contact_details)) {
            foreach ($request->contact_details as $contact) {
                ContactDetail::create([
                    'parent_id'   => $emp->id,
                    'parent_type' => 'contract_emp',
                    'name'        => $contact['name'],
                    'relationship'        => $contact['relationship'] ?? null,
                    'phone_number' => $contact['phone_number'],
                ]);
            }
        }

        if ($request->rejoing_details) {
            $empRejoing = EmployeeRejoing::create([
                'parent_id' => $emp->id,
                'status' => $request->rejoin_status,
                'notes' => $request->rejoin_notes,
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Updated successfully', 'data' => $emp]);
    }

    public function destroy($id)
    {
        $emp = ContractCanEmp::where('id', $id)->where('is_deleted', 0)->firstOrFail();
        $emp->update(['is_deleted' => 1]);

        return response()->json(['success' => true, 'message' => 'Deleted successfully']);
    }

    // public function import(Request $request)
    // {

    //     // dd($request->all());
    //     // 1️⃣ Validate CSV file only
    //     $validator = Validator::make($request->all(), [
    //         'file' => 'required|file|mimes:csv,txt'
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Invalid file format. Only CSV allowed.',
    //             'errors'  => $validator->errors()
    //         ], 422);
    //     }

    //     $file = $request->file('file');
    //     $handle = fopen($file->getRealPath(), 'r');

    //     $header = fgetcsv($handle); // CSV header
    //     $inserted = 0;
    //     $skipped  = 0;
    //     $errors   = [];

    //     while (($row = fgetcsv($handle)) !== false) {

    //         $data = array_combine($header, $row);

    //         $exists = ContractCanEmp::where('aadhar_number', $data['aadhar_number'])
    //             ->where('is_deleted', 0)
    //             ->exists();

    //         if ($exists) {
    //             $skipped++;
    //             continue;
    //         }


    //         //    dd($aadhar);
    //         /* 🔹 INSERT */

    //         $date_of_birth = $this->parseDate($data['date_of_birth'] ?? null);
    //         $joining_date  = $this->parseDate($data['joining_date'] ?? null);

    //         // ✅ Default: take employee_id from CSV if exists
    //         $employee_id = $data['employee_id'] ?? null;

    //         // ✅ Auto-generate ONLY if CSV employee_id is empty
    //         if (empty($employee_id) && $joining_date && $request->company_id) {
    //             $employee_id = $this->generateEmployeeId(
    //                 $request->company_id,
    //                 $joining_date
    //             );
    //         }


    //         ContractCanEmp::create([
    //             'employee_id'      => $employee_id,
    //             'name'             => $data['name'] ?? null,
    //             'date_of_birth'    => $date_of_birth,
    //             'father_name'      => $data['father_name'] ?? null,
    //             'joining_date'     => $joining_date,
    //             'aadhar_number'    => $data['aadhar_number'] ?? null,
    //             'gender'           => $data['gender'] ?? null,
    //             'address'          => $data['address'] ?? null,
    //             'phone_number'     => $data['phone_number'] ?? null,
    //             'acc_no'           => $data['acc_no'] ?? null,
    //             'account_number'   => $data['account_number'] ?? null,
    //             'ifsc_code'        => $data['ifsc_code'] ?? null,
    //             'uan_number'       => $data['uan_number'] ?? null,
    //             'esic'             => $data['esic'] ?? null,

    //             'company_id'       => $request->company_id ?? null,
    //             'status'           => 1,
    //             'is_deleted'       => 0,
    //             'created_by'       => $request->created_by ?? null,
    //             'role_id'       => $request->role_id ?? null,
    //         ]);

    //         $inserted++;
    //     }

    //     fclose($handle);

    //     return response()->json([
    //         'success'   => true,
    //         'message'   => 'CSV import completed',
    //         'inserted'  => $inserted,
    //         'skipped'   => $skipped,
    //         'errors'    => $errors // optional
    //     ], 200);
    // }

    public function import(Request $request)
    {
        // 1️⃣ Validate CSV file
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

        $header = fgetcsv($handle);
        
        // 🔧 Clean header: remove BOM, line breaks, and trim whitespace
        $header = array_map(function($col) {
            $col = preg_replace('/[\x00-\x1F\x7F\xEF\xBB\xBF]/', '', $col); // Remove non-printable and BOM
            return trim(str_replace(["\r", "\n"], '', $col));
        }, $header);
        
        $inserted = 0;
        $skipped  = 0;
        $errors   = [];

        while (($row = fgetcsv($handle)) !== false) {

            // ⚠️ Skip broken rows
            if (count($header) !== count($row)) {
                $errors[] = ['row' => $row, 'error' => 'Column mismatch'];
                continue;
            }

            $data = array_combine($header, $row);

            // 🔒 Safe read Aadhar (Handle scientific notation)
            $aadhar = $this->csvValue($data, 'aadhar_number');
            if ($aadhar && strpos(strtolower($aadhar), 'e+') !== false) {
                $aadhar = number_format((float)$aadhar, 0, '', '');
            }
            
            if (empty($aadhar)) {
                $skipped++;
                continue;
            }

            // 🔁 Duplicate check only if aadhar exists
            if ($aadhar) {
                $exists = ContractCanEmp::where('aadhar_number', $aadhar)
                    ->where('is_deleted', 0)
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }
            }

            /* 🔹 Dates */
            $date_of_birth = $this->parseDate($this->csvValue($data, 'date_of_birth'));
            $joining_date  = $this->parseDate($this->csvValue($data, 'joining_date'));

            /* 🔹 Employee ID */
            $employee_id = $this->csvValue($data, 'employee_id');

            if (empty($employee_id) && $joining_date && $request->company_id) {
                $employee_id = $this->generateEmployeeId(
                    $request->company_id,
                    $joining_date
                );
            }
            
            /* 🔹 Insert */
            ContractCanEmp::create([
                'employee_id'    => $employee_id,
                'name'           => $this->csvValue($data, 'name'),
                'date_of_birth'  => $date_of_birth,
                'father_name'    => $this->csvValue($data, 'father_name'),
                'joining_date'   => $joining_date,
                'aadhar_number'  => $aadhar,
                'gender'         => $this->csvValue($data, 'gender'),
                'address'        => $this->csvValue($data, 'address'),
                'phone_number'   => $this->csvValue($data, 'phone_number'),
                'acc_no'         => $this->csvValue($data, 'acc_no'),
                'account_number' => $this->csvValue($data, 'account_number'),
                'ifsc_code'      => $this->csvValue($data, 'ifsc_code'),
                'uan_number'     => $this->csvValue($data, 'uan_number'),
                'esic'           => $this->csvValue($data, 'esic'),
                'emr_contact_number' => $this->csvValue($data, 'emr_contact_number'),
                'marital_status'     => $this->csvValue($data, 'marital_status'),
                'current_address'    => $this->csvValue($data, 'current_address'),
                'pan_number'     => $this->csvValue($data, 'pan_number'),
                'city'     => $this->csvValue($data, 'city'),
                'state'     => $this->csvValue($data, 'state'),
                'branch_name'     => $this->csvValue($data, 'branch_name'),
                'bank_name'     => $this->csvValue($data, 'bank_name'),

                'company_id'     => $request->company_id,
                'status'         => 1,
                'is_deleted'     => 0,
                'created_by'     => $request->created_by,
                'role_id'        => $request->role_id,
            ]);
          

            $inserted++;
        }

        fclose($handle);

        return response()->json([
            'success'  => true,
            'message'  => 'CSV import completed',
            'inserted' => $inserted,
            'skipped'  => $skipped,
            'errors'   => $errors
        ], 200);
    }


    // private function csvValue(array $data, string $key)
    // {
    //     return array_key_exists($key, $data) && $data[$key] !== ''
    //         ? $data[$key]
    //         : null;
    // }
    private function csvValue(array $data, string $key)
    {
        if (!array_key_exists($key, $data) || $data[$key] === '') {
            return null;
        }

        return mb_convert_encoding($data[$key], 'UTF-8', 'UTF-8,ISO-8859-1,Windows-1252');
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

    public function RejoinStatusList()
    {
        $rejoinstatus = EmpRejoinLogs::with(['employee','company','boardingPoint'])->get();

        return response()->json(['success' => true, 'data' => $rejoinstatus]);
    }

    public function RejoinStatusUpdate(Request $request, $id)
    {
        // //emp rejoing details
        // $rejoing_data = [
        //     'parent_id' => parent_id,
        //     'company_id' => company_id,
        //     'boarding_point_id' => boarding_point_id,
        //     'address' => $request->address,
        //     'joining_date' => $request->joining_date,
        //     'employee_id' => $request->employee_id,
        //     'rejoining_note' => $request->rejoining_note,
        //     'rejoin_status' => $request->rejoin_status,
        //     'created_by' => $request->created_by
        // ];


        $rejoinstatus = new EmpRejoinLogs();
        $data = $request->all();
        $rejoinstatus->create($data);

        return response()->json(['success' => true, 'message' => 'Rejoin Status Updated successfully']);
    }
}
