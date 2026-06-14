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
        Schema::create('gmb_insights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->constrained()->cascadeOnDelete();
            $table->date('metric_date');
            
            // Views metrics
            $table->integer('views_search')->default(0);
            $table->integer('views_maps')->default(0);
            $table->integer('views_total')->default(0);
            
            // Customer actions
            $table->integer('actions_website')->default(0);
            $table->integer('actions_phone')->default(0);
            $table->integer('actions_driving_directions')->default(0);
            
            // Photo metrics
            $table->integer('photos_views_merchant')->default(0);
            $table->integer('photos_views_customers')->default(0);
            $table->integer('photos_count_merchant')->default(0);
            $table->integer('photos_count_customers')->default(0);
            
            // Local post metrics
            $table->integer('local_post_views_search')->default(0);
            $table->integer('local_post_actions_call_to_action')->default(0);
            
            $table->timestamps();
            
            // Ensure unique date per channel
            $table->unique(['channel_id', 'metric_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gmb_insights');
    }
};
