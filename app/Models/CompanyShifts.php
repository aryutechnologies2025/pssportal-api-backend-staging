<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyShifts extends Model
{
    use HasFactory;

    protected $table='companies_shift';

    protected $fillable = [
        'parent_id',
        'shift_name',
        'start_time',
        'end_time',
        'created_by',
        'is_deleted',
        'company_shift_id'
    ];
}
