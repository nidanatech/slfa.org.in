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
        Schema::table('opportunity_charters', function (Blueprint $table) {
            $table->string('version_number')->default('1.0')->after('opportunity_id');
            $table->boolean('is_current')->default(true)->after('version_number');
            $table->unsignedBigInteger('supersedes_id')->nullable()->after('is_current');
            $table->text('change_summary')->nullable()->after('supersedes_id'); // Why was this version created?
        });

        Schema::create('project_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('opportunity_id')->constrained()->onDelete('cascade');
            $table->foreignId('charter_id')->constrained('opportunity_charters')->onDelete('cascade'); // Request against this version
            $table->text('requested_changes');
            $table->text('justification');
            $table->json('attachments')->nullable(); // Paths to files
            $table->enum('status', ['pending', 'processed', 'rejected'])->default('pending');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_change_requests');
        Schema::table('opportunity_charters', function (Blueprint $table) {
            $table->dropColumn(['version_number', 'is_current', 'supersedes_id', 'change_summary']);
        });
    }
};
