<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BoardingPoint extends Model
{
    use HasFactory;
    protected $table='boarding_points';

    protected $fillable = [
        'company_id',
        'point_name',
        'status',
        'is_deleted',
        'created_by',
        'updated_by',
    ];

      public function company() {
        return $this->belongsTo(Company::class, 'company_id')->select('id', 'company_name');
    }
}
