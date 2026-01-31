<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadManagementCategory extends Model
{
    use HasFactory;

    protected $table = 'lead_management_categories';

    protected $fillable = [
        'name',
        'status',
        'is_deleted',
        'created_by',
        'updated_by',
    ];
}
