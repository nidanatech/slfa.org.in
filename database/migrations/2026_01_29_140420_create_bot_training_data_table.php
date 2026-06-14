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
        Schema::create('bot_training_data', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->foreignId('bot_id')->constrained('ai_bots')->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->text('question');
            $table->text('answer');
            $table->json('embedding')->nullable();
            $table->timestamps();
            
            $table->index('bot_id');
            $table->index('tenant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bot_training_data');
    }
};
