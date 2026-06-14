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
            $table->string('estimated_duration')->nullable()->after('budget_summary');
            $table->date('estimated_start_date')->nullable()->after('estimated_duration');
            $table->date('estimated_end_date')->nullable()->after('estimated_start_date');
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
