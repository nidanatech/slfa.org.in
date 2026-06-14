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
        Schema::table('ai_bots', function (Blueprint $table) {
            $table->json('supported_channels')->nullable();
            $table->boolean('sleep_on_manual_message')->default(true);
            $table->integer('handover_timeout_seconds')->nullable();
            $table->text('fallback_message')->nullable();
            $table->float('confidence_threshold')->default(0.7);
            $table->integer('max_turns_per_session')->default(20);
            $table->string('default_language')->default('en');
            $table->string('timezone')->default('UTC');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_bots', function (Blueprint $table) {
            $table->dropColumn([
                'supported_channels',
                'sleep_on_manual_message',
                'handover_timeout_seconds',
                'fallback_message',
                'confidence_threshold',
                'max_turns_per_session',
                'default_language',
                'timezone'
            ]);
        });
    }
};
