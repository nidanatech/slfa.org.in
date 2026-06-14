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
        Schema::table('opportunity_charters', function (Blueprint $table) {
            $table->integer('estimated_duration_weeks')->nullable()->after('budget_summary');
            $table->decimal('budget_amount', 15, 2)->nullable()->after('estimated_duration_weeks');
            $table->string('currency', 3)->default('USD')->after('budget_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('opportunity_charters', function (Blueprint $table) {
            //
        });
    }
};
