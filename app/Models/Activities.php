<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activities extends Model
{
    use HasFactory;

    protected $table = 'activities';

    protected $fillable = [
        'created_by',
        'role_id',
        'reason',
        'type' // employee | contract
    ];

    // Regular Employee
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'created_by')
            ->with('role') // ✅ eager load role
            ->select('id', 'full_name', 'gen_employee_id', 'role_id');
    }

    public function attendance()
    {
        return $this->hasOne(PssEmployeeAttendance::class, 'employee_id', 'created_by')
            ->latest('id'); // ✅ simpler & safer than latestOfMany
    }


    // Contract Employee

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id')
            ->select('id', 'role_name');
    }
}
