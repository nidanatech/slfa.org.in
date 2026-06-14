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
        Schema::create('team_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->constrained('team_channels')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('content');
            $table->enum('message_type', ['text', 'file', 'system'])->default('text');
            $table->foreignId('parent_id')->nullable()->constrained('team_messages')->nullOnDelete();
            $table->json('metadata')->nullable(); // For file info, mentions, etc.
            $table->boolean('is_edited')->default(false);
            $table->timestamp('edited_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['channel_id', 'created_at']);
            $table->index('user_id');
            $table->index('parent_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_messages');
    }
};
