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
        \Illuminate\Support\Facades\DB::table('channels')
            ->where('type', 'whatsapp')
            ->update(['type' => 'whatsapp_api']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \Illuminate\Support\Facades\DB::table('channels')
            ->where('type', 'whatsapp_api')
            ->update(['type' => 'whatsapp']);
    }
};
