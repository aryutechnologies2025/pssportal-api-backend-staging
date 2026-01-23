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
        Schema::table('contract_can_emps', function (Blueprint $table) {
            $table->unsignedBigInteger('boarding_point_id')
                ->nullable()
                ->after('company_id');

            $table->foreign('boarding_point_id')
                ->references('id')
                ->on('boarding_points')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contract_can_emps', function (Blueprint $table) {
            $table->dropForeign(['boarding_point_id']);
            $table->dropColumn('boarding_point_id');
        });
    }
};
