<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Eductions extends Model
{
    use HasFactory;
    
    protected $table = 'eductions';

    protected $fillable = [
        'eduction_name',
        'status',
        'is_deleted',
        'created_by',
        'updated_by',
    ];
    
}
