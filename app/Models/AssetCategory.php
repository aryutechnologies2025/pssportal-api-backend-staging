<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'status',
        'is_deleted',
        'created_by',
        'updated_by'
    ];

    public function subCategories()
    {
        return $this->hasMany(AssetSubCategory::class, 'asset_category_id');
    }
}
