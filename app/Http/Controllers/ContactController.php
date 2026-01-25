<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\contact;
use Carbon\Carbon;

class ContactController extends Controller
{

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'         => 'required|string|max:150',
            'email'        => 'required|email|max:255',
            'phone_number' => 'nullable|string|max:20',
            'subject'      => 'required|string',
            'message'      => 'nullable|string',
        ]);

        // Validation failed
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors'  => $validator->errors()
            ], 422);
        }

        // Create contact
        $contact = contact::create($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Contact submitted successfully',
            'data'    => $contact
        ], 201);
    }

    public function index(Request $request)
    {
        // Base query
        $query = Contact::where('is_deleted', 0);

        // 📅 Date filter
        if ($request->filled('from_date') && $request->filled('to_date')) {
            $from = Carbon::parse($request->from_date)->startOfDay();
            $to   = Carbon::parse($request->to_date)->endOfDay();

            $query->whereBetween('created_at', [$from, $to]);
        }

        // 🏷 Subject filter
        if ($request->filled('subject')) {
            $query->where('subject', $request->subject);
            // OR use like search:
            // $query->where('subject', 'LIKE', '%' . $request->subject . '%');
        }

        // Execute query
        $contacts = $query
            ->orderBy('id', 'desc')
            ->get();

        // Subject dropdown list
        $subject = Contact::where('is_deleted', 0)
            ->select('subject')
            ->whereNotNull('subject')
            ->distinct()
            ->orderBy('subject')
            ->get();

        return response()->json([
            'success'  => true,
            'data'     => $contacts,
            'subjects' => $subject
        ], 200);
    }


    public function destroy($id)
    {
        $jobForm = contact::find($id);

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

    public function show(Request $request, $id)
    {
        $role = Contact::findOrFail($id);

        return response()->json([
            'status' => true,
            'data' => $role
        ]);
    }
}
