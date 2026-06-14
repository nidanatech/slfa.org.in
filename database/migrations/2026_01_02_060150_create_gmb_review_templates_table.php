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
        Schema::create('gmb_review_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->constrained()->onDelete('cascade');
            $table->string('name'); // e.g., "5-Star Response Instructions"
            $table->integer('star_rating'); // 1-5
            $table->text('instructions'); // AI instructions for this rating
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Index for faster lookups
            $table->index(['channel_id', 'star_rating', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gmb_review_templates');
    }
};
