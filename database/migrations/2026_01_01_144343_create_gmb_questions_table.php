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
        Schema::create('gmb_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->constrained()->cascadeOnDelete();
            $table->string('question_id')->unique(); // Google's question ID
            
            // Question author
            $table->string('author_display_name')->nullable();
            $table->string('author_profile_photo_url')->nullable();
            
            // Question content
            $table->text('text');
            $table->integer('upvote_count')->default(0);
            
            // Answer (if exists)
            $table->text('answer_text')->nullable();
            $table->string('answer_author_display_name')->nullable();
            $table->timestamp('answer_create_time')->nullable();
            
            // Timestamps from Google
            $table->timestamp('create_time')->nullable();
            $table->timestamp('update_time')->nullable();
            
            $table->timestamps();
            
            $table->index(['channel_id', 'create_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gmb_questions');
    }
};
