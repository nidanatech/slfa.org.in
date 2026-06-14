<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competitor_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_id')->constrained('competitor_sources')->onDelete('cascade');
            $table->foreignId('competitor_id')->constrained()->onDelete('cascade');
            $table->enum('change_type', ['content', 'pricing', 'feature', 'hiring', 'ad', 'seo', 'product']);
            $table->string('category'); // Specific category like "pricing_change", "new_job_posting"
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->string('title');
            $table->text('description');
            $table->foreignId('before_snapshot_id')->nullable()->constrained('competitor_snapshots')->onDelete('set null');
            $table->foreignId('after_snapshot_id')->constrained('competitor_snapshots')->onDelete('cascade');
            $table->json('diff_data')->nullable(); // Structured diff
            $table->timestamp('detected_at');
            $table->boolean('is_read')->default(false);
            $table->timestamps();

            $table->index(['competitor_id', 'detected_at']);
            $table->index(['change_type', 'severity']);
            $table->index('is_read');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competitor_changes');
    }
};
