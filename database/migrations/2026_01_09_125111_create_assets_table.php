<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();

            $table->foreignId('asset_category_id')
          ->constrained('asset_categories');

        $table->foreignId('asset_sub_category_id')
          ->constrained('asset_sub_categories');

        $table->string('ledger')->nullable();
        $table->string('title');
        $table->string('invoice_number')->nullable();
        $table->date('purchase_date')->nullable();

        $table->decimal('depreciation_percentage', 5, 2)->nullable();
        $table->integer('quantity');
        $table->decimal('rate', 10, 2);
        $table->decimal('gst_rate', 5, 2)->nullable();
        $table->decimal('taxable_amount', 12, 2)->nullable();
        $table->decimal('cgst_rate', 5, 2)->nullable();
        $table->decimal('sgst_rate', 5, 2)->nullable();
        $table->decimal('igst_rate', 5, 2)->nullable();
        $table->decimal('invoice_value', 12, 2);
        $table->integer('warranty_years')->nullable();
        $table->date('disposed_date')->nullable();
        $table->string('invoice_file')->nullable();

        $table->boolean('is_deleted')->default(0);
        $table->boolean('status')->default(1);
        $table->string('created_by')->nullable();
        $table->string('updated_by')->nullable();

        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
