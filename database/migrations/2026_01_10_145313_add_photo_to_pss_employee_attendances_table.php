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
            $table->string('photo')->nullable()->after('reason');
            $table->decimal('latitude', 10, 7)->nullable()->after('photo');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pss_employee_attendances', function (Blueprint $table) {
            $table->dropColumn('photo');
            $table->dropColumn('latitude');
            $table->dropColumn('longitude');
            $table->dropColumn('location');
        });
    }
};
