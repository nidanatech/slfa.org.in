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
        Schema::create('user_invitations', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('role_name'); // Role to assign upon acceptance
            $table->string('token', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamps();

            // Ensure one active invitation per email per tenant?
            // Or just allow multiples. Simple is fine.
            $table->index(['email', 'tenant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_invitations');
    }
};
