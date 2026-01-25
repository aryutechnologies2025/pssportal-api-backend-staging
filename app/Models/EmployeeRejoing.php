<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeRejoing extends Model
{
    use HasFactory;
    protected $table = 'employee_rejoings';

    protected $fillable = [
        'parent_id',
        'status',
        'notes',
    ];
}
