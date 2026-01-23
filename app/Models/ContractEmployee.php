<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractEmployee extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'address',
        'phone_number',
        'aadhar_number',
        'interview_date',
        'interview_status',
        'joining_date',
        'joining_status',
        'reference',
        'status',
        'created_by',
        'is_deleted',
        'role_id',
        'other_reference',
        'joined_date',
        'education',
        'profile_picture',
        'education_id',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }


    public function education()
    {
        return $this->belongsTo(Eductions::class, 'education_id');
    }
    

    public function notes()
    {
        return $this->hasMany(NoteAttachment::class, 'parent_id')
            ->where('parent_type', 'contract_emp');
    }

    public function documents()
    {
        return $this->hasMany(ContractCandidateDocument::class, 'employee_id');
    }
}
