<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceShiftDetails extends Model
{
    use HasFactory;
    protected $table = 'attendance_shift_details';

    protected $fillable = [
        'attendance_id',
        'employee_id',
        'shift_id',
        'start_time',
        'end_time',
    ];

    public function shift()
    {
        return $this->belongsTo(CompanyShifts::class, 'shift_id')
            ->select('id', 'shift_name');
    }
}
