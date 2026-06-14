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
        Schema::table('opportunity_charters', function (Blueprint $table) {
            $table->text('signature_data')->nullable()->after('signature_hash'); // For canvas image
            $table->string('signed_device')->nullable()->after('signed_by_ip');
            $table->text('user_agent')->nullable()->after('signed_device');
        });
    }

    public function down(): void
    {
        Schema::table('opportunity_charters', function (Blueprint $table) {
            $table->dropColumn(['signature_data', 'signed_device', 'user_agent']);
        });
    }
};
