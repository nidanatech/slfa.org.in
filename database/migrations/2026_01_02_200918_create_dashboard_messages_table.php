<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashboard_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dashboard_conversation_id')->constrained('dashboard_conversations')->cascadeOnDelete();
            $table->string('role'); // user, assistant, system
            $table->text('content');
            $table->string('run_id')->nullable()->comment('OpenAI Run ID associated with this message generation');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_messages');
    }
};
