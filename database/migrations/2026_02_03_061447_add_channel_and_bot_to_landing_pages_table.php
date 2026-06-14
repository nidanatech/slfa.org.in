<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('landing_pages')) {
            Schema::table('landing_pages', function (Blueprint $table) {
                if (!Schema::hasColumn('landing_pages', 'channel_id')) {
                    $table->foreignId('channel_id')->nullable()->constrained('channels')->nullOnDelete();
                }
                if (!Schema::hasColumn('landing_pages', 'ai_bot_id')) {
                    $table->foreignId('ai_bot_id')->nullable()->constrained('ai_bots')->nullOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('landing_pages')) {
            Schema::table('landing_pages', function (Blueprint $table) {
                if (Schema::hasColumn('landing_pages', 'channel_id')) {
                    $table->dropForeign(['channel_id']);
                }
                if (Schema::hasColumn('landing_pages', 'ai_bot_id')) {
                    $table->dropForeign(['ai_bot_id']);
                }
                
                $columns = [];
                if (Schema::hasColumn('landing_pages', 'channel_id')) $columns[] = 'channel_id';
                if (Schema::hasColumn('landing_pages', 'ai_bot_id')) $columns[] = 'ai_bot_id';
                
                if (!empty($columns)) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};
