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
        Schema::create('business_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            
            // Branding & Basic Info
            $table->string('logo_url')->nullable();
            $table->string('friendly_name')->nullable();
            $table->string('legal_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('branded_domain')->nullable();
            $table->string('website')->nullable();
            
            // Localization
            $table->string('currency')->default('USD');
            $table->string('timezone')->default('UTC');
            
            // Physical Address
            $table->string('address_street')->nullable();
            $table->string('address_city')->nullable();
            $table->string('address_state')->nullable();
            $table->string('address_zip')->nullable();
            $table->string('address_country')->nullable();
            
            // Authorized Representative (Stored as JSON)
            // { first_name, last_name, email, job_position, phone }
            $table->json('authorized_representative')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_profiles');
    }
};
