<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkReport extends Model
{
    use HasFactory;

    protected $table ='work_reports';

    protected $fillable = ['report', 'report_date', 'created_by'];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'created_by')->select('full_name', 'id', 'gen_employee_id');
    }
}
