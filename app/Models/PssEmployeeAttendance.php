<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PssEmployeeAttendance extends Model
{
    use HasFactory;

    protected $table = 'pss_employee_attendances';

    protected $fillable = [
        'employee_id',
        'attendance_date',
        'attendance_time',
        'shift_id',
        'reason',
        'photo',
        'latitude',
        'longitude',
    ];



    public function shift()
    {
        return $this->belongsTo(PssWorkShift::class, 'shift_id')->select('shift_name', 'id');
    }


    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id')->select('full_name', 'id', 'gen_employee_id');
    }
}
