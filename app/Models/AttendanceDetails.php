<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceDetails extends Model
{
    use HasFactory;

    protected $table = 'attendance_details';

    protected $fillable = [
        'attendance_id',
        'employee_id',   // contract employee id
        'attendance',
        'shift_id'
    ];

    protected $casts = [
        'shift_id' => 'array',
    ];

    protected $appends = ['shifts'];

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    // ✅ ADD THIS
    public function contractEmployee()
    {
        return $this->belongsTo(ContractCanEmp::class, 'employee_id')->select('id', 'name');
    }


    public function getShiftsAttribute()
    {
        if (empty($this->shift_id)) {
            return [];
        }

        return CompanyShifts::whereIn('id', $this->shift_id)
            ->select('id', 'shift_name')
            ->get();
    }


    public function shiftDetails()
    {
        return $this->hasMany(
            AttendanceShiftDetails::class,
            'attendance_id',
            'id'
        );
    }
}
