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
        Schema::create('opportunities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->foreignId('pipeline_id')->constrained()->onDelete('restrict');
            $table->foreignId('stage_id')->constrained('pipeline_stages')->onDelete('restrict');
            $table->foreignId('contact_id')->constrained()->onDelete('cascade');
            $table->foreignId('company_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('owner_id')->nullable()->constrained('users')->onDelete('set null');
            $table->decimal('value_amount', 15, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->date('expected_close_date')->nullable();
            $table->enum('status', ['open', 'won', 'lost'])->default('open');
            $table->enum('source', ['chat', 'manual', 'api'])->default('manual');
            $table->timestamps();
            $table->timestamp('closed_at')->nullable();
            
            $table->index(['tenant_id', 'status']);
            $table->index(['pipeline_id', 'stage_id']);
            $table->index('owner_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('opportunities');
    }
};
