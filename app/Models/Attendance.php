<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $table = 'attendances';

    protected $fillable = [
        'company_id',
        'attendance_date',
        'status',
        'created_by',
        'updated_by',
        'is_deleted',
        'role_id'
    ];

    protected $casts = [
        'shift_id' => 'array',
    ];

    public function details()
    {
        return $this->hasMany(AttendanceDetails::class, 'attendance_id')
            ->with([
                'contractEmployee',
                'shiftDetails.shift' // nested relation
            ]);
    }



    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'created_by')->select('full_name', 'id');
    }

    public function shifts()
    {
        return $this->hasMany(CompanyShifts::class, 'parent_id')
            ->where('is_deleted', 0);
    }
}
