<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('promo_video_path')->nullable();
            $table->string('promo_poster_path')->nullable();
            $table->text('promo_transcript')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('events', fn (Blueprint $table) => $table->dropColumn(['promo_video_path', 'promo_poster_path', 'promo_transcript']));
    }
};
