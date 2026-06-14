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
        if (!Schema::hasTable('landing_pages')) {
            Schema::create('landing_pages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->string('name');
                $table->string('slug')->unique();
                $table->json('content')->nullable();
                $table->json('settings')->nullable();
                $table->boolean('is_published')->default(false);
                $table->timestamp('published_at')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        Schema::table('landing_pages', function (Blueprint $table) {
            if (!Schema::hasColumn('landing_pages', 'user_id')) {
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            }
            if (!Schema::hasColumn('landing_pages', 'tenant_id')) {
                $table->foreignId('tenant_id')->nullable()->constrained()->onDelete('cascade');
            }
            if (!Schema::hasColumn('landing_pages', 'name')) {
                $table->string('name')->nullable();
            }
            if (!Schema::hasColumn('landing_pages', 'slug')) {
                $table->string('slug')->nullable()->unique();
            }
            if (!Schema::hasColumn('landing_pages', 'content')) {
                $table->json('content')->nullable();
            }
            if (!Schema::hasColumn('landing_pages', 'settings')) {
                $table->json('settings')->nullable();
            }
            if (!Schema::hasColumn('landing_pages', 'is_published')) {
                $table->boolean('is_published')->default(false);
            }
            if (!Schema::hasColumn('landing_pages', 'published_at')) {
                $table->timestamp('published_at')->nullable();
            }
            if (!Schema::hasColumn('landing_pages', 'generation_prompt')) {
                $table->text('generation_prompt')->nullable();
            }
            if (!Schema::hasColumn('landing_pages', 'brand_context')) {
                $table->json('brand_context')->nullable();
            }
            if (!Schema::hasColumn('landing_pages', 'language')) {
                $table->string('language')->default('en');
            }
            if (!Schema::hasColumn('landing_pages', 'seo_metadata')) {
                $table->json('seo_metadata')->nullable();
            }
            if (!Schema::hasColumn('landing_pages', 'views_count')) {
                $table->unsignedBigInteger('views_count')->default(0);
            }
            if (!Schema::hasColumn('landing_pages', 'submissions_count')) {
                $table->unsignedBigInteger('submissions_count')->default(0);
            }
            if (!Schema::hasColumn('landing_pages', 'clicks_count')) {
                $table->unsignedBigInteger('clicks_count')->default(0);
            }
            if (!Schema::hasColumn('landing_pages', 'channel_id')) {
                $table->foreignId('channel_id')->nullable()->constrained('channels')->nullOnDelete();
            }
            if (!Schema::hasColumn('landing_pages', 'ai_bot_id')) {
                $table->foreignId('ai_bot_id')->nullable()->constrained('ai_bots')->nullOnDelete();
            }
            if (!Schema::hasColumn('landing_pages', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No down migration as this is a corrective "fix-it-all" migration
    }
};
