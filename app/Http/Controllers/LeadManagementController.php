<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LeadManagement;
use App\Models\LeadManagementCategory;
use Carbon\Carbon;
use App\Models\LeadManagementNote;

class LeadManagementController extends Controller
{
    /**
     * List Leads
     */
    public function index(Request $request)
    {
        $query = LeadManagement::where('is_deleted', '0');

        // Optional filters
        // if ($request->filled('gender')) {
        //     $query->where('gender', $request->lead_status);
        // }
        if ($request->filled('gender')) {
        $query->where('gender', $request->gender);
        }

        if ($request->filled('platform')) {
            $query->where('platform', $request->platform);
        }

        // // ✅ Date range filter
        // $query->when(
        //     $request->filled('from_date') && $request->filled('to_date'),
        //     function ($q) use ($request) {
        //         $from = Carbon::parse($request->from_date)->startOfDay();
        //         $to   = Carbon::parse($request->to_date)->endOfDay();

        //         $q->whereBetween('created_at', [$from, $to]);
        //     }
        // );
        $query->when(
        $request->filled('from_date') && $request->filled('to_date'),
        function ($q) use ($request) {
        $q->whereBetween('created_at', [
            Carbon::parse($request->from_date)->startOfDay(),
            Carbon::parse($request->to_date)->endOfDay(),
            ]);
        }
        );
        $data = $query->latest()->get();

        $lead_categories = LeadManagementCategory::where('is_deleted', '0') ->select('id', 'name')->get();
        $age = LeadManagement::where('is_deleted', '0')
            ->whereNotNull('age')
            ->pluck('age')
            ->unique()
            ->values()
            ->toArray();

        $cities = LeadManagement::where('is_deleted', '0')
            ->whereNotNull('city')
            ->pluck('city')
            ->unique()
            ->values()
            ->toArray();

        $platforms = LeadManagement::where('is_deleted', '0')
            ->select('platform')
            ->distinct()
            ->get()
            ->mapWithKeys(function ($item) {
                return [
                    $item->platform => match ($item->platform) {
                        'ig' => 'Instagram',
                        'fb' => 'Facebook',
                        'portal' => 'Portal',
                        default => ucfirst($item->platform),
                    }
                ];
            });


        return response()->json([
            'success' => true,
            'data'    => $data,
            'platforms' => $platforms,
            'age' => $age,
            'cities' => $cities,
            'lead-category' => $lead_categories
        ]);
    }

