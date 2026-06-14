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
        Schema::create('llm_token_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bot_id')->constrained('ai_bots')->cascadeOnDelete();
            $table->unsignedBigInteger('bot_session_id')->nullable(); 
            // Can't easily constrain bot_session_id if it's sometimes system call or cross-session, but usually related to bot_sessions table
            // However, user said nullable. Let's add foreign constraint if possible but nullable.
            // $table->foreign('bot_session_id')->references('id')->on('bot_sessions')->nullOnDelete(); 
            // Keeping it unconstrained or simple integer for flexibility if system calls don't map to session perfectly? 
            // Assuming constrained is better for integrity.
            $table->foreignId('conversation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            
            $table->string('provider')->default('openai'); // openai, anthropic
            $table->string('model'); // gpt-4, etc.
            
            $table->integer('prompt_tokens')->default(0);
            $table->integer('completion_tokens')->default(0);
            $table->integer('total_tokens')->default(0);
            
            $table->string('request_type')->default('chat'); // chat, embedding
            $table->string('purpose')->nullable(); // reply, routing
            
            $table->decimal('cost_usd', 10, 6)->nullable();
            $table->integer('latency_ms')->nullable();
            
            $table->timestamps();
        });

        Schema::create('bot_daily_token_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bot_id')->constrained('ai_bots')->cascadeOnDelete();
            $table->date('date');
            
            $table->integer('total_prompt_tokens')->default(0);
            $table->integer('total_completion_tokens')->default(0);
            $table->integer('total_tokens')->default(0);
            $table->decimal('total_cost_usd', 10, 6)->default(0);
            
            $table->timestamps();
            
            $table->unique(['bot_id', 'date']);
        });

        Schema::create('tenant_daily_token_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            
            $table->integer('total_tokens')->default(0);
            $table->decimal('total_cost_usd', 10, 6)->default(0);
            
            $table->timestamps();
            
            $table->unique(['tenant_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_daily_token_usage');
        Schema::dropIfExists('bot_daily_token_usage');
        Schema::dropIfExists('llm_token_usage');
    }
};
