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
        Schema::table('calendar_events', function (Blueprint $table) {
            $table->string('google_event_id')->nullable()->after('source');
            $table->foreignId('google_calendar_connection_id')->nullable()->after('google_event_id')->constrained()->onDelete('set null');
            $table->timestamp('last_synced_at')->nullable()->after('google_calendar_connection_id');
            
            $table->index('google_event_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calendar_events', function (Blueprint $table) {
            $table->dropForeign(['google_calendar_connection_id']);
            $table->dropColumn(['google_event_id', 'google_calendar_connection_id', 'last_synced_at']);
        });
    }
};
