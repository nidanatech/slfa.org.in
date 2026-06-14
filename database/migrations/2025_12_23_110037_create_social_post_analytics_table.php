<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_post_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('social_post_channel_id')->constrained()->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            
            // Engagement Metrics
            $table->integer('likes')->default(0);
            $table->integer('comments')->default(0);
            $table->integer('shares')->default(0);
            $table->integer('clicks')->default(0);
            $table->integer('impressions')->default(0);
            $table->integer('reach')->default(0);
            $table->decimal('engagement_rate', 5, 2)->default(0);
            
            // Raw Platform Data
            $table->json('platform_insights')->nullable();
            
            // Sync Tracking
            $table->timestamp('last_synced_at')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('social_post_channel_id');
            $table->index('tenant_id');
            $table->index('last_synced_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_post_analytics');
    }
};
