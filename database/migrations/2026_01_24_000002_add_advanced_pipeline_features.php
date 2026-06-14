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
        Schema::table('pipelines', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
            $table->boolean('is_active')->default(true)->after('description');
            $table->json('ai_settings')->nullable()->after('allow_stage_downgrade');
        });

        Schema::table('pipeline_stages', function (Blueprint $table) {
            $table->string('color')->default('#64748b')->after('name'); // slate-500 default
            $table->text('description')->nullable()->after('color');
            $table->string('stage_type')->default('open')->after('description'); // open, won, lost, spam
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pipelines', function (Blueprint $table) {
            $table->dropColumn(['description', 'is_active', 'ai_settings']);
        });

        Schema::table('pipeline_stages', function (Blueprint $table) {
            $table->dropColumn(['color', 'description', 'stage_type']);
        });
    }
};