    /**
     * Add Lead
     */
    public function store(Request $request)
    {
        // 🔹 Normalize phone

        $phone = $this->normalizePhone($request->phone);
        // 🔍 Check existing lead
        $existingLead = LeadManagement::where('phone', $phone)
            ->where('is_deleted', '0')
            ->first();

        if ($existingLead) {
            return response()->json([
                'success' => false,
                'message' => 'Phone number already exists',
                'existing_lead_id' => $existingLead->id
            ], 409); // Conflict
        }

        $data = $request->all();
        $date_of_birth  = $this->parseDate($data['date_of_birth'] ?? null);
        $age = null;

        if ($date_of_birth) {
            try {
                $age = Carbon::parse($date_of_birth)->age; // auto calculates from today
            } catch (\Exception $e) {
                $age = null;
            }
        }
        $data['phone']        = $phone;
        $data['age']          = $age;
        $data['platform']     = 'portal'; // ✅ static
        $data['lead_status'] = $data['lead_status'] ?? 'open';
        $data['is_organic'] = $data['is_organic'];
        $data['created_time'] = date('Y-m-d H:i:s');
        $data['created_by'] = $request->created_by ?? null;

        $lead = LeadManagement::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Lead created successfully',
            'data'    => $lead
        ]);
    }

    /**
     * View Single Lead
     */
    public function show($id)
    {
        $lead = LeadManagement::where('id', $id)
            ->where('is_deleted', '0')
            ->first();

        if (!$lead) {
            return response()->json([
                'success' => false,
                'message' => 'Lead not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $lead
        ]);
    }

    /**
     * Update Lead
     */
    public function update(Request $request, $id)
    {
        $lead = LeadManagement::where('id', $id)
            ->where('is_deleted', '0')
            ->first();

        if (!$lead) {
            return response()->json([
                'success' => false,
                'message' => 'Lead not found'
            ], 404);
        }

        $data = $request->all();
        $date_of_birth  = $this->parseDate($data['date_of_birth'] ?? null);
        $age = null;

        if ($date_of_birth) {
            try {
                $age = Carbon::parse($date_of_birth)->age; // auto calculates from today
            } catch (\Exception $e) {
                $age = null;
            }
        }
        $data['age']          = $age;
        $data['updated_by'] = $request->updated_by ?? null;

        $lead->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Lead updated successfully',
            'data'    => $lead
        ]);
    }


    private function normalizePhone($rawPhone)
    {
        $phone = preg_replace('/[^0-9]/', '', $rawPhone);

        if (strlen($phone) > 10 && substr($phone, 0, 2) === '91') {
            $phone = substr($phone, -10);
        }

        return '91' . $phone;
    }

    /**
     * Delete Lead (Soft Delete)
     */
    public function destroy($id)
    {
        $lead = LeadManagement::find($id);

        if (!$lead) {
            return response()->json([
                'success' => false,
                'message' => 'Lead not found'
            ], 404);
        }

        $lead->update([
            'is_deleted' => '1'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Lead deleted successfully'
        ]);
    }


    public function import(Request $request)
    {
        $file = $request->file('file');

        if (!$file) {
            return response()->json([
                'success' => false,
                'message' => 'CSV file is required'
            ], 422);
        }

        $content = file_get_contents($file->getRealPath());

        // 🔹 More robust encoding detection and conversion
        $encoding = mb_detect_encoding($content, ['UTF-8', 'UTF-16LE', 'UTF-16BE', 'ISO-8859-1', 'Windows-1252'], true);
        if ($encoding && $encoding !== 'UTF-8') {
            $content = mb_convert_encoding($content, 'UTF-8', $encoding);
        }

        // 🔹 Final fallback: Sanitize non-UTF8 characters just in case
        $content = mb_convert_encoding($content, 'UTF-8', 'UTF-8');

        // 🔹 Use temp stream
        $temp = fopen('php://temp', 'r+');
        fwrite($temp, $content);
        rewind($temp);

        // 🔹 Auto-detect Delimiter (, or \t)
        $firstLine = fgets($temp);
        rewind($temp);
        $delimiter = (strpos($firstLine, "\t") !== false && strpos($firstLine, ",") === false) ? "\t" : ",";

        $header = fgetcsv($temp, 0, $delimiter);
        if (!$header) {
            return response()->json(['success' => false, 'message' => 'CSV file is empty'], 422);
        }

        // 🔹 Clean & Standardize Headers
        $header = array_map(function ($h) {
            $h = preg_replace('/^\xEF\xBB\xBF/', '', $h); // Remove BOM
            $h = preg_replace('/[\x00-\x1F\x7F]/', '', $h); // Remove non-printable
            return strtolower(trim(str_replace(' ', '_', $h)));
        }, $header);

        $inserted = 0;
        $skipped  = 0;

        while (($row = fgetcsv($temp, 0, $delimiter)) !== false) {
            if (count($header) !== count($row)) {
                $skipped++;
                continue;
            }

            // Clean row data (remove potential encoding issues per cell)
            $row = array_map(function($val) {
                return $val ? mb_convert_encoding($val, 'UTF-8', 'UTF-8') : $val;
            }, $row);

            $data = array_combine($header, $row);

            // 🔹 Clean & Normalize phone
            $rawPhone = $data['phone'] ?? $data['phone_number'] ?? $data['contact'] ?? null;
            if (!$rawPhone) {
                $skipped++;
                continue;
            }

            // Handle scientific notation (e.g. 9.87E+09)
            if (is_numeric($rawPhone) && strpos(strtolower($rawPhone), 'e+') !== false) {
                $rawPhone = number_format((float)$rawPhone, 0, '', '');
            }

            $phone = $this->normalizePhone($rawPhone);

            // 🔍 Check existing by phone
            $exists = LeadManagement::where('phone', $phone)
                ->where('is_deleted', '0')
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            $date_of_birth  = $this->parseDate($data['date_of_birth'] ?? $data['dob'] ?? null);
            $parsedCreatedTime = $data['created_time'] ?? $data['created_at'] ?? null;
            $createdTime    = $parsedCreatedTime ? $this->parseDateTime($parsedCreatedTime) : date('Y-m-d H:i:s');

            if (!$createdTime) $createdTime = date('Y-m-d H:i:s');

            // 🔹 Normalize gender
            $rawGender = strtolower(trim($data['gender'] ?? ''));
            if (in_array($rawGender, ['male', 'm'])) {
                $gender = 'male';
            } elseif (in_array($rawGender, ['female', 'f'])) {
                $gender = 'female';
            } else {
                $gender = 'other';
            }

            $age = null;
            if ($date_of_birth) {
                try {
                    $age = Carbon::parse($date_of_birth)->age;
                } catch (\Exception $e) {
                    $age = null;
                }
            }

            LeadManagement::create([
                'lead_id'       => $data['id'] ?? $data['lead_id'] ?? null,
                'created_time'  => $createdTime,
                'ad_id'         => $data['ad_id'] ?? null,
                'ad_name'       => $data['ad_name'] ?? null,
                'adset_id'      => $data['adset_id'] ?? null,
                'adset_name'    => $data['adset_name'] ?? null,
                'campaign_id'   => $data['campaign_id'] ?? null,
                'campaign_name' => $data['campaign_name'] ?? null,
                'form_id'       => $data['form_id'] ?? null,
                'form_name'     => $data['form_name'] ?? null,
                'is_organic'    => isset($data['is_organic']) ? ($data['is_organic'] == 'true' || $data['is_organic'] == 1 ? 1 : 0) : 0,
                'platform'      => $data['platform'] ?? 'csv_import',
                'full_name'     => $data['full_name'] ?? $data['name'] ?? null,
                'gender'        => $gender,
                'phone'         => $phone,
                'date_of_birth' => $date_of_birth,
                'post_code'     => $data['post_code'] ?? $data['pincode'] ?? $data['zip'] ?? null,
                'city'          => $data['city'] ?? null,
                'state'         => $data['state'] ?? null,
                'age'           => $age,
                'lead_status'   => 'open',
                'status'        => 1,
                'is_deleted'    => '0',
                'created_by'    => $request->created_by,
            ]);

            $inserted++;
        }

        fclose($temp);

        return response()->json([
            'success'  => true,
            'message'  => 'Lead CSV import completed',
            'inserted' => $inserted,
            'skipped'  => $skipped,
        ]);
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

    private function parseDateTime($value)
    {
        if (empty($value)) {
            return null;
        }

        try {
            // If numeric timestamp (seconds or milliseconds)
            if (is_numeric($value)) {
                return strlen($value) > 10
                    ? Carbon::createFromTimestampMs($value)->format('Y-m-d H:i:s')
                    : Carbon::createFromTimestamp($value)->format('Y-m-d H:i:s');
            }

            // Try automatic parsing (ISO, FB formats, etc.)
            return Carbon::parse($value)->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            return null; // fail-safe
        }
    }

    public function statusUpdate(Request $request, $id)
    {
        $lead = LeadManagement::where('id', $id)
            ->where('is_deleted', '0')
            ->first();

        if (!$lead) {
            return response()->json([
                'success' => false,
                'message' => 'Lead not found'
            ], 404);
        }

        // 🔹 Update Lead Status
        $lead->lead_status = $request->lead_status;
        $lead->save();

        LeadManagementNote::create([
            'parent_id'       => $id,
            'notes'           => $request->notes,
            'status'          => $request->lead_status,
            'followup_status' => $request->followup_status,
            'scheduled_date' => $request->scheduled_date,
            'followup_date'   => $request->followup_date
                ? Carbon::parse($request->followup_date)->format('Y-m-d')
                : null,
            'created_by'      => $request->created_by ?? null,
        ]);


        return response()->json([
            'success' => true,
            'message' => 'Lead status updated & note added successfully'
        ]);
    }

    public function statusList(Request $request, $id)
    {
        $leads = LeadManagement::where('id', $id)->with('notes')
            ->select('lead_status', 'id')
            ->where('is_deleted', '0')
            ->first();

        return response()->json([
            'success' => true,
            'leadstatus' => $leads
        ]);
    }
}
