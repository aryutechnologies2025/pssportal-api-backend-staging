<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
class LeadManagement extends Model
{
    use HasFactory;

    protected $table = 'lead_management';

    protected $fillable = [
        'lead_id',
        'created_time',
        'ad_id',
        'ad_name',
        'adset_id',
        'adset_name',
        'campaign_id',
        'campaign_name',
        'form_id',
        'form_name',
        'is_organic',
        'platform',
        'full_name',
        'gender',
        'phone',
        'date_of_birth',
        'post_code',
        'city',
        'state',
        'lead_status',
        'status',
        'created_by',
        'updated_by',
        'is_deleted',
        'age'
    ];

    public function notes()
    {
        return $this->hasMany(LeadManagementNote::class, 'parent_id')
            ->select(
                'parent_id',
                'notes',
                'followup_status',
                'scheduled_date',
                'followup_date',
                'status',
                DB::raw("DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') as created_at")
            )->orderBy('created_at', 'desc');
    }
}
