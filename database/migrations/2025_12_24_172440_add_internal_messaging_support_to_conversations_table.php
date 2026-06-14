<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            // Make channel_id nullable to support internal conversations
            $table->foreignId('channel_id')->nullable()->change();
            
            // Add conversation type (external, internal, note)
            $table->string('conversation_type')->default('external')->after('channel_id');
            
            // Track who initiated internal conversations
            $table->foreignId('initiated_by')->nullable()->after('conversation_type')
                ->constrained('users')->nullOnDelete();
            
            // Add index for faster filtering
            $table->index('conversation_type');
        });

        // Update existing conversations to be 'external' type
        DB::table('conversations')->update(['conversation_type' => 'external']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropIndex(['conversation_type']);
            $table->dropForeign(['initiated_by']);
            $table->dropColumn(['conversation_type', 'initiated_by']);
            
            // Make channel_id required again
            $table->foreignId('channel_id')->nullable(false)->change();
        });
    }
};
