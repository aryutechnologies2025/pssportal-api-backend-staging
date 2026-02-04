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
        Schema::table('lead_management', function (Blueprint $table) {
            $table->unsignedBigInteger('lead_category_id')->nullable()->after('age');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lead_management', function (Blueprint $table) {
            $table->dropColumn('lead_category_id');
        });
    }
};
