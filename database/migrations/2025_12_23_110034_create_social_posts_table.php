<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('brand_voice_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('design_kit_id')->nullable()->constrained()->onDelete('set null');
            
            // Content
            $table->text('content');
            $table->json('hashtags')->nullable();
            $table->json('mentions')->nullable();
            
            // Status & Approval
            $table->enum('status', ['draft', 'pending_approval', 'approved', 'rejected', 'scheduled', 'publishing', 'published', 'failed'])->default('draft');
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            
            // Scheduling & Publishing
            $table->timestamp('scheduled_for')->nullable();
            $table->timestamp('published_at')->nullable();
            
            // Metadata
            $table->json('metadata')->nullable();
            $table->boolean('ai_generated')->default(false);
            $table->text('ai_prompt')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'scheduled_for']);
            $table->index('user_id');
            $table->index('brand_voice_id');
            $table->index('approved_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_posts');
    }
};
