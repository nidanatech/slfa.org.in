<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->char('public_id', 26)->nullable()->unique();
            $table->boolean('admission_enabled')->default(false);
            $table->timestamp('admission_closes_at')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 320)->nullable();
            $table->string('og_image_path')->nullable();
        });
        Schema::table('news_posts', function (Blueprint $table) {
            $table->char('public_id', 26)->nullable()->unique();
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 320)->nullable();
            $table->string('og_image_path')->nullable();
        });
        foreach (['events', 'news_posts'] as $table) {
            foreach (DB::table($table)->pluck('id') as $id) {
                DB::table($table)->where('id', $id)->update(['public_id' => (string) Str::ulid()]);
            }
        }
        Schema::create('event_admissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('phone', 30);
            $table->unsignedSmallInteger('adult_count')->default(1);
            $table->unsignedSmallInteger('child_count')->default(0);
            $table->string('status')->default('requested');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['event_id', 'email']);
        });
        Schema::table('gallery_media', function (Blueprint $table) {
            $table->foreignId('programme_item_id')->nullable()->after('album_id')->constrained('event_programme_items')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('gallery_media', fn (Blueprint $table) => $table->dropConstrainedForeignId('programme_item_id'));
        Schema::dropIfExists('event_admissions');
        Schema::table('news_posts', fn (Blueprint $table) => $table->dropColumn(['public_id', 'meta_title', 'meta_description', 'og_image_path']));
        Schema::table('events', fn (Blueprint $table) => $table->dropColumn(['public_id', 'admission_enabled', 'admission_closes_at', 'meta_title', 'meta_description', 'og_image_path']));
    }
};
