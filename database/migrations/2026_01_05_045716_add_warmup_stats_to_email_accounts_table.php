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
        Schema::table('email_accounts', function (Blueprint $table) {
            $table->decimal('bounce_rate', 5, 2)->default(0);
            $table->integer('spam_reports')->default(0);
            $table->decimal('provider_error_rate', 5, 2)->default(0);
            $table->decimal('inbox_rate', 5, 2)->default(100);
            $table->decimal('reply_rate', 5, 2)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_accounts', function (Blueprint $table) {
            //
        });
    }
};
