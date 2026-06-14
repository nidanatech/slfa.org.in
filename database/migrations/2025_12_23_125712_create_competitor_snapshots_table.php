<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competitor_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_id')->constrained('competitor_sources')->onDelete('cascade');
            $table->enum('snapshot_type', ['full', 'incremental'])->default('full');
            $table->string('content_hash'); // SHA256 hash for quick comparison
            $table->longText('raw_content')->nullable(); // Full HTML/JSON
            $table->json('parsed_data')->nullable(); // Structured extracted data
            $table->json('metadata')->nullable(); // Scrape metadata
            $table->string('file_path')->nullable(); // For very large content
            $table->timestamp('captured_at');
            $table->timestamps();

            $table->index(['source_id', 'captured_at']);
            $table->index('content_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competitor_snapshots');
    }
};
