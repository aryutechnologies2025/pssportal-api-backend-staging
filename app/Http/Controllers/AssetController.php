<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;


class AssetController extends Controller
{
    public function index()
    {
        $assets = Asset::with(['category:id,name', 'subCategory:id,name'])
            ->where('is_deleted', 0)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $assets
        ]);
    }

    public function show($id)
    {
        $asset = Asset::with(['category', 'subCategory'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $asset
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'asset_category_id'     => 'required|exists:asset_categories,id',
            'asset_sub_category_id' => 'required|exists:asset_sub_categories,id',
            'title'                 => 'required|string',
            'quantity'              => 'required|integer|min:1',
            'rate'                  => 'required|numeric|min:0',
            'invoice_value'         => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        /* ============================
         Invoice File upload
        ============================ */
        $photoDir = public_path('uploads/asset_management');

        if (!file_exists($photoDir)) {
            mkdir($photoDir, 0755, true);
        }

        $invoicefile = '';
        if ($request->hasFile('invoice_file')) {
            $photo = $request->file('invoice_file');
            $photoName = now()->format('YmdHis') . '_' .  rand(10000, 99999) . '_' . Str::slug(pathinfo($photo->getClientOriginalName(), PATHINFO_FILENAME))
                . '.' . $photo->getClientOriginalExtension();

            $photo->move($photoDir, $photoName);
            $invoicefile = 'uploads/asset_management/' . $photoName;
        }

        $asset = Asset::create([
            'asset_category_id'     => $request->asset_category_id,
            'asset_sub_category_id' => $request->asset_sub_category_id,
            'ledger'                => $request->ledger,
            'title'                 => $request->title,
            'invoice_number'        => $request->invoice_number,
            'purchase_date'         => $request->purchase_date,
            'depreciation_percentage'=> $request->depreciation_percentage,
            'quantity'              => $request->quantity,
            'rate'                  => $request->rate,
            'gst_rate'              => $request->gst_rate,
            'taxable_amount'        => $request->taxable_amount,
            'cgst_rate'             => $request->cgst_rate,
            'sgst_rate'             => $request->sgst_rate,
            'igst_rate'             => $request->igst_rate,
            'invoice_value'         => $request->invoice_value,
            'warranty_years'        => $request->warranty_years,
            'disposed_date'         => $request->disposed_date,
            'invoice_file'          => $invoicefile,
            'status'                => $request->status ?? 1,
            'created_by'            => $request->created_by,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Asset created successfully',
            'data' => $asset
        ], 201);
    }

    public function update(Request $request, $id)
    {
    $validator = Validator::make($request->all(), [
        'asset_category_id'     => 'required|exists:asset_categories,id',
        'asset_sub_category_id' => 'required|exists:asset_sub_categories,id',
        'title'                 => 'required|string',
        'quantity'              => 'required|integer|min:1',
        'rate'                  => 'required|numeric|min:0',
        'invoice_value'         => 'required|numeric|min:0',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Validation error',
            'errors' => $validator->errors()
        ], 422);
    }

    $asset = Asset::findOrFail($id);

    // ============================
    // Invoice file upload (UPDATE)
    // ============================
    $invoicefile = $asset->invoice_file; // keep old file

    if ($request->hasFile('invoice_file')) {
        $photoDir = public_path('uploads/asset_management');
        if (!file_exists($photoDir)) {
            mkdir($photoDir, 0755, true);
        }

        $photo = $request->file('invoice_file');

        $photoName = now()->format('YmdHis') . '_' . rand(10000, 99999) . '_' .
            Str::slug(
                pathinfo($photo->getClientOriginalName(), PATHINFO_FILENAME)
            ) . '.' . $photo->getClientOriginalExtension();

        $photo->move($photoDir, $photoName);

        $invoicefile = 'uploads/asset_management/' . $photoName;
    }

    $asset->update([
        'asset_category_id'      => $request->asset_category_id,
        'asset_sub_category_id'  => $request->asset_sub_category_id,
        'ledger'                 => $request->ledger,
        'title'                  => $request->title,
        'invoice_number'         => $request->invoice_number,
        'purchase_date'          => $request->purchase_date,
        'depreciation_percentage'=> $request->depreciation_percentage,
        'quantity'               => $request->quantity,
        'rate'                   => $request->rate,
        'gst_rate'               => $request->gst_rate,
        'taxable_amount'         => $request->taxable_amount,
        'cgst_rate'              => $request->cgst_rate,
        'sgst_rate'              => $request->sgst_rate,
        'igst_rate'              => $request->igst_rate,
        'invoice_value'          => $request->invoice_value,
        'warranty_years'         => $request->warranty_years,
        'disposed_date'          => $request->disposed_date,
        'invoice_file'           => $invoicefile, 
        'status'                 => $request->status ?? $asset->status,
        'updated_by'             => $request->updated_by,
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Asset updated successfully',
        'data' => $asset
    ]);
    }

    public function destroy($id)
    {
        $asset = Asset::find($id);

        if (!$asset) {
            return response()->json([
                'success' => false,
                'message' => 'Record not found'
            ], 404);
        }

        $asset->is_deleted = 1;
        $asset->save();

        return response()->json([
            'success' => true,
            'message' => 'Asset deleted successfully'
        ]);
    }
}
