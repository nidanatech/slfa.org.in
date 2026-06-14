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
        Schema::create('gmb_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->constrained()->cascadeOnDelete();
            $table->string('post_id')->unique(); // Google's post ID
            
            // Post content
            $table->text('summary')->nullable();
            $table->string('call_to_action_type')->nullable(); // BOOK, ORDER, SHOP, LEARN_MORE, SIGN_UP, CALL
            $table->string('call_to_action_url')->nullable();
            
            // Media
            $table->string('media_format')->nullable(); // PHOTO, VIDEO
            $table->text('media_url')->nullable();
            
            // Post type and event details
            $table->string('topic_type')->nullable(); // STANDARD, EVENT, OFFER, ALERT
            $table->string('event_title')->nullable();
            $table->json('event_schedule')->nullable(); // start_date, start_time, end_date, end_time
            
            // Metadata
            $table->string('state')->default('LIVE'); // LIVE, EXPIRED, REJECTED
            $table->string('search_url')->nullable();
            
            // Timestamps from Google
            $table->timestamp('create_time')->nullable();
            $table->timestamp('update_time')->nullable();
            
            $table->timestamps();
            
            $table->index(['channel_id', 'state']);
            $table->index(['channel_id', 'topic_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gmb_posts');
    }
};
