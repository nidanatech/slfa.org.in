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
            $table->text('purpose_justification')->nullable()->after('id');
            $table->text('high_level_requirements')->nullable()->after('deliverables');
            $table->text('overall_risk')->nullable()->after('high_level_requirements');
            $table->text('milestone_summary')->nullable()->after('overall_risk');
            $table->text('budget_summary')->nullable()->after('milestone_summary');
            $table->text('stakeholder_list')->nullable()->after('budget_summary');
            $table->text('approval_requirements')->nullable()->after('stakeholder_list');
            $table->text('pm_authority')->nullable()->after('approval_requirements');
            $table->text('sponsor_authority')->nullable()->after('pm_authority');
            
            // Renaming or keeping existing ones to match meaning
            // 'objectives' -> already exists, will be used as 'Measurable Project Objectives'
            // 'deliverables' -> already exists
            // 'constraints', 'assumptions' -> already exist
            // 'in_scope', 'out_of_scope' -> already exist, collectively 'Boundaries'
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
