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
        Schema::create('finances', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')->constrained('pss_company');
            $table->foreignId('branch_id')->constrained('branches');

            $table->date('date');
            $table->decimal('amount', 15, 2);

            $table->longText('description')->nullable();
            $table->string('bill')->nullable(); 

            $table->boolean('status')->default(1);
            $table->boolean('is_deleted')->default(0);

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
        Schema::dropIfExists('finances');
    }
};
