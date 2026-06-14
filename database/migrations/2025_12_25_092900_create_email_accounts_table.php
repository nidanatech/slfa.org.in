<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->enum('provider', ['gmail', 'outlook']);
            $table->string('email_address');
            $table->string('display_name')->nullable();
            
            // OAuth tokens (encrypted)
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            
            // Provider-specific IDs
            $table->string('provider_account_id')->nullable();
            
            // Sync settings
            $table->boolean('sync_enabled')->default(true);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamp('sync_from_date')->nullable();
            
            // Status
            $table->enum('status', ['active', 'disconnected', 'error'])->default('active');
            $table->text('error_message')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->unique(['user_id', 'email_address']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_accounts');
    }
};
