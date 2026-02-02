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
        Schema::create('attendance_shift_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')
                ->constrained('attendance_details')
                ->cascadeOnDelete();
            $table->foreignId('employee_id')
                ->constrained('contract_can_emps')
                ->cascadeOnDelete();
            $table->foreignId('shift_id')
                ->constrained('companies_shift')
                ->cascadeOnDelete();
            // $table->date('shift_date');
            $table->string('start_time');
            $table->string('end_time');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_shift_details');
    }
};
