<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\JobForm;
use App\Models\Remark;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class JobFormController extends Controller
{
    public function index(Request $request)
    {

        // Default values
        $limit = $request->get('limit', 10); // default 10
        $page  = $request->get('page', 1);   // default 1

        $query = JobForm::where('is_deleted', 0)->withCount(['remarks']);

        // 📅 Filter by created_at date
        if ($request->filled('from_date') && $request->filled('to_date')) {
            $from = Carbon::parse($request->from_date)->startOfDay();
            $to   = Carbon::parse($request->to_date)->endOfDay();

            $query->whereBetween('created_at', [$from, $to]);
        }

        if ($request->filled('reference')) {
            $query->where('reference', $request->reference);
        }

        // 🔎 Filter by district ✅ NEW
        if ($request->filled('district')) {
            $query->where('district', $request->district);
        }

        if ($request->filled('gender')) {
            $query->where('gender', $request->gender);
        }

        $data = $query
            ->orderBy('created_at', 'desc')
            ->paginate($limit, ['*'], 'page', $page);

        $reference = JobForm::where('is_deleted', 0)->select('reference')
            ->whereNotNull('reference')
            ->distinct()
            ->orderBy('reference')
            ->get();

        // District dropdown list ✅ NEW
        $district = JobForm::where('is_deleted', 0)
            ->select('district')
            ->whereNotNull('district')
            ->distinct()
            ->orderBy('district')
            ->get();
        $gender = JobForm::where('is_deleted', 0)->select('gender')
            ->whereNotNull('gender')
            ->distinct()
            ->orderBy('gender')
            ->get();

        return response()->json([
            'success' => true,
            'count'   => $data->count(),
            'data'    => $data->items(), // only current page records
            'meta'    => [
                'current_page' => $data->currentPage(),
                'per_page'     => $data->perPage(),
                'total'        => $data->total(),
                'last_page'    => $data->lastPage(),
            ],
            'reference' => $reference,
            'district'  => $district,
            'gender' => $gender
        ]);
    }

    public function pssEnquirystore(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'aadhar_number' => [
                'required',
                'digits:12',
                Rule::unique('job_forms', 'aadhar_number')
                    ->where(function ($query) {
                        return $query->where('is_deleted', 0);
                    }),
            ],
        ], [
            'aadhar_number.unique' => 'Aadhar number already exists.',
            'aadhar_number.digits' => 'Aadhar number must be 12 digits.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()
            ], 422);
        }


        // Check if Aadhar exists (only active records)
        // $existing = JobForm::where('aadhar_number', $request->aadhar_number)
        //     ->where('is_deleted', 0)
        //     ->first();

        // if ($existing) {
        //     // Aadhar already exists → return success message
        //     return response()->json([
        //         'success' => true,
        //         'message' => 'Contact Enquiry submitted successfully',
        //         'data' => $existing
        //     ]);
        // }


        // Create a new CourseEnquiry record
        $course = new JobForm;
        $course->name = $request->name;
        $course->email_id = $request->email_id;
        $course->contact_number = $request->contact_number;
        $course->aadhar_number = $request->aadhar_number;
        $course->date_of_birth = $request->date_of_birth;
        $course->gender = $request->gender;
        $course->marital_Status = $request->marital_Status;
        $course->education = $request->education;
        $course->major = $request->major;
        $course->city = $request->city;
        $course->district = $request->district;
        $course->reference = $request->reference;
        $course->remarks = $request->remarks;
        $course->save();
        // Send email notifications
        // try {
        //     Log::info('Mail check: '.$course->email);
        //     // Send email to the client
        //     Mail::to($course->email)->send(new StudentEmail([
        //         'name' => $course->name,
        //         'service' => $course->service,

        //     ]));

        //     // Send email to admin
        //     Mail::to('yuvaraj@aryuenterprises.com')->send(new CompanyEmail([
        //         'name' => $course->name,
        //         'email' => $course->email,
        //         'number' => $course->phone,
        //         'message' => $course->message,
        //         'service' => $course->service,

        //     ]));
        // } catch (\Exception $e) {
        //     // Log any email sending errors
        //     Log::error('Mail failed: '.$e->getMessage());
        // }

        // Return a JSON response
        return response()->json([
            'success' => true,
            'message' => 'Contact Enquiry submitted successfully',

        ]);
    }

    public function destroy($id)
    {
        $jobForm = JobForm::find($id);

        if (!$jobForm) {
            return response()->json([
                'success' => false,
                'message' => 'Record not found'
            ], 404);
        }

        $jobForm->is_deleted = 1;
        $jobForm->save();

        return response()->json([
            'success' => true,
            'message' => 'Record deleted successfully'
        ]);
    }

    public function show($id)
    {
        $jobform = JobForm::with('remarks')
            ->where('id', $id)
            ->where('is_deleted', 0)
            ->first();

        if (!$jobform) {
            return response()->json([
                'success' => false,
                'message' => 'Form view not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $jobform
        ]);
    }

    public function export(Request $request)
    {
        $query = JobForm::where('is_deleted', 0);

        if ($request->filled('from_date') && $request->filled('to_date')) {
            $query->whereBetween('created_at', [
                Carbon::parse($request->from_date)->startOfDay(),
                Carbon::parse($request->to_date)->endOfDay()
            ]);
        }

        // ✅ keep relationship name exactly as-is
        $records = $query
            ->with('remarks')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->stream(function () use ($records) {

            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'S.No',
                'Name',
                'Email',
                'Contact',
                'Gender',
                'District',
                'Referred By',
                'Register On',
                'Remarks'
            ]);

            foreach ($records as $index => $row) {

                // ✅ SAFELY fetch relationship (NOT column)
                $remarksCollection = $row->getRelationValue('remarks');

                $remarksText = collect($remarksCollection)
                    ->pluck('notes')
                    ->implode(' | ');

                fputcsv($handle, [
                    $index + 1,
                    $row->name,
                    $row->email_id,
                    $row->contact_number,
                    $row->gender,
                    $row->district,
                    $row->reference,
                    optional($row->created_at)->format('d-m-Y'),
                    $remarksText
                ]);
            }

            fclose($handle);

        }, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="job_forms.csv"',
        ]);
    }
}
