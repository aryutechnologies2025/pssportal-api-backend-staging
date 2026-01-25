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
        Schema::create('emp_rejoining_logs', function (Blueprint $table) {
            $table->id();

            // Contract employee reference
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('contract_can_emps')
                ->nullOnDelete();

            // Company reference
            $table->foreignId('company_id')
                ->nullable()
                ->constrained('companies')
                ->cascadeOnDelete();

            // Boarding point reference
            $table->foreignId('boarding_point_id')
                ->nullable()
                ->constrained('boarding_points')
                ->nullOnDelete();

            // Actual fields
            $table->text('address')->nullable();
            $table->date('joining_date')->nullable();
            $table->string('employee_id')->nullable();
            $table->text('rejoining_note')->nullable();

            // Status: 0 = pending, 1 = approved, 2 = rejected
            $table->tinyInteger('rejoin_status')->default(0);

            $table->string('created_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emp_rejoin_logs');
    }
};
