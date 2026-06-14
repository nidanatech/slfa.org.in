<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_accounts', function (Blueprint $table) {
            $table->boolean('warmup_skipped')->default(false)->after('warmup_emails_sent');
            $table->timestamp('warmup_skipped_at')->nullable()->after('warmup_skipped');
        });
    }

    public function down(): void
    {
        Schema::table('email_accounts', function (Blueprint $table) {
            $table->dropColumn(['warmup_skipped', 'warmup_skipped_at']);
        });
    }
};
