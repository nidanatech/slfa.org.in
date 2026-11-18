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
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            $table->enum('membership_type', ['individual', 'business'])->default('individual');
            $table->text('address')->nullable(); // Residential or Registered Office
            $table->string('landline')->nullable();
            $table->string('mobile')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('personal_email')->nullable(); // Extra email besides User email
            
            // Individual Specific
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['Male', 'Female', 'Others'])->nullable();
            $table->string('occupation')->nullable();
            
            // Business Specific
            $table->string('business_name')->nullable();
            $table->enum('nature_of_business', ['Company', 'LLP', 'Society', 'Firm', 'Others'])->nullable();
            $table->date('date_of_incorporation')->nullable();
            
            // Connection with SL (Store as JSON array or string)
            $table->json('connection_with_srilanka')->nullable(); 
            
            $table->string('referred_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
};
