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
        Schema::create('project_artifacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('opportunities')->onDelete('cascade');
            $table->string('step_key')->index(); // e.g., req_overview, scope_definition
            $table->string('artifact_type')->index(); // e.g., meeting_invite, charter_draft, scope_matrix
            $table->json('form_data')->nullable(); // Stores the input values
            $table->longText('generated_content')->nullable(); // Stores the AI synthesis
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_artifacts');
    }
};
