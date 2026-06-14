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
        Schema::create('sender_identities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('identity_type'); // 'email' or 'domain'
            $table->string('identity_value'); // 'foo@bar.com' or 'bar.com'
            $table->string('status')->default('pending'); // pending, success, failed
            $table->boolean('is_default')->default(false);
            
            // Verification Data
            $table->text('verification_token')->nullable(); // Legacy TXT or Email check status
            $table->json('dkim_attributes')->nullable(); // Array of CNAME tokens
            
            $table->timestamps();

            $table->unique(['tenant_id', 'identity_value']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sender_identities');
    }
};
