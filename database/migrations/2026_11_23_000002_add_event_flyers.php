<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const GALA_TITLE = '7th Annual Gala Cocktail Dinner 2026';

    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('programme_image_path')->nullable()->after('cover_image_path');
        });

        DB::table('events')->where('title', self::GALA_TITLE)->update([
            'cover_image_path' => '/images/events/gala-dinner-2026-poster.jpeg',
            'programme_image_path' => '/images/events/gala-dinner-2026-programme.jpeg',
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('events')
            ->where('title', self::GALA_TITLE)
            ->where('cover_image_path', '/images/events/gala-dinner-2026-poster.jpeg')
            ->update(['cover_image_path' => null]);

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('programme_image_path');
        });
    }
};
