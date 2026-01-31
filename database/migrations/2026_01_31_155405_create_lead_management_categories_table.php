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
        Schema::create('lead_management_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable()->index();
            $table->enum('status', ['1', '0'])->default('1');
            $table->enum('is_deleted', ['0', '1'])->default('0');
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
        Schema::dropIfExists('lead_management_categories');
    }
};
