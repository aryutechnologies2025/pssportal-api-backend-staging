<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_category_id',
        'asset_sub_category_id',
        'ledger',
        'title',
        'invoice_number',
        'purchase_date',
        'depreciation_percentage',
        'quantity',
        'rate',
        'gst_rate',
        'taxable_amount',
        'cgst_rate',
        'sgst_rate',
        'igst_rate',
        'invoice_value',
        'warranty_years',
        'disposed_date',
        'invoice_file',
        'is_deleted',
        'status',
        'created_by',
        'updated_by',
    ];

    public function category()
    {
        return $this->belongsTo(AssetCategory::class, 'asset_category_id');
    }

    public function subCategory()
    {
        return $this->belongsTo(AssetSubCategory::class, 'asset_sub_category_id');
    }
}
