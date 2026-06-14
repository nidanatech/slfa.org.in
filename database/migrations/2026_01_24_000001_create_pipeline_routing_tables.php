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
        Schema::create('pipeline_channel_defaults', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pipeline_id')->constrained()->cascadeOnDelete();
            $table->string('channel')->comment('whatsapp, instagram, facebook, api, widget, etc');
            $table->integer('priority')->default(0);
            $table->timestamps();

            $table->index(['channel', 'priority']);
        });

        Schema::create('pipeline_stage_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pipeline_id')->constrained()->cascadeOnDelete();
            $table->json('conditions_json');
            $table->foreignId('target_stage_id')->constrained('pipeline_stages')->cascadeOnDelete();
            $table->integer('priority')->default(0);
            $table->timestamps();

            $table->index('pipeline_id');
        });

        Schema::create('stage_change_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->foreignId('old_stage_id')->nullable()->constrained('pipeline_stages')->nullOnDelete();
            $table->foreignId('new_stage_id')->constrained('pipeline_stages')->cascadeOnDelete();
            $table->string('triggered_by'); // ai, manual, workflow
            $table->float('confidence')->nullable();
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->index('contact_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stage_change_logs');
        Schema::dropIfExists('pipeline_stage_rules');
        Schema::dropIfExists('pipeline_channel_defaults');
    }
};
