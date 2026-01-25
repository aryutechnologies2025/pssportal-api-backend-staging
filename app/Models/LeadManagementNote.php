<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadManagementNote extends Model
{
    use HasFactory;

    protected $table = 'lead_management_notes';

    protected $fillable = [
        'parent_id',
        'notes',
        'status',
        'followup_status',
        'followup_date',
        'created_by',
        'scheduled_date'
    ];

    public function getCreatedAtAttribute($value)
    {
        return \Carbon\Carbon::parse($value)->format('Y-m-d H:i:s');
    }
}
