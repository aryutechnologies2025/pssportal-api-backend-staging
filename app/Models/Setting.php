<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_logo',
        'fav_icon',
        'admin_email',
        'address',
        'created_by',
        'gst_number',
        'date_format'
    ];
}
