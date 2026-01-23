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
            $table->string('emr_contact_number')->nullable();
            $table->string('marital_status')->nullable();
            $table->string('current_address')->nullable();
            $table->string('pan_number')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('branch_name')->nullable();
            $table->string('bank_name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contract_can_emps', function (Blueprint $table) {
            $table->dropColumn([
                'emr_contact_number',
                'marital_status',
                'current_address',
                'pan_number',
                'city',
                'state',
                'branch_name',
                'bank_name',
            ]);
        });
    }
};
