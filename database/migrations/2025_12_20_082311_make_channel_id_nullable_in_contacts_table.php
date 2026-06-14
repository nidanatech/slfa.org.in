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
        Schema::table('contacts', function (Blueprint $table) {
            // Add a regular index for channel_id first so the foreign key isn't left without one
            $table->index('channel_id');
            $table->dropUnique(['channel_id', 'external_id']); // Drop the unique constraint
            $table->foreignId('channel_id')->nullable()->change(); // Make channel_id nullable
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->foreignId('channel_id')->nullable(false)->change(); // Revert to required
            $table->unique(['channel_id', 'external_id']); // Restore constraint
        });
    }
};
