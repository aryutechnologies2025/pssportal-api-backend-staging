<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Finance extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'branch_id',
        'date',
        'amount',
        'description',
        'bill',
        'status',
        'is_deleted',
        'created_by',
        'updated_by',
    ];

    public function company()
    {
        return $this->belongsTo(PssCompany::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
