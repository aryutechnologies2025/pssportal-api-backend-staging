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
        Schema::table('companies', function (Blueprint $table) {
            $table->dropUnique('companies_gst_number_unique');
            $table->dropUnique('companies_support_email_unique');
            $table->dropUnique('companies_billing_email_unique');

            $table->string('gst_number')->nullable()->change();
            $table->string('support_email')->nullable()->change();
            $table->string('billing_email')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {

          // Revert nullable
            $table->string('gst_number')->nullable(false)->change();
            $table->string('support_email')->nullable(false)->change();
            $table->string('billing_email')->nullable(false)->change();
            
            $table->unique('gst_number');
            $table->unique('support_email');
            $table->unique('billing_email');
        });
    }
};
