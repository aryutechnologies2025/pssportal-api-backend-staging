<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $table='companies';
    
    protected $fillable = [
        'company_name',
        'address',
        'gst_number',
        'website_url',
        'phone_number',
        'target',
        'support_email',
        'billing_email',
        'status',
        'created_by',
        'is_deleted',
        'role_id',
        'prefix',
        'company_emp_id',
        'notes',
        'company_shift_id'
    ];

    public function contacts()
    {
        return $this->hasMany(ContactDetail::class, 'parent_id')
            ->where('parent_type', 'company');
    }

    public function notes()
    {
        return $this->hasMany(NoteAttachment::class, 'parent_id')
            ->where('parent_type', 'company');
    }

    public function latestNote()
    {
        return $this->hasOne(NoteAttachment::class, 'parent_id')
            ->where('parent_type', 'company')
            ->latest('created_at');
    }


    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'company_id');
    }

    public function contractEmployees()
    {
        return $this->hasMany(ContractEmployee::class, 'company_id');
    }

    public function shifts()
    {
        return $this->hasMany(CompanyShifts::class, 'parent_id')
            ->where('is_deleted', 0);
    }
}
