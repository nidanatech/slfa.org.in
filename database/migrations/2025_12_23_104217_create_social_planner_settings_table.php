<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_planner_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            
            // Image Generation Settings
            $table->string('image_model')->default('dall-e-3'); // dall-e-3, dall-e-2, midjourney, stable-diffusion
            $table->string('image_model_api_key')->nullable();
            $table->string('image_quality')->default('standard'); // standard, hd
            $table->string('image_size')->default('1024x1024'); // 1024x1024, 1792x1024, 1024x1792
            
            // Approval Workflow
            $table->boolean('requires_approval')->default(false);
            $table->foreignId('approver_id')->nullable()->constrained('users')->onDelete('set null');
            $table->boolean('notify_approver_email')->default(true);
            $table->boolean('notify_approver_slack')->default(false);
            $table->string('slack_webhook_url')->nullable();
            
            // Posting Settings
            $table->boolean('auto_publish_approved')->default(true);
            $table->integer('default_post_delay_minutes')->default(0); // Delay between posts
            $table->string('default_timezone')->default('UTC');
            
            // Content Settings
            $table->integer('max_content_length')->default(2000);
            $table->boolean('auto_hashtag_suggestions')->default(true);
            $table->boolean('auto_emoji_suggestions')->default(true);
            $table->json('prohibited_words')->nullable(); // Array of words to flag
            
            // Analytics & Tracking
            $table->boolean('track_engagement')->default(true);
            $table->boolean('track_clicks')->default(true);
            $table->string('utm_source')->default('social_planner');
            $table->string('utm_medium')->default('social');
            
            // AI Settings
            $table->string('ai_model')->default('gpt-4'); // gpt-4, gpt-3.5-turbo, claude
            $table->string('ai_api_key')->nullable();
            $table->integer('ai_max_tokens')->default(500);
            $table->float('ai_temperature')->default(0.7);
            
            // Metadata
            $table->timestamps();
            
            // Indexes
            $table->unique('tenant_id');
            $table->index('approver_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_planner_settings');
    }
};
