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
        // 1. Knowledge Base Tables
        Schema::create('knowledge_bases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('language')->default('en');
            $table->enum('visibility', ['internal', 'bot', 'hybrid'])->default('bot');
            $table->timestamps();
        });

        Schema::create('knowledge_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('knowledge_base_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // faq, document, url, text, database
            $table->string('name');
            $table->json('source_config_json')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });

        Schema::create('knowledge_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('knowledge_base_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_id')->constrained('knowledge_sources')->cascadeOnDelete();
            $table->text('content');
            // $table->vector('embedding_vector', 1536)->nullable(); // pgvector support if available
            $table->integer('token_count')->nullable();
            $table->timestamps();
        });

        // 2. Bot Config Tables
        Schema::create('bot_ai_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bot_id')->constrained('ai_bots')->cascadeOnDelete();
            $table->string('openai_model')->default('gpt-4-turbo');
            $table->text('system_prompt');
            $table->text('additional_information')->nullable();
            $table->float('temperature')->default(0.7);
            $table->integer('max_tokens')->default(1000);
            $table->timestamps();
        });

        Schema::create('bot_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bot_id')->constrained('ai_bots')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('bot_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bot_id')->constrained('ai_bots')->cascadeOnDelete();
            $table->string('action_type'); // book_appointment, trigger_workflow, etc.
            $table->json('config_json')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('bot_action_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bot_action_id')->constrained('bot_actions')->cascadeOnDelete();
            $table->json('config_json');
            $table->timestamps();
        });
        
        // 3. Scenarios & Sequences
        Schema::create('bot_action_scenarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bot_action_id')->constrained('bot_actions')->cascadeOnDelete();
            $table->string('scenario_name');
            $table->string('trigger_condition'); // intent, phrase, confidence
            $table->json('trigger_config_json')->nullable();
            $table->text('final_message')->nullable();
            $table->integer('reactivate_after_hours')->default(24);
            $table->unsignedBigInteger('add_contact_tag_id')->nullable(); 
            $table->unsignedBigInteger('assigned_user_id')->nullable();
            $table->unsignedBigInteger('target_bot_id')->nullable();
            $table->boolean('create_task')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('bot_action_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('scenario_id')->constrained('bot_action_scenarios')->cascadeOnDelete();
            $table->integer('sequence_order');
            $table->integer('delay_minutes')->default(0);
            $table->enum('message_source', ['ai', 'static'])->default('ai');
            $table->text('message_content')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 4. Runtime & Logs
        Schema::create('bot_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bot_id')->constrained('ai_bots')->cascadeOnDelete();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->string('channel')->nullable();
            $table->integer('turn_count')->default(0);
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('ended_at')->nullable();
            $table->string('ended_reason')->nullable();
            $table->boolean('is_test')->default(false);
        });

        Schema::create('bot_action_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bot_id')->constrained('ai_bots')->cascadeOnDelete();
            $table->foreignId('bot_action_id')->constrained('bot_actions')->cascadeOnDelete();
            $table->foreignId('conversation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action_type');
            $table->foreignId('scenario_id')->nullable()->constrained('bot_action_scenarios')->nullOnDelete();
            $table->string('status');
            $table->timestamp('executed_at')->useCurrent();
        });

        Schema::create('bot_knowledge_bases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bot_id')->constrained('ai_bots')->cascadeOnDelete();
            $table->foreignId('knowledge_base_id')->constrained()->cascadeOnDelete();
            $table->integer('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('bot_daily_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bot_id')->constrained('ai_bots')->cascadeOnDelete();
            $table->date('date');
            $table->integer('contacts_created')->default(0);
            $table->integer('actions_triggered')->default(0);
            $table->integer('appointments_booked')->default(0);
            $table->integer('conversations_handled')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bot_daily_stats');
        Schema::dropIfExists('bot_knowledge_bases');
        Schema::dropIfExists('bot_action_logs');
        Schema::dropIfExists('bot_sessions');
        Schema::dropIfExists('bot_action_sequences');
        Schema::dropIfExists('bot_action_scenarios');
        Schema::dropIfExists('bot_action_configs');
        Schema::dropIfExists('bot_actions');
        Schema::dropIfExists('bot_goals');
        Schema::dropIfExists('bot_ai_configs');
        Schema::dropIfExists('knowledge_chunks');
        Schema::dropIfExists('knowledge_sources');
        Schema::dropIfExists('knowledge_bases');
    }
};
