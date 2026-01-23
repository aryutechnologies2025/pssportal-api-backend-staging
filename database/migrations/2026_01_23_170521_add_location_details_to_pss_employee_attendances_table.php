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
        Schema::table('pss_employee_attendances', function (Blueprint $table) {
             $table->text('location_details')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pss_employee_attendances', function (Blueprint $table) {
              $table->dropColumn([
                'emr_contact_number'
              ]);
        });
    }
};
