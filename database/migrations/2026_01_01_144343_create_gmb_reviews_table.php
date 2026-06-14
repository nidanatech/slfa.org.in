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
        Schema::create('gmb_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->constrained()->cascadeOnDelete();
            $table->string('review_id')->unique(); // Google's review ID
            
            // Reviewer information
            $table->string('reviewer_display_name')->nullable();
            $table->string('reviewer_profile_photo_url')->nullable();
            
            // Review content
            $table->integer('star_rating');
            $table->text('comment')->nullable();
            
            // Business reply
            $table->text('review_reply')->nullable();
            $table->timestamp('reply_update_time')->nullable();
            
            // Timestamps from Google
            $table->timestamp('create_time')->nullable();
            $table->timestamp('update_time')->nullable();
            
            $table->timestamps();
            
            $table->index(['channel_id', 'star_rating']);
            $table->index(['channel_id', 'create_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gmb_reviews');
    }
};
