<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('full_name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->enum('membership_type', ['individual', 'business']);
            $table->text('sri_lanka_connection')->nullable(); // origin/ancestral/trade/education/spouse
            $table->string('occupation')->nullable();
            $table->string('company_name')->nullable();
            $table->string('designation')->nullable();
            $table->string('city')->nullable();
            $table->string('photo_path')->nullable();
            $table->enum('status', ['pending', 'active', 'expired', 'rejected'])->default('pending');
            $table->boolean('directory_visible')->default(true);
            $table->timestamp('directory_consent_at')->nullable();
            $table->boolean('show_contact_info')->default(false);
            $table->boolean('featured')->default(false);
            $table->string('member_id_string')->nullable()->unique(); // e.g. SLFA-2026-001
            $table->date('date_of_birth')->nullable();
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('renewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
