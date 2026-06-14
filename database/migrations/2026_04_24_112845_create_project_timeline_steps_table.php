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
        Schema::create('project_timeline_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('opportunity_id')->constrained('opportunities')->onDelete('cascade');
            $table->string('phase'); // INITIATION, PLANNING, EXECUTION, MONITORING, CLOSING
            $table->string('key'); // unique key for the step (e.g. develop_charter)
            $table->string('label');
            $table->text('description')->nullable();
            $table->string('status')->default('pending'); // pending, current, complete
            $table->integer('order_position')->default(0);
            $table->string('action_route')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['opportunity_id', 'phase']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_timeline_steps');
    }
};
