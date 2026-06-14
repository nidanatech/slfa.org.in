<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competitor_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competitor_id')->constrained()->onDelete('cascade');
            $table->enum('source_type', ['website', 'blog', 'social', 'careers', 'ads', 'seo', 'product']);
            $table->string('platform')->nullable(); // linkedin, twitter, instagram, youtube, etc.
            $table->string('url');
            $table->boolean('is_active')->default(true);
            $table->enum('validation_status', ['pending', 'valid', 'invalid'])->default('pending');
            $table->text('validation_error')->nullable();
            $table->timestamp('last_scraped_at')->nullable();
            $table->enum('scrape_frequency', ['hourly', 'daily', 'weekly'])->default('daily');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['competitor_id', 'is_active']);
            $table->index(['source_type', 'platform']);
            $table->index('last_scraped_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competitor_sources');
    }
};
