<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_sync_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_account_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['pending', 'running', 'completed', 'failed'])->default('pending');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('emails_synced')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamps();
            
            $table->index(['email_account_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_sync_jobs');
    }
};
