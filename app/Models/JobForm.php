<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobForm extends Model
{
    use HasFactory;

    protected $table = 'job_forms';
    

    public function remarks()
    {
        return $this->hasMany(Remark::class, 'parent_id', 'id')
            ->where('is_deleted', 0);
    }
}
