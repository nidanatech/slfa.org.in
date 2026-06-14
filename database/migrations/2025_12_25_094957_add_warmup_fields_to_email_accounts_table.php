<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_accounts', function (Blueprint $table) {
            // Warmup tracking fields
            $table->boolean('warmup_enabled')->default(false)->after('sync_enabled');
            $table->timestamp('warmup_started_at')->nullable()->after('warmup_enabled');
            $table->timestamp('warmup_completed_at')->nullable()->after('warmup_started_at');
            $table->timestamp('last_warmup_at')->nullable()->after('warmup_completed_at');
            $table->integer('warmup_day')->default(1)->after('last_warmup_at');
            $table->integer('warmup_emails_sent')->default(0)->after('warmup_day');
        });
    }

    public function down(): void
    {
        Schema::table('email_accounts', function (Blueprint $table) {
            $table->dropColumn([
                'warmup_enabled',
                'warmup_started_at',
                'warmup_completed_at',
                'last_warmup_at',
                'warmup_day',
                'warmup_emails_sent',
            ]);
        });
    }
};
