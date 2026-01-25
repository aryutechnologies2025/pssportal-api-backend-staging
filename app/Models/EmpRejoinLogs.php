<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmpRejoinLogs extends Model
{
    use HasFactory;
    protected $table = 'emp_rejoining_logs';

    protected $fillable = [
        'parent_id',
        'company_id',
        'boarding_point_id',
        'address',
        'joining_date',
        'employee_id',
        'rejoining_note',
        'rejoin_status',
        'created_by',
    ];

    public function employee()
    {
        return $this->belongsTo(ContractCanEmp::class, 'parent_id')->select('id', 'name');
    }

    public function company()
    {
        return $this->belongsTo(Company::class)->select('id', 'company_name');
    }

    public function boardingPoint()
    {
        return $this->belongsTo(BoardingPoint::class)->select('id', 'point_name');
    }
}
