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
        Schema::create('employee_rejoings', function (Blueprint $table) {
            $table->id();
             $table->unsignedBigInteger('parent_id')->nullable();

            $table->foreign('parent_id')
                ->references('id')
                ->on('contract_can_emps')
                ->onDelete('set null');
            $table->string('status')->default(0)->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_rejoings');
    }
};
