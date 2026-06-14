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
            $table->boolean('ai_sorting_enabled')->default(false)->after('name');
            $table->float('ai_confidence_threshold')->default(0.7)->after('ai_sorting_enabled');
            $table->boolean('allow_stage_downgrade')->default(false)->after('ai_confidence_threshold');
        });

        Schema::table('pipeline_stages', function (Blueprint $table) {
            $table->boolean('is_final')->default(false)->after('is_won');
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->foreignId('pipeline_id')->nullable()->constrained('pipelines')->nullOnDelete()->after('tenant_id');
            $table->foreignId('stage_id')->nullable()->constrained('pipeline_stages')->nullOnDelete()->after('pipeline_id');
            $table->float('stage_confidence')->nullable()->after('stage_id');
            $table->string('stage_updated_by')->nullable()->comment('ai, manual, workflow')->after('stage_confidence');
            $table->timestamp('stage_updated_at')->nullable()->after('stage_updated_by');
            $table->timestamp('next_stage_change_allowed_at')->nullable()->after('stage_updated_at');
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->integer('message_count')->default(0)->after('status');
            $table->timestamp('last_classified_at')->nullable()->after('message_count');
            $table->integer('classification_version')->default(0)->after('last_classified_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pipelines', function (Blueprint $table) {
            $table->dropColumn(['ai_sorting_enabled', 'ai_confidence_threshold', 'allow_stage_downgrade']);
        });

        Schema::table('pipeline_stages', function (Blueprint $table) {
            $table->dropColumn(['is_final']);
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->dropForeign(['pipeline_id']);
            $table->dropForeign(['stage_id']);
            $table->dropColumn([
                'pipeline_id', 'stage_id', 'stage_confidence', 
                'stage_updated_by', 'stage_updated_at', 'next_stage_change_allowed_at'
            ]);
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn(['message_count', 'last_classified_at', 'classification_version']);
        });
    }
};
