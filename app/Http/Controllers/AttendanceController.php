<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;

use App\Models\AttendanceDetails;
use App\Models\ContractCanEmp;
use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\Employee;
use App\Models\CompanyShifts;
use Carbon\Carbon;
use App\Models\AttendanceShiftDetails;

class AttendanceController extends Controller
{
    /**
     * CREATE ATTENDANCE
     */
    public function store(Request $request)
    {

        // dd($request->all());
        // $validator = Validator::make($request->all(), [
        //     'company_name'     => 'required|string',
        //     'attendance_date'  => 'required|date',
        //     'employees'        => 'required|array',
        //     'employees.*.employee_id' => 'required|exists:employees,id',
        //     'employees.*.attendance'  => 'required|in:0,1'
        // ]);

        // if ($validator->fails()) {
        //     return response()->json([
        //         'success' => false,
        //         'errors'  => $validator->errors()
        //     ], 422);
        // }

        $alreadyExists = Attendance::where('company_id', $request->company_id)
            ->where('is_deleted', 0)
            ->whereDate('attendance_date', $request->attendance_date)
            ->exists();

        if ($alreadyExists) {
            return response()->json([
                'success' => false,
                'errors' => [
                    'attendance_date' => [
                        'Attendance for this company on this date already exists.'
                    ]
                ]
            ], 422);
        }


        DB::beginTransaction();

        try {
            $attendance = Attendance::create([
                'company_id'    => $request->company_id,
                'attendance_date' => $request->attendance_date,
                'created_by'      => $request->created_by,
            ]);

            foreach ($request->employees as $emp) {
                $attendanceDetail = AttendanceDetails::create([
                    'attendance_id' => $attendance->id,
                    'employee_id'   => $emp['employee_id'], // contract employee id
                    'attendance'    => $emp['attendance'],
                    // 'shift_id'      => $emp['shift_id']
                ]);

                if (!empty($emp['shift_details']) && is_array($emp['shift_details'])) {
                    foreach ($emp['shift_details'] as $shift) {
                        AttendanceShiftDetails::create([
                            'attendance_id' => $attendanceDetail->id,
                            'employee_id'   => $emp['employee_id'],
                            'shift_id'      => $shift['shift_id'],
                            'start_time' => isset($shift['start_time']) ? Carbon::parse($shift['start_time'])->format('H:i:s') : null,
                            'end_time' => isset($shift['end_time']) ? Carbon::parse($shift['end_time'])->format('H:i:s') : null,

                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Contract employee attendance added successfully'
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * VIEW ALL ATTENDANCE
     */
    public function index(Request $request)
    {
        $query = Attendance::with([
            'company:id,company_name',
            'company.shifts',
            'details',
            'employee'
        ])->where('is_deleted', 0);

        // Date filter
        if ($request->filled('from_date') && $request->filled('to_date')) {
            $from = Carbon::parse($request->from_date)->startOfDay();
            $to   = Carbon::parse($request->to_date)->endOfDay();

            $query->whereBetween('attendance_date', [$from, $to]);
        }

        // Company filter
        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        // Created by / Updated by filter (proper grouping)
        if ($request->filled('created_by')) {
            $query->where(function ($q) use ($request) {
                $q->where('created_by', $request->created_by)
                    ->orWhere('updated_by', $request->created_by);
            });
        }

        $data = $query->latest()->get();

        // Companies dropdown
        $companies = Company::where('status', 1)
            ->where('is_deleted', 0)
            ->select('id', 'company_name')
            ->latest()
            ->get();

        // Created by dropdown
        $createdby = Employee::where('status', 1)
            ->where('is_deleted', 0)
            ->where('id', '!=', 1)
            ->select('id', 'full_name')
            ->latest()
            ->get();

        return response()->json([
            'success'   => true,
            'data'      => $data,
            'companies' => $companies,
            'createdby' => $createdby
        ]);
    }

    /**
     * VIEW SINGLE ATTENDANCE
     */
    // public function show($id)
    // {
    //     $attendance = Attendance::with([
    //         'company:id,company_name',
    //         'details.contractEmployee',
    //         'shifts'
    //     ])->findOrFail($id);

    //     $shifts = CompanyShifts::where('parent_id', $attendance->company_id)
    //         ->where('is_deleted', 0)
    //         ->get();

    //     return response()->json([
    //         'success' => true,
    //         'data'    => $attendance,
    //         'shifts' => $shifts
    //     ]);
    // }

    public function show($id)
    {
        $attendance = Attendance::with([
            'company:id,company_name',
            'details.contractEmployee',
            'shifts',
            'details.shiftDetails.shift'
        ])->findOrFail($id);

        $attendance->details->each(function ($detail) {
        if ($detail->shiftDetails) {
        $detail->shiftDetails->each(function ($sd) {
            $sd->start_time_display = $sd->start_time
                ? Carbon::parse($sd->start_time)->format('h:i A')
                : null;
            $sd->end_time_display = $sd->end_time
                ? Carbon::parse($sd->end_time)->format('h:i A')
                : null;
            });
            }
        });

        // ✅ Attendance counts
        $presentCount = $attendance->details
            ->where('attendance', '1')   // or 'present'
            ->count();

        $absentCount = $attendance->details
            ->where('attendance', '0')   // or 'absent'
            ->count();

        $notMarkedCount = $attendance->details
            ->whereNull('attendance')
            ->count();

        $shifts = CompanyShifts::where('parent_id', $attendance->company_id)
            ->where('is_deleted', 0)
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $attendance,
            'shifts'  => $shifts,
            'counts'  => [
                'present'    => $presentCount,
                'absent'     => $absentCount,
                'not_marked' => $notMarkedCount,
                'total'      => $attendance->details->count()
            ]
        ]);
    }


    /**
     * UPDATE ATTENDANCE
     */
    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $attendance = Attendance::findOrFail($id);

            $attendance->update([
                'company_id'    => $request->company_id,
                'attendance_date' => $request->attendance_date,
                'updated_by'      => $request->updated_by,
            ]);

            AttendanceDetails::where('attendance_id', $id)->delete();

            foreach ($request->employees as $emp) {
                $attendanceDetail = AttendanceDetails::create([
                    'attendance_id' => $attendance->id,
                    'employee_id'   => $emp['employee_id'], // contract employee id
                    'attendance'    => $emp['attendance'],
                    // 'shift_id'      => $emp['shift_id']
                ]);

                if (!empty($emp['shift_details']) && is_array($emp['shift_details'])) {
                    foreach ($emp['shift_details'] as $shift) {
                        AttendanceShiftDetails::create([
                            'attendance_id' => $attendanceDetail->id,
                            'employee_id'   => $emp['employee_id'],
                            'shift_id'      => $shift['shift_id'],
                            'start_time' => isset($shift['start_time']) ? Carbon::parse($shift['start_time'])->format('H:i:s') : null,
                            'end_time' => isset($shift['end_time']) ? Carbon::parse($shift['end_time'])->format('H:i:s') : null,
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Contract employee attendance updated successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * DELETE ATTENDANCE (SOFT DELETE)
     */
    public function destroy($id)
    {
        Attendance::where('id', $id)->update(['is_deleted' => 1]);

        return response()->json([
            'success' => true,
            'message' => 'Attendance deleted successfully'
        ]);
    }

    public function getCompanyEmployees($company_id)
    {
        $employees = ContractCanEmp::where('company_id', $company_id)
            ->where('status', 1)
            ->where('is_deleted', 0)
            ->whereNotNull('joining_date')
            ->whereDate('joining_date', '<=', Carbon::today())
            ->get(['id', 'name']);

        $shifts = CompanyShifts::where('parent_id', $company_id)
            ->where('is_deleted', 0)
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $employees,
            'shifts'  => $shifts
        ]);
    }

    // public function import(Request $request)
    // {
    //     $alreadyExists = Attendance::where('company_id', $request->company_id)
    //         ->where('is_deleted', 0)
    //         ->whereDate('attendance_date', $request->attendance_date)
    //         ->exists();

    //     if ($alreadyExists) {
    //         return response()->json([
    //             'success' => false,
    //             'errors' => [
    //                 'attendance_date' => [
    //                     'Attendance for this company on this date already exists.'
    //                 ]
    //             ]
    //         ], 422);
    //     }

    //     $file = $request->file('file');
    //     $handle = fopen($file->getRealPath(), 'r');

    //     DB::beginTransaction();
    //     try {
    //         $header = fgetcsv($handle); // CSV header

    //         // 🔒 Prevent duplicate attendance
    //         $alreadyExists = Attendance::where('company_id', $request->company_id)
    //             ->whereDate('attendance_date', $request->attendance_date)
    //             ->where('is_deleted', 0)
    //             ->exists();

    //         if ($alreadyExists) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Attendance already exists for this date'
    //             ], 422);
    //         }

    //         $attendance = Attendance::create([
    //             'company_id' => $request->company_id,
    //             'attendance_date' => $request->attendance_date,
    //             'created_by' => $request->created_by,
    //         ]);

    //         $inserted = 0;
    //         $skipped  = 0;

    //         while (($row = fgetcsv($handle)) !== false) {

    //             $data = array_combine($header, $row);

    //             // 🔹 Find contract employee
    //             $employee = ContractCanEmp::where('employee_id', $data['employee_id'])
    //                 ->where('company_id', $request->company_id)
    //                 ->where('is_deleted', 0)
    //                 ->first();

    //             if (!$employee) {
    //                 $skipped++;
    //                 continue;
    //             }

    //             // // 🔹 Parse multiple shift codes from CSV
    //             // $shiftCodes = array_map('trim', explode(',', $data['company_shift']));

    //             // // 🔹 Fetch all matching shifts
    //             // $companyShifts = CompanyShifts::whereIn('company_shift_id', $shiftCodes)
    //             //     ->where('parent_id', $request->company_id)
    //             //     ->where('is_deleted', 0)
    //             //     ->pluck('id')
    //             //     ->toArray();

    //             // if (empty($companyShifts)) {
    //             //     $skipped++;
    //             //     continue;
    //             // }

    //             // 🔹 Normalize attendance value
    //             $rawAttendance = strtolower(trim($data['attendance']));

    //             if (in_array($rawAttendance, ['present', 'presend', 'p', 'P', '1'])) {
    //                 $attendanceStatus = 1;
    //             } elseif (in_array($rawAttendance, ['absent', 'a', 'A', '0'])) {
    //                 $attendanceStatus = 0;
    //             } else {
    //                 $skipped++;
    //                 continue;
    //             }

    //             AttendanceDetails::create([
    //                 'attendance_id' => $attendance->id,
    //                 'employee_id'   => $employee->id, // ✅ correct ID
    //                 'attendance'    => $attendanceStatus,
    //                 // 'shift_id'      => $companyShifts
    //             ]);

    //             $inserted++;
    //         }

    //         fclose($handle);
    //         DB::commit();

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Attendance imported successfully',
    //             'inserted' => $inserted,
    //             'skipped' => $skipped
    //         ]);
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return response()->json(['error' => $e->getMessage()], 500);
    //     }
    // }

    public function import(Request $request)
    {
        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');

        DB::beginTransaction();

        try {
            $header = fgetcsv($handle);

            $inserted = 0;
            $updated  = 0;
            $skipped  = 0;

            while (($row = fgetcsv($handle)) !== false) {

                $data = array_combine($header, $row);

                // 1️⃣ Validate date
                if (empty($data['attendance_date'])) {
                    $skipped++;
                    continue;
                }


                $attendance_date  = $this->parseDate($data['attendance_date'] ?? null);

                // 2️⃣ Find or create attendance (company + date)
                $attendance = Attendance::firstOrCreate(
                    [
                        'company_id'       => $request->company_id,
                        'attendance_date'  => $attendance_date,
                        'is_deleted'       => 0
                    ],
                    [
                        'created_by' => $request->created_by
                    ]
                );

                // 3️⃣ Find employee
                $employee = ContractCanEmp::where('employee_id', $data['employee_id'])
                    ->where('company_id', $request->company_id)
                    ->where('is_deleted', 0)
                    ->first();

                if (!$employee) {
                    $skipped++;
                    continue;
                }

                // 4️⃣ Normalize attendance
                $rawAttendance = strtolower(trim($data['attendance']));

                if (in_array($rawAttendance, ['present', 'p', 'P', '1'])) {
                    $attendanceStatus = 1;
                } elseif (in_array($rawAttendance, ['absent', 'a', 'A', '0'])) {
                    $attendanceStatus = 0;
                } else {
                    $skipped++;
                    continue;
                }

                // 5️⃣ Insert or update attendance details
                $detail = AttendanceDetails::updateOrCreate(
                    [
                        'attendance_id' => $attendance->id,
                        'employee_id'   => $employee->id,
                    ],
                    [
                        'attendance' => $attendanceStatus
                    ]
                );

                $detail->wasRecentlyCreated ? $inserted++ : $updated++;
            }

            fclose($handle);
            DB::commit();

            return response()->json([
                'success'  => true,
                'message'  => 'Attendance imported successfully',
                'inserted' => $inserted,
                'updated'  => $updated,
                'skipped'  => $skipped
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
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
}
