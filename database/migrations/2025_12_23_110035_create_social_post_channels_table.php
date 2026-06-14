<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_post_channels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('social_post_id')->constrained()->onDelete('cascade');
            $table->foreignId('channel_id')->constrained()->onDelete('cascade');
            
            // Platform Response Data
            $table->string('platform_post_id')->nullable();
            $table->string('platform_url')->nullable();
            
            // Status
            $table->enum('status', ['pending', 'publishing', 'published', 'failed'])->default('pending');
            $table->timestamp('published_at')->nullable();
            $table->text('error_message')->nullable();
            
            // Metadata
            $table->json('platform_metadata')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('social_post_id');
            $table->index('channel_id');
            $table->index('status');
            $table->unique(['social_post_id', 'channel_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_post_channels');
    }
};
