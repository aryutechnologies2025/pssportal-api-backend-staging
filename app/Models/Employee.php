<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $fillable = [
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
        'offical_email',
        'password',
        'role_id',
        'job_form_referal',
        'company_id',
        'branch_id',
        'date_of_joining',
        'gen_employee_id',
        'is_deleted',
        'jp_referal'
    ];

    protected $casts = [
        'skills' => 'array',
        'date_of_birth' => 'date',
        'effective_date' => 'date',
        'company_id' => 'array',
    ];

    // Relationships
    public function contacts()
    {
        return $this->hasMany(ContactDetail::class, 'parent_id')
            ->where('is_deleted', 0);
    }

    public function educations()
    {
        return $this->hasMany(EmployeeEducation::class)
            ->where('is_deleted', 0);
    }

    public function experiences()
    {
        return $this->hasMany(EmployeeExperience::class)
            ->where('is_deleted', 0);
    }

    public function documentGroups()
    {
        return $this->hasMany(EmployeeDocumentGroup::class)
            ->where('is_deleted', 0);
    }

    public function verifications()
    {
        return $this->hasMany(EmployeeVerification::class)
            ->where('is_deleted', 0);
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id', 'id')->select('id','role_name');
    }
}
