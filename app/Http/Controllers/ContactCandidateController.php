<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ContractEmployee;
use App\Models\NoteAttachment;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Models\Company;
use App\Models\Employee;
use Illuminate\Support\Facades\Hash;
use App\Models\ContractCanEmp;
use App\Models\ContractCandidateDocument;
use App\Models\Eductions;
use App\Models\BoardingPoint;
use Illuminate\Support\Str;

class ContactCandidateController extends Controller
{

    public function store(Request $request)
    {

        /* ============================
       STEP 1: CHECK IN EMPLOYEE TABLE
       ============================ */
        $existingEmployee = ContractCanEmp::where('aadhar_number', $request->aadhar_number)
            ->where('is_deleted', 0)
            ->first();

        if ($existingEmployee) {
            return response()->json([
                'success'     => false,
                'message'     => 'This Aadhar number is already registered as Employee.',
                'existing_id' => $existingEmployee->id,
                'type'        => 'employee',
            ], 409);
        }

        /* ============================
       STEP 2: CHECK IN CANDIDATE TABLE
       ============================ */
        $existingCandidate = ContractEmployee::where('aadhar_number', $request->aadhar_number)
            ->where('is_deleted', 0)
            ->first();

        if ($existingCandidate) {
            return response()->json([
                'success'     => false,
                'message'     => 'This Aadhar number is already registered as Candidate.',
                'existing_id' => $existingCandidate->id,
                'type'        => 'candidate',
            ], 409);
        }

        $data = $request->all();

        if ($request->reference === 'other') {
            $data['other_reference'] = $request->other_reference;
        }

        /* ============================
       PROFILE PHOTO UPLOAD
        ============================ */
        $photoDir = public_path('uploads/contract_candidate/profile');
        if (!file_exists($photoDir)) {
            mkdir($photoDir, 0755, true);
        }

        if ($request->hasFile('profile_picture')) {
            $photo = $request->file('profile_picture');
            $photoName = now()->format('YmdHis') . '_' .  rand(10000, 99999) . '_' . Str::slug(pathinfo($photo->getClientOriginalName(), PATHINFO_FILENAME))
                . '.' . $photo->getClientOriginalExtension();

            $photo->move($photoDir, $photoName);
            $data['profile_picture'] = 'uploads/contract_candidate/profile/' . $photoName;
        }
        // Create employee
        $emp = ContractEmployee::create($data);

        /* ============================
       MULTIPLE DOCUMENT UPLOAD
     ============================ */
        if ($request->hasFile('documents')) {

            $docDir = public_path('uploads/contract_candidate/documents');
            if (!file_exists($docDir)) {
                mkdir($docDir, 0755, true);
            }

            foreach ($request->file('documents') as $doc) {

                $originalName = $doc->getClientOriginalName();

                $docName = now()->format('YmdHis') . '_' . rand(10000, 99999) . '_' .
                    Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) .
                    '.' . $doc->getClientOriginalExtension();

                $doc->move($docDir, $docName);

                ContractCandidateDocument::create([
                    'employee_id'   => $emp->id,
                    'original_name' => $originalName,
                    'document_path' => 'uploads/contract_candidate/documents/' . $docName,
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
        // dd(Hash::make('Portal#123'));
        $employees = ContractEmployee::where('status', '1')->where('is_deleted', 0)
            ->when($request->filled('reference'), function ($q) use ($request) {
                $q->where('reference', $request->reference);
            })
            ->when($request->filled('interview_status'), function ($q) use ($request) {
                $q->where('interview_status', $request->interview_status);
            })
            ->when($request->filled('joining_status'), function ($q) use ($request) {
                $q->where('joining_status', $request->joining_status);
            })
            ->when($request->filled('education'), function ($q) use ($request) {
                $q->where('education_id', $request->education);
            })
            // Add company filter here
            ->when($request->filled('company_id'), function ($q) use ($request) {
            $q->where('company_id', $request->company_id);
            })
            ->when($request->filled('from_date') && $request->filled('to_date'), function ($q) use ($request) {
                $from = Carbon::parse($request->from_date)->startOfDay();
                $to   = Carbon::parse($request->to_date)->endOfDay();
                $q->whereBetween('created_at', [$from, $to]);
            })
            // ->where('joining_status', 'not_joined')
            ->with('notes')
            ->select('id', 'company_id', 'name', 'phone_number', 'reference', 'other_reference', 'interview_status', 'joining_status', 'education_id', 'status', 'created_at')
            ->orderByDesc('id')
            ->get();

        $interview_status = ContractEmployee::where('is_deleted', 0)->select('interview_status')
            ->whereNotNull('interview_status')
            ->distinct()
            ->orderBy('interview_status')
            ->get();

        $candidate_status = ContractEmployee::where('is_deleted', 0)->select('joining_status')
            ->whereNotNull('joining_status')
            ->distinct()
            ->orderBy('joining_status')
            ->get();

        $companies = Company::where('status', '1')->where('is_deleted', 0)
            ->select('id', 'company_name', 'company_emp_id')
            ->latest()
            ->get();

        $educations = ContractEmployee::where('status', 1)
            ->where('is_deleted', 0)
            ->pluck('education')
            ->unique()
            ->values()
            ->toArray();

        $pssemployees = Employee::where('status', '1')->where('is_deleted', 0)
            ->where('id', '!=', 1)
            ->where('job_form_referal', 1)
            ->select('full_name', 'id')
            ->get();

        $educations = Eductions::where('status', '1')->where('is_deleted', 0)
            ->select('id', 'eduction_name')
            ->latest()
            ->get();


        return response()->json(['success' => true, 'data' => [
            'employees'         => $employees,
            'interview_status'  => $interview_status,
            'candidate_status'  => $candidate_status,
            'companies' => $companies,
            'pssemployees' => $pssemployees,
            'educations' => $educations
        ]]);
    }

    public function show($id)
    {
        // Load employee with documents
        $emp = ContractEmployee::with(['documents', 'education'])->where('id', $id)
            ->where('is_deleted', 0)
            ->firstOrFail();

        $notes = collect(); // Always start with empty collection

        /**
         * ✅ INTERVIEW STATUS NOTES
         */
        if (in_array($emp->interview_status, ['rejected', 'hold', 'waiting'])) {

            // Only query if column exists
            if (\Schema::hasColumn('note_attachments', 'notes_status')) {
                $interviewNote = NoteAttachment::where('parent_id', $emp->id)
                    ->where('parent_type', 'contract_emp')
                    ->whereIn('notes_status', ['rejected', 'hold', 'waiting'])
                    ->latest('id')
                    ->first();

                if ($interviewNote) {
                    $notes->push($interviewNote);
                }
            }
        }

        /**
         * ✅ JOINING STATUS NOTES
         */
        if ($emp->joining_status === 'not_joined') {

            if (\Schema::hasColumn('note_attachments', 'notes_status')) {
                $joiningNote = NoteAttachment::where('parent_id', $emp->id)
                    ->where('parent_type', 'contract_emp')
                    ->where('notes_status', 'not_joined')
                    ->latest('id')
                    ->first();

                if ($joiningNote) {
                    $notes->push($joiningNote);
                }
            }
        }

        // Attach filtered notes (always returns array)
        $emp->notes = $notes->values(); // ->values() to reset keys

        return response()->json([
            'success' => true,
            'data'    => $emp
        ]);
    }


    public function update(Request $request, $id)
    {
        $emp = ContractEmployee::where('id', $id)->where('is_deleted', 0)->firstOrFail();

        /* ============================
       STEP 1: CHECK IN EMPLOYEE TABLE
       ============================ */
        $existingEmployee = ContractCanEmp::where('aadhar_number', $request->aadhar_number)
            ->where('is_deleted', 0)
            ->first();

        if ($existingEmployee) {
            return response()->json([
                'success'     => false,
                'message'     => 'This Aadhaar number is already registered as Employee.',
                'existing_id' => $existingEmployee->id,
                'type'        => 'employee',
                'field'       => 'aadhar_number'
            ], 409);
        }

        /* ============================
       STEP 2: CHECK IN CANDIDATE TABLE (EXCLUDE CURRENT ID)
       ============================ */
        $existingCandidate = ContractEmployee::where('aadhar_number', $request->aadhar_number)
            ->where('is_deleted', 0)
            ->where('id', '!=', $id)
            ->first();

        if ($existingCandidate) {
            return response()->json([
                'success'     => false,
                'message'     => 'This Aadhaar number is already registered as Candidate.',
                'existing_id' => $existingCandidate->id,
                'type'        => 'candidate',
                'field'       => 'aadhar_number'
            ], 409);
        }


        $data = $request->all();
        if ($request->reference === 'other') {
            $data['other_reference'] = $request->other_reference;
        }


        /* ============================
       UPDATE PROFILE PHOTO
     ============================ */
        $photoDir = public_path('uploads/contract_candidate/profile');
        if (!file_exists($photoDir)) {
            mkdir($photoDir, 0755, true);
        }

        if ($request->hasFile('profile_picture')) {

            // ❌ delete old photo
            if (!empty($emp->profile_picture) && file_exists(public_path($emp->profile_picture))) {
                unlink(public_path($emp->profile_picture));
            }

            $photo = $request->file('profile_picture');
            $photoName = now()->format('YmdHis') . '_' . rand(10000, 99999) . '_' . Str::slug(pathinfo($photo->getClientOriginalName(), PATHINFO_FILENAME)) .
                '.' . $photo->getClientOriginalExtension();

            $photo->move($photoDir, $photoName);

            $data['profile_picture'] = 'uploads/contract_candidate/profile/' . $photoName;
        }


        $emp->update($request->all());

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
        $toDelete = ContractCandidateDocument::where('employee_id', $emp->id)
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

            $docDir = public_path('uploads/contract_candidate/documents');
            if (!file_exists($docDir)) {
                mkdir($docDir, 0755, true);
            }

            foreach ($newFiles as $file) {

                $originalName = $file->getClientOriginalName();

                $docName = now()->format('YmdHis') . '_' . rand(10000, 99999) . '_' .
                    Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) . '.' .
                    $file->getClientOriginalExtension();

                $file->move($docDir, $docName);

                ContractCandidateDocument::create([
                    'employee_id'   => $emp->id,
                    'original_name' => $originalName,
                    'document_path' => 'uploads/contract_candidate/documents/' . $docName,
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
        $emp = ContractEmployee::where('id', $id)->where('is_deleted', 0)->firstOrFail();
        $emp->update(['is_deleted' => 1]);

        return response()->json(['success' => true, 'message' => 'Deleted successfully']);
    }

    public function import(Request $request)
    {
        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');

        $header = fgetcsv($handle);
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

            // 🔒 Safe read
            $aadhar = $this->csvValue($data, 'aadhar_number');

            // 🔁 Duplicate check only if aadhar exists
            if ($aadhar) {
                $exists = ContractEmployee::where('aadhar_number', $aadhar)
                    ->where('is_deleted', 0)
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }
            }

            /* 🔹 Dates */
            $interview_date = $this->parseDate($this->csvValue($data, 'interview_date'));

            if (!Company::where('id', $request->company_id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid company_id'
                ], 422);
            }

            /* 🔹 Insert */
            ContractEmployee::create([
                'name'           => $this->csvValue($data, 'name'),
                'gender'         => $this->csvValue($data, 'gender'),
                'marital_status'     => $this->csvValue($data, 'marital_status'),
                'phone_number'   => $this->csvValue($data, 'phone_number'),
                'aadhar_number'  => $aadhar,
                'pan_number'     => $this->csvValue($data, 'pan_number'),
                'interview_date'  => $interview_date,
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

    private function csvValue(array $data, string $key)
    {
        return array_key_exists($key, $data) && $data[$key] !== ''
            ? $data[$key]
            : null;
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
        $dateOfJoining = Carbon::parse($request->date_of_joining)->format('Ymd');

        $company =  Company::where('id', $company_id)->where('company_emp_id', 'automatic')->select('company_emp_id', 'prefix')->first();
        $prefix = $company->prefix . $dateOfJoining;

        /**
         * Get last employee_id for same date
         * Example: pss20250112005
         */
        $lastEmployee = ContractEmployee::where('employee_id', 'like', $prefix . '%')
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

        $newEmployeeId = $prefix . $nextNumber;

        return response()->json([
            'success' => true,
            'employee_id' => $newEmployeeId
        ]);
    }

    public function moveCandidateToEmp(Request $request)
    {

        // $data = $request->all();
        // $emp = ContractCanEmp::create($data);

        $newEmployeeId = null;
        $company_id = $request->company_id;


        $company = Company::where('id', $company_id)
            ->select('company_emp_id', 'prefix')
            ->first();
        // $company =  Company::where('id', $company_id)->where('company_emp_id', 'automatic')->select('company_emp_id', 'prefix')->first();

        if ($company && $company->company_emp_id === 'automatic') {
            $dateOfJoining = Carbon::parse($request->date_of_joining)->format('Ym');
            $prefix = $company->prefix . $dateOfJoining;
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

            $newEmployeeId = $prefix . $nextNumber;
        }

        $emp = ContractCanEmp::create(array_merge(
            $request->all(),
            [
                'address'     => '-',
                'employee_id' => $newEmployeeId
            ]
        ));

        return response()->json(['success' => true, 'message' => 'Contract Employee created successfully']);
    }
}
