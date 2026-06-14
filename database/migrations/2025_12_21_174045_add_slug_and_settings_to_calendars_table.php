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
        Schema::table('calendars', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->index();
            $table->integer('slot_duration')->default(30)->comment('Minutes per slot');
            $table->integer('buffer_time')->default(0)->comment('buffer time after events in minutes');
            $table->boolean('is_public')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calendars', function (Blueprint $table) {
            $table->dropColumn(['slug', 'slot_duration', 'buffer_time', 'is_public']);
        });
    }
};
