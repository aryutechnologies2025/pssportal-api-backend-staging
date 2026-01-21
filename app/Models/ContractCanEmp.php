<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractCanEmp extends Model
{
    use HasFactory;

    use HasFactory;

    protected $fillable = [
        'company_id',
        'name', //
        'address', //
        'phone_number', //
        'aadhar_number', //
        'joining_date', //
        'status',
        'created_by',
        'is_deleted',
        'date_of_birth', //
        'father_name', //
        'gender', //
        'address', //
        'acc_no', //
        'ifsc_code', //
        'uan_number', //
        'employee_id',
        'esic', //
        'profile_picture',
        'account_number'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id')->select('id', 'company_name');
    }

    public function notes()
    {
        return $this->hasMany(NoteAttachment::class, 'parent_id')
            ->where('parent_type', 'contract_emp');
    }

    public function documents()
    {
        return $this->hasMany(ContractEmployeeDocument::class, 'employee_id');
    }
}
