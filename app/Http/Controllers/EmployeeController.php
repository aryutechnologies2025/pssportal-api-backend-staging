<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\ContactDetail;
use App\Models\EmployeeEducation;
use App\Models\EmployeeExperience;
use App\Models\EmployeeVerification;
use App\Models\EmployeeDocumentGroup;
use App\Models\EmployeeDocument;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Models\Company;
use App\Models\Role;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Carbon\Carbon;


class EmployeeController extends Controller
{
    // LIST
    public function index(Request $request)
    {
        // $employees = Employee::where('is_deleted', 0)
        //     ->with(['role'])
        //     ->select('full_name', 'role_id', 'job_form_referal', 'company_id', 'id')
        //     ->where('id', '!=', 1)
        //     ->get();

        $employees = Employee::where('is_deleted', 0)
            ->with(['role'])
            ->select('full_name', 'role_id', 'job_form_referal', 'company_id', 'id', 'status', 'jb_referal')
            ->where('id', '!=', 1)
            ->when($request->filled('role_id'), function ($q) use ($request) {
                $q->where('role_id', $request->role_id);
            })
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->orderBy('id', 'DESC')
            ->get();


        $assignedCompanyIds = $employees
            ->pluck('company_id')   // [[18], [19]]
            ->flatten()             // [18, 19]
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        // dd($assignedCompanyIds);


        $companies = Company::select('id', 'company_name')
            ->where('status', '1')
            ->where('is_deleted', 0)
            ->get();

        $companies = $companies->map(function ($company) use ($assignedCompanyIds) {
            $company->assign_status = in_array($company->id, $assignedCompanyIds)
                ? 'already_assign'
                : 'not_assign';

            return $company;
        });


        $roles = Role::select('id', 'role_name')->where('status', '1')->where('is_deleted', 0)
            ->where('id', '!=', 1)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Employees fetched successfully',
            'data' => $employees,
            'companies' => $companies,
            'roles' => $roles
        ], 200);
    }

    public function show($id)
    {
        $employee = Employee::with([
            'contacts',
            'educations',
            'experiences',
            'documentGroups.documents',
            'verifications'
        ])
            ->where('is_deleted', 0)
            ->find($id);

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee not found'
            ], 404);
        }

        // Hide timestamps ONLY for this response
        $employee->makeHidden(['created_at', 'updated_at']);

        $employee->contacts->makeHidden(['created_at', 'updated_at']);
        $employee->educations->makeHidden(['created_at', 'updated_at']);
        $employee->experiences->makeHidden(['created_at', 'updated_at']);
        $employee->verifications->makeHidden(['created_at', 'updated_at']);

        $employee->documentGroups->each(function ($group) {
            $group->makeHidden(['created_at', 'updated_at']);
            $group->documents->makeHidden(['created_at', 'updated_at']);
        });

        return response()->json([
            'success' => true,
            'message' => 'Employee details fetched successfully',
            'data' => $employee
        ], 200);
    }


    public function store(Request $request)
    {

        // $validator = Validator::make($request->all(), [
        //     'offical_email' => [
        //         'required',
        //         'email',
        //         Rule::unique('employees', 'offical_email')->where(
        //             fn($q) =>
        //             $q->where('is_deleted', 0)
        //         ),
        //     ],
        // ]);


        $validator = Validator::make($request->all(), [

            'offical_email' => [
                'required',
                'email',
                Rule::unique('employees', 'offical_email')
                    ->where(fn($q) => $q->where('is_deleted', 0)),
            ],

            'aadhaar_no' => [
                'required',
                Rule::unique('employees', 'aadhaar_no')
                    ->where(fn($q) => $q->where('is_deleted', 0)),
            ],

            'pan_no' => [
                'required',
                Rule::unique('employees', 'pan_no')
                    ->where(fn($q) => $q->where('is_deleted', 0)),
            ],

            'email' => [
                'nullable',
                'email',
                Rule::unique('employees', 'email')
                    ->where(fn($q) => $q->where('is_deleted', 0)),
            ],

        ], [
            'aadhaar_no.unique' => 'Aadhaar number already exists.',
            'pan_no.unique'     => 'PAN number already exists.',
            'email.unique'      => 'Email already exists.',
            'offical_email.unique' => 'Official email already exists.',
        ]);



        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors()
            ], 422);
        }

        DB::transaction(function () use ($request) {

            // ---------------------------
            // Ensure folders exist
            $photoDir = public_path('uploads/emp_profile_pic');
            $docDir   = public_path('uploads/emp_documents');

            if (!file_exists($photoDir)) mkdir($photoDir, 0755, true);
            if (!file_exists($docDir))   mkdir($docDir, 0755, true);

            // ---------------------------
            // Upload employee photo
            $photoPath = null;
            if ($request->hasFile('photo')) {
                $photo = $request->file('photo');
                $photoName = time() . '_' . preg_replace('/\s+/', '_', $photo->getClientOriginalName());
                $photo->move($photoDir, $photoName);
                $photoPath = 'uploads/emp_profile_pic/' . $photoName;
            }

            // ✅ FIXED CREATE
            $employee = Employee::create(array_merge(
                $request->only([
                    'full_name',
                    'aadhaar_no',
                    'pan_no',
                    'father_name',
                    'mother_name',
                    'marital_status',
                    'spouse_name',
                    'phone_no',
                    'email',
                    'qualification',
                    'date_of_birth',
                    'local_address',
                    'permanent_address',
                    'bank_name',
                    'bank_account_no',
                    'ifsc_code',
                    'bank_branch',
                    'salary_amount',
                    'salary_basis',
                    'payment_type',
                    'effective_date',
                    'offical_email',
                    'created_by',
                    'role_id',
                    'branch_id',
                    'date_of_joining',
                    'gen_employee_id'
                ]),
                [
                    'photo'      => $photoPath,
                    'skills'     => is_array($request->skills) ? json_encode($request->skills) : $request->skills,
                    'password'   => Hash::make($request->password),
                    'is_deleted' => 0,
                ]
            ));

            // ---------------------------
            // Contacts
            foreach ($request->contacts ?? [] as $contact) {
                ContactDetail::create([
                    'parent_id'    => $employee->id,
                    'parent_type'  => 'employee',
                    'name'         => $contact['name'],
                    'relationship' => $contact['relationship'],
                    'phone_number' => $contact['phone_number'],
                ]);
            }

            // ---------------------------
            // Education
            foreach ($request->educations ?? [] as $edu) {
                EmployeeEducation::create([
                    'employee_id' => $employee->id,
                    ...$edu
                ]);
            }

            // Experience
            foreach ($request->experiences ?? [] as $exp) {
                EmployeeExperience::create([
                    'employee_id' => $employee->id,
                    'job_title' => $exp['job_title'] ?? null,
                    'company_industry' => $exp['company_industry'] ?? null,
                    'company_name' => $exp['company_name'] ?? null,
                    'previous_salary' => $exp['previous_salary'] ?? null,
                    'from_date' => $exp['from_date'] ?? null,
                    'to_date' => $exp['to_date'] ?? null,
                    'responsibilities' => $exp['responsibilities'] ?? null, // array
                    'verification_documents' => $exp['verification_documents'] ?? null, // array
                ]);
            }
            // Verifications
            foreach ($request->verifications ?? [] as $ver) {
                $data = [
                    'employee_id'    => $employee->id,                // ensure correct employee ID
                    'document_type'  => $ver['type'] ?? null,        // document type
                    'is_verified'    => $ver['status'] ?? 0,         // default to 0 if not provided
                    'is_deleted'     => 0,                           // mark as active
                    'created_date'   => now()->format('Y-m-d'),      // current date
                ];

                EmployeeVerification::create($data);
            }
            // ---------------------------
            // Document Groups

            // INPUT (title)
            $documentInputs = $request->input('documents');

            // FILES
            $documentFiles = $request->file('documents');
            if (!empty($documentInputs)) {

                foreach ($documentInputs as $docIndex => $docInput) {

                    // ✅ CREATE DOCUMENT GROUP WITH TITLE
                    $group = EmployeeDocumentGroup::create([
                        'employee_id' => $employee->id,
                        'title'       => $docInput['title'], // ✅ WILL STORE NOW
                    ]);

                    // ✅ SAVE FILES FOR THIS GROUP

                    foreach ($documentFiles[$docIndex]['files'] as $file) {

                        $filename = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());
                        $file->move($docDir, $filename);

                        EmployeeDocument::create([
                            'document_group_id' => $group->id,
                            'file_path'         => 'uploads/emp_documents/' . $filename,
                            'original_name'     => $file->getClientOriginalName(),
                        ]);
                    }
                }
            }
        });

        return response()->json(['message' => 'Employee created successfully']);
    }

    // UPDATE
    // public function update(Request $request, $id)
    // {

    //     $employeeId =  $request->employee_id;

    //     $validator = Validator::make($request->all(), [
    //         'offical_email' => [
    //             'required',
    //             'email',
    //             Rule::unique('employees', 'offical_email')
    //                 ->ignore($employeeId)               // 👈 ignore current employee
    //                 ->where(function ($q) {
    //                     $q->where('is_deleted', 0);        // 👈 ignore deleted records
    //                 }),
    //         ],
    //     ], [
    //         'offical_email.unique' => 'Official email already exists.',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Validation failed',
    //             'errors'  => $validator->errors(),
    //         ], 422);
    //     }

    //     DB::transaction(function () use ($request, $employee) {

    //         // ---------------------------
    //         // Ensure upload folders exist
    //         $photoDir = public_path('uploads/emp_profile_pic');
    //         $docDir = public_path('uploads/emp_documents');


    //         if (!file_exists($photoDir)) {
    //             mkdir($photoDir, 0755, true);
    //         }
    //         if (!file_exists($docDir)) {
    //             mkdir($docDir, 0755, true);
    //         }

    //         // ---------------------------
    //         // Upload employee photo if new file provided
    //         if ($request->hasFile('photo')) {
    //             $photo = $request->file('photo');
    //             $photoName = time() . '_' . preg_replace('/\s+/', '_', $photo->getClientOriginalName());
    //             $photo->move($photoDir, $photoName);
    //             $photoPath = 'uploads/emp_profile_pic/' . $photoName;
    //             $request->merge(['photo' => $photoPath]); // merge to request so update can use it
    //         }

    //         $employeeId =  $request->employee_id;
    //         // ---------------------------
    //         // Update employee main details
    //         $employee->update($request->only([
    //             'full_name',
    //             'aadhaar_no',
    //             'pan_no',
    //             'father_name',
    //             'mother_name',
    //             'marital_status',
    //             'spouse_name',
    //             'phone_no',
    //             'email',
    //             'qualification',
    //             'date_of_birth',
    //             'local_address',
    //             'permanent_address',
    //             'photo',
    //             'bank_name',
    //             'bank_account_no',
    //             'ifsc_code',
    //             'bank_branch',
    //             'salary_amount',
    //             'salary_basis',
    //             'payment_type',
    //             'effective_date',
    //             'skills',
    //             'updated_by',
    //             'offical_email',
    //             'role_id',
    //             'branch_id'
    //         ]));

    //         // ---------------------------
    //         // // Contacts
    //         $contactIdsFromRequest = [];

    //         foreach ($request->contacts ?? [] as $contact) {

    //             // ✅ UPDATE
    //             if (!empty($contact['id'])) {

    //                 ContactDetail::where('id', $contact['id'])
    //                     ->where('parent_id', $employeeId)
    //                     ->where('parent_type', 'employee')
    //                     ->update([
    //                         'name'         => $contact['name'] ?? null,
    //                         'relationship' => $contact['relationship'] ?? null,
    //                         'phone_number' => $contact['phone_number'] ?? null,
    //                         'is_deleted'   => 0,
    //                     ]);

    //                 $contactIdsFromRequest[] = $contact['id'];
    //             }
    //             // ✅ CREATE
    //             else {

    //                 $newContact = ContactDetail::create([
    //                     'parent_id'    => $employeeId,
    //                     'parent_type'  => 'employee',
    //                     'name'         => $contact['name'] ?? null,
    //                     'relationship' => $contact['relationship'] ?? null,
    //                     'phone_number' => $contact['phone_number'] ?? null,
    //                     'is_deleted'   => 0,
    //                 ]);

    //                 $contactIdsFromRequest[] = $newContact->id;
    //             }
    //         }

    //         /**
    //          * ✅ MARK REMOVED CONTACTS AS DELETED
    //          */
    //         ContactDetail::where('parent_id', $employeeId)
    //             ->where('parent_type', 'employee')
    //             ->whereNotIn('id', $contactIdsFromRequest)
    //             ->update([
    //                 'is_deleted' => 1
    //             ]);


    //         // ---------------------------
    //         // Education
    //         $educationIdsFromRequest = [];

    //         foreach ($request->educations ?? [] as $edu) {

    //             $data = [
    //                 'employee_id' => $employeeId,
    //                 'school_name'      => $edu['school_name'] ?? null,
    //                 'department_name'   => $edu['department_name'] ?? null,
    //                 'year_of_passing'        => $edu['year_of_passing'] ?? null,
    //                 'is_deleted'  => 0, // ✅ mark active
    //             ];

    //             // ✅ UPDATE
    //             if (!empty($edu['id'])) {
    //                 EmployeeEducation::where('id', $edu['id'])
    //                     ->where('employee_id', $employeeId)
    //                     ->update($data);

    //                 $educationIdsFromRequest[] = $edu['id'];
    //             }
    //             // ✅ CREATE
    //             else {
    //                 $newEdu = EmployeeEducation::create($data);
    //                 $educationIdsFromRequest[] = $newEdu->id;
    //             }
    //         }

    //         // ✅ MARK OTHER EDUCATIONS AS DELETED
    //         EmployeeEducation::where('employee_id', $employeeId)
    //             ->whereNotIn('id', $educationIdsFromRequest)
    //             ->update(['is_deleted' => 1]);


    //         // ---------------------------
    //         // Experience

    //         foreach ($request->experiences ?? [] as $exp) {

    //             $data = [
    //                 'employee_id'            => $employeeId,
    //                 'job_title'              => $exp['job_title'] ?? null,
    //                 'company_industry'       => $exp['company_industry'] ?? null,
    //                 'company_name'           => $exp['company_name'] ?? null,
    //                 'previous_salary'        => $exp['previous_salary'] ?? null,
    //                 'from_date'              => $exp['from_date'] ?? null,
    //                 'to_date'                => $exp['to_date'] ?? null,
    //                 'responsibilities'       => $exp['responsibilities'] ?? [],
    //                 'verification_documents' => $exp['verification_documents'] ?? [],
    //             ];

    //             // ✅ UPDATE
    //             if (!empty($exp['id'])) {

    //                 // EmployeeExperience::where('id', $exp['id'])
    //                 //     ->where('employee_id', $employeeId) // ✅ integer
    //                 //     ->update($data);

    //                 EmployeeExperience::where('id', $exp['id'])
    //                     ->where('employee_id', $employeeId)
    //                     ->update(array_merge($data, [
    //                         'is_deleted' => 1,
    //                     ]));
    //             }
    //             // ✅ INSERT
    //             else {
    //                 EmployeeExperience::create($data);
    //             }
    //         }



    //         // ---------------------------
    //         // Verifications
    //         $verificationIdsFromRequest = [];

    //         foreach ($request->verifications ?? [] as $ver) {

    //             $data = [
    //                 'employee_id' => $employeeId,
    //                 'document_type' => $ver['type'] ?? null,
    //                 'is_verified' => $ver['status'] ?? null,
    //                 'created_date'   => date('Y-m-d'),
    //                 'is_deleted' => 0, // ✅ mark as active
    //             ];

    //             // ✅ UPDATE existing
    //             if (!empty($ver['id'])) {
    //                 EmployeeVerification::where('id', $ver['id'])
    //                     ->where('employee_id', $employeeId)
    //                     ->update($data);

    //                 $verificationIdsFromRequest[] = $ver['id'];
    //             }
    //             // ✅ CREATE new
    //             else {
    //                 $newVer = EmployeeVerification::create($data);
    //                 $verificationIdsFromRequest[] = $newVer->id;
    //             }
    //         }

    //         // ✅ MARK OTHER VERIFICATIONS AS DELETED
    //         EmployeeVerification::where('employee_id', $employeeId)
    //             ->whereNotIn('id', $verificationIdsFromRequest)
    //             ->update(['is_deleted' => 1]);


    //         // ---------------------------
    //         // Employee Document Groups & Files
    //         $documentGroupIdsFromRequest = [];

    //         foreach ($request->documents ?? [] as $docGroup) {
    //             // Decode JSON if sent via FormData
    //             if (is_string($docGroup)) {
    //                 $docGroup = json_decode($docGroup, true);
    //             }

    //             if (!is_array($docGroup)) {
    //                 continue;
    //             }

    //             $data = [
    //                 'employee_id' => $employeeId,
    //                 'title'       => $docGroup['title'] ?? null,
    //                 'is_deleted'  => 0, // mark as active
    //             ];

    //             // ✅ UPDATE existing
    //             if (!empty($docGroup['document_group_id'])) {
    //                 $group = EmployeeDocumentGroup::where('id', $docGroup['document_group_id'])
    //                     ->where('employee_id', $employeeId)
    //                     ->first();

    //                 if ($group) {
    //                     $group->update($data);
    //                     $documentGroupIdsFromRequest[] = $group->id;
    //                 } else {
    //                     // If ID sent but not found, skip files
    //                     continue;
    //                 }
    //             }
    //             // ✅ CREATE new
    //             else {
    //                 $group = EmployeeDocumentGroup::create($data);
    //                 $documentGroupIdsFromRequest[] = $group->id;
    //             }

    //             // Process files
    //             $files = $docGroup['files'] ?? [];

    //             foreach ($files as $file) {

    //                 /**
    //                  * ✅ CASE 1: NEW FILE UPLOAD (UploadedFile directly)
    //                  */
    //                 if ($file instanceof UploadedFile) {

    //                     $originalName = $file->getClientOriginalName();
    //                     $filename = time() . '_' . preg_replace('/\s+/', '_', $originalName);
    //                     $file->move($docDir, $filename);

    //                     EmployeeDocument::create([
    //                         'document_group_id' => $group->id,
    //                         'file_path'         => 'uploads/emp_documents/' . $filename,
    //                         'original_name'     => $originalName,
    //                     ]);

    //                     continue;
    //                 }

    //                 /**
    //                  * ✅ CASE 2: JSON STRING (existing file)
    //                  */
    //                 if (is_string($file)) {
    //                     $file = json_decode($file, true);
    //                 }

    //                 if (!is_array($file)) {
    //                     continue;
    //                 }

    //                 /**
    //                  * ✅ EXISTING FILE UPDATE
    //                  */
    //                 if (!empty($file['document_id']) && !empty($file['path'])) {

    //                     EmployeeDocument::where('id', $file['document_id'])->update([
    //                         'file_path'     => $file['path'],
    //                         // 'original_name' => basename($file['path'])
    //                     ]);
    //                 }
    //             }
    //         }

    //         // ✅ MARK OTHER DOCUMENT GROUPS AS DELETED
    //         EmployeeDocumentGroup::where('employee_id', $employeeId)
    //             ->whereNotIn('id', $documentGroupIdsFromRequest)
    //             ->update(['is_deleted' => 1]);
    //     });

    //     return response()->json(['message' => 'Employee updated successfully']);
    // }

    public function update(Request $request, $id)
    {

        // ✅ Fetch employee FIRST
        $employee = Employee::where('id', $id)
            ->where('is_deleted', 0)
            ->firstOrFail();

        // ✅ Validation
        $validator = Validator::make($request->all(), [

            'offical_email' => [
                'required',
                'email',
                Rule::unique('employees', 'offical_email')
                    ->ignore($id)
                    ->where(fn($q) => $q->where('is_deleted', 0)),
            ],

            'aadhaar_no' => [
                'required',
                Rule::unique('employees', 'aadhaar_no')
                    ->ignore($id)
                    ->where(fn($q) => $q->where('is_deleted', 0)),
            ],

            'pan_no' => [
                'required',
                Rule::unique('employees', 'pan_no')
                    ->ignore($id)
                    ->where(fn($q) => $q->where('is_deleted', 0)),
            ],

            'email' => [
                'nullable',
                'email',
                Rule::unique('employees', 'email')
                    ->ignore($id)
                    ->where(fn($q) => $q->where('is_deleted', 0)),
            ],

        ], [
            'aadhaar_no.unique' => 'Aadhaar number already exists.',
            'pan_no.unique'     => 'PAN number already exists.',
            'email.unique'      => 'Email already exists.',
            'offical_email.unique' => 'Official email already exists.',
        ]);


        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }


        DB::transaction(function () use ($request, $employee, $id) {

            // ---------------------------
            // Ensure upload folders exist
            $photoDir = public_path('uploads/emp_profile_pic');
            $docDir = public_path('uploads/emp_documents');


            if (!file_exists($photoDir)) {
                mkdir($photoDir, 0755, true);
            }
            if (!file_exists($docDir)) {
                mkdir($docDir, 0755, true);
            }

            // ---------------------------
            // Upload employee photo if new file provided
            if ($request->hasFile('photo')) {
                $photo = $request->file('photo');
                $photoName = time() . '_' . preg_replace('/\s+/', '_', $photo->getClientOriginalName());
                $photo->move($photoDir, $photoName);
                $photoPath = 'uploads/emp_profile_pic/' . $photoName;
                $request->merge(['photo' => $photoPath]); // merge to request so update can use it
            }

            $employeeId =  $request->employee_id;
            // ---------------------------
            // Update employee
            $employee->update($request->only([
                'full_name',
                'aadhaar_no',
                'pan_no',
                'father_name',
                'mother_name',
                'marital_status',
                'spouse_name',
                'phone_no',
                'email',
                'qualification',
                'date_of_birth',
                'local_address',
                'permanent_address',
                'photo',
                'bank_name',
                'bank_account_no',
                'ifsc_code',
                'bank_branch',
                'salary_amount',
                'salary_basis',
                'payment_type',
                'effective_date',
                'skills',
                'updated_by',
                'offical_email',
                'role_id',
                'branch_id',
                'date_of_joining',
                'gen_employee_id'
            ]));

            // ---------------------------
            // // Contacts
            $contactIdsFromRequest = [];

            foreach ($request->contacts ?? [] as $contact) {

                // ✅ UPDATE
                if (!empty($contact['id'])) {

                    ContactDetail::where('id', $contact['id'])
                        ->where('parent_id', $employeeId)
                        ->where('parent_type', 'employee')
                        ->update([
                            'name'         => $contact['name'] ?? null,
                            'relationship' => $contact['relationship'] ?? null,
                            'phone_number' => $contact['phone_number'] ?? null,
                            'is_deleted'   => 0,
                        ]);

                    $contactIdsFromRequest[] = $contact['id'];
                }
                // ✅ CREATE
                else {

                    $newContact = ContactDetail::create([
                        'parent_id'    => $employeeId,
                        'parent_type'  => 'employee',
                        'name'         => $contact['name'] ?? null,
                        'relationship' => $contact['relationship'] ?? null,
                        'phone_number' => $contact['phone_number'] ?? null,
                        'is_deleted'   => 0,
                    ]);

                    $contactIdsFromRequest[] = $newContact->id;
                }
            }

            /**
             * ✅ MARK REMOVED CONTACTS AS DELETED
             */
            ContactDetail::where('parent_id', $employeeId)
                ->where('parent_type', 'employee')
                ->whereNotIn('id', $contactIdsFromRequest)
                ->update([
                    'is_deleted' => 1
                ]);


            // ---------------------------
            // Education
            // $educationIdsFromRequest = [];

            // foreach ($request->educations ?? [] as $edu) {

            //     $data = [
            //         'employee_id' => $employeeId,
            //         'school_name'      => $edu['school_name'] ?? null,
            //         'department_name'   => $edu['department_name'] ?? null,
            //         'year_of_passing'        => $edu['year_of_passing'] ?? null,
            //         'is_deleted'  => 0, // ✅ mark active
            //     ];

            //     // ✅ UPDATE
            //     if (!empty($edu['id'])) {
            //         EmployeeEducation::where('id', $edu['id'])
            //             ->where('employee_id', $employeeId)
            //             ->update($data);

            //         $educationIdsFromRequest[] = $edu['id'];
            //     }
            //     // ✅ CREATE
            //     else {
            //         $newEdu = EmployeeEducation::create($data);
            //         $educationIdsFromRequest[] = $newEdu->id;
            //     }
            // }

            // // ✅ MARK OTHER EDUCATIONS AS DELETED
            // EmployeeEducation::where('employee_id', $employeeId)
            //     ->whereNotIn('id', $educationIdsFromRequest)
            //     ->update(['is_deleted' => 1]);

            $educationIdsFromRequest = [];
            $hasExistingId = false;

            foreach ($request->educations ?? [] as $edu) {

                $data = [
                    'employee_id'     => $employeeId,
                    'school_name'     => $edu['school_name'] ?? null,
                    'department_name' => $edu['department_name'] ?? null,
                    'year_of_passing' => $edu['year_of_passing'] ?? null,
                    'is_deleted'      => 0,
                ];

                // ✅ UPDATE
                if (!empty($edu['id'])) {

                    $hasExistingId = true;

                    EmployeeEducation::where('id', $edu['id'])
                        ->where('employee_id', $employeeId)
                        ->update($data);

                    $educationIdsFromRequest[] = $edu['id'];
                }
                // ✅ CREATE
                else {
                    $newEdu = EmployeeEducation::create($data);
                    $educationIdsFromRequest[] = $newEdu->id;
                }
            }

            /**
             * ✅ MARK DELETED ONLY IF EXISTING IDS WERE SENT
             */
            if ($hasExistingId) {
                EmployeeEducation::where('employee_id', $employeeId)
                    ->whereNotIn('id', $educationIdsFromRequest)
                    ->update(['is_deleted' => 1]);
            }



            // ---------------------------
            // Experience

            foreach ($request->experiences ?? [] as $exp) {

                $data = [
                    'employee_id'            => $employeeId,
                    'job_title'              => $exp['job_title'] ?? null,
                    'company_industry'       => $exp['company_industry'] ?? null,
                    'company_name'           => $exp['company_name'] ?? null,
                    'previous_salary'        => $exp['previous_salary'] ?? null,
                    'from_date'              => $exp['from_date'] ?? null,
                    'to_date'                => $exp['to_date'] ?? null,
                    'responsibilities'       => $exp['responsibilities'] ?? [],
                    'verification_documents' => $exp['verification_documents'] ?? [],
                ];

                // ✅ UPDATE
                if (!empty($exp['id'])) {

                    // EmployeeExperience::where('id', $exp['id'])
                    //     ->where('employee_id', $employeeId) // ✅ integer
                    //     ->update($data);

                    EmployeeExperience::where('id', $exp['id'])
                        ->where('employee_id', $employeeId)
                        ->update(array_merge($data, [
                            'is_deleted' => 1,
                        ]));
                }
                // ✅ INSERT
                else {
                    EmployeeExperience::create($data);
                }
            }



            // ---------------------------
            // Verifications
            $verificationIdsFromRequest = [];

            foreach ($request->verifications ?? [] as $ver) {

                $data = [
                    'employee_id' => $employeeId,
                    'document_type' => $ver['type'] ?? null,
                    'is_verified' => $ver['status'] ?? null,
                    'created_date'   => date('Y-m-d'),
                    'is_deleted' => 0, // ✅ mark as active
                ];

                // ✅ UPDATE existing
                if (!empty($ver['id'])) {
                    EmployeeVerification::where('id', $ver['id'])
                        ->where('employee_id', $employeeId)
                        ->update($data);

                    $verificationIdsFromRequest[] = $ver['id'];
                }
                // ✅ CREATE new
                else {
                    $newVer = EmployeeVerification::create($data);
                    $verificationIdsFromRequest[] = $newVer->id;
                }
            }

            // ✅ MARK OTHER VERIFICATIONS AS DELETED
            EmployeeVerification::where('employee_id', $employeeId)
                ->whereNotIn('id', $verificationIdsFromRequest)
                ->update(['is_deleted' => 1]);


            // ---------------------------
            // Employee Document Groups & Files
            $documentGroupIdsFromRequest = [];

            foreach ($request->documents ?? [] as $docGroup) {
                // Decode JSON if sent via FormData
                if (is_string($docGroup)) {
                    $docGroup = json_decode($docGroup, true);
                }

                if (!is_array($docGroup)) {
                    continue;
                }

                $data = [
                    'employee_id' => $employeeId,
                    'title'       => $docGroup['title'] ?? null,
                    'is_deleted'  => 0, // mark as active
                ];

                // ✅ UPDATE existing
                if (!empty($docGroup['document_group_id'])) {
                    $group = EmployeeDocumentGroup::where('id', $docGroup['document_group_id'])
                        ->where('employee_id', $employeeId)
                        ->first();

                    if ($group) {
                        $group->update($data);
                        $documentGroupIdsFromRequest[] = $group->id;
                    } else {
                        // If ID sent but not found, skip files
                        continue;
                    }
                }
                // ✅ CREATE new
                else {
                    $group = EmployeeDocumentGroup::create($data);
                    $documentGroupIdsFromRequest[] = $group->id;
                }

                // Process files
                $files = $docGroup['files'] ?? [];

                foreach ($files as $file) {

                    /**
                     * ✅ CASE 1: NEW FILE UPLOAD (UploadedFile directly)
                     */
                    if ($file instanceof UploadedFile) {

                        $originalName = $file->getClientOriginalName();
                        $filename = time() . '_' . preg_replace('/\s+/', '_', $originalName);
                        $file->move($docDir, $filename);

                        EmployeeDocument::create([
                            'document_group_id' => $group->id,
                            'file_path'         => 'uploads/emp_documents/' . $filename,
                            'original_name'     => $originalName,
                        ]);

                        continue;
                    }

                    /**
                     * ✅ CASE 2: JSON STRING (existing file)
                     */
                    if (is_string($file)) {
                        $file = json_decode($file, true);
                    }

                    if (!is_array($file)) {
                        continue;
                    }

                    /**
                     * ✅ EXISTING FILE UPDATE
                     */
                    if (!empty($file['document_id']) && !empty($file['path'])) {

                        EmployeeDocument::where('id', $file['document_id'])->update([
                            'file_path'     => $file['path'],
                            // 'original_name' => basename($file['path'])
                        ]);
                    }
                }
            }

            // ✅ MARK OTHER DOCUMENT GROUPS AS DELETED
            EmployeeDocumentGroup::where('employee_id', $employeeId)
                ->whereNotIn('id', $documentGroupIdsFromRequest)
                ->update(['is_deleted' => 1]);
        });

        return response()->json(['message' => 'Employee updated successfully']);
    }


    public function destroy($id)
    {
        // dd($id);
        $emp = Employee::where('id', $id)->where('is_deleted', '0')->firstOrFail();
        $emp->update(['is_deleted' => '1']);

        return response()->json(['success' => true, 'message' => 'Deleted successfully']);
    }

    public function jobreferalupdate(Request $request, $id)
    {
        $emp = Employee::where('id', $id)->where('is_deleted', 0)->firstOrFail();
        if ($request->type == 'external') {
            $emp->jb_referal = $request->job_form_ext_referal;
            $emp->save();
        } else {
            $emp->job_form_referal = $request->job_form_referal;
            $emp->save();
        }


        return response()->json(['success' => true, 'message' => 'Job form referal updated successfully']);
    }


    public function assigncompany(Request $request, $id)
    {
        $emp = Employee::where('id', $id)->where('is_deleted', 0)->firstOrFail();
        $emp->update([
            'company_id' => $request->company_id // array
        ]);

        return response()->json(['success' => true, 'message' => 'Job form referal updated successfully']);
    }

    public function getEmpidGenearate(Request $request)
    {
        $dateOfJoining = Carbon::parse($request->date_of_joining)->format('Ym');
        $prefix = 'pss' . $dateOfJoining;

        // Get last employee for SAME MONTH
        $lastEmployee = Employee::where('gen_employee_id', 'like', $prefix . '%')
            ->orderBy('gen_employee_id', 'desc')
            ->first();

        if ($lastEmployee) {
            // Extract numeric sequence AFTER prefix+date
            $lastNumber = (int) substr($lastEmployee->gen_employee_id, strlen($prefix));
            $nextNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = '001';
        }

        $newEmployeeId = $prefix . $nextNumber;

        return response()->json([
            'success' => true,
            'employee_id' => $newEmployeeId
        ]);
    }


    public function changepassword(Request $request)
    {
        $employeeid = $request->employee_id;
        $employee = Employee::find($employeeid);

        // ✅ Update hashed password
        $employee->password = Hash::make($request->password);
        $employee->save();

        return response()->json([
            'success' => true,
            'message' => 'Password updated successfully'
        ], 200);
    }
}
