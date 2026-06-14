<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_post_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('social_post_id')->constrained()->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            
            // File Information
            $table->string('file_path');
            $table->enum('file_type', ['image', 'video']);
            $table->integer('file_size'); // bytes
            $table->string('mime_type');
            $table->string('original_filename');
            
            // Dimensions
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->integer('duration')->nullable(); // for videos, in seconds
            
            // AI Generation
            $table->boolean('ai_generated')->default(false);
            $table->text('ai_prompt')->nullable();
            
            // Ordering
            $table->integer('sort_order')->default(0);
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['social_post_id', 'sort_order']);
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_post_media');
    }
};
