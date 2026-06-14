<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competitor_insights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('competitor_id')->constrained()->onDelete('cascade');
            $table->foreignId('change_id')->nullable()->constrained('competitor_changes')->onDelete('set null');
            $table->enum('insight_type', ['strategic', 'tactical', 'threat', 'opportunity']);
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->string('title');
            $table->text('summary'); // AI-generated summary
            $table->longText('analysis'); // Full AI analysis
            $table->json('recommendations')->nullable(); // Actionable recommendations
            $table->json('affected_areas')->nullable(); // ["pricing", "features", "marketing"]
            $table->decimal('confidence_score', 3, 2)->default(0.00); // 0.00 to 1.00
            $table->enum('generated_by', ['ai', 'manual'])->default('ai');
            $table->timestamp('generated_at');
            $table->boolean('is_dismissed')->default(false);
            $table->timestamp('dismissed_at')->nullable();
            $table->foreignId('dismissed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['tenant_id', 'competitor_id']);
            $table->index(['priority', 'is_dismissed']);
            $table->index('generated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competitor_insights');
    }
};
