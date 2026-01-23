<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    use HasFactory;

    protected $table = 'announcements';

    protected $fillable = [
        'start_date',
        'expiry_date',
        'announcement_details',
        'visible_to',
        'status',
        'is_deleted',
        'created_by',
        'updated_by',
    ];
}
