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
        Schema::create('brand_voices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Business Information
            $table->string('brand_name');
            $table->string('business_type');
            $table->string('company_website')->nullable();
            
            // Contact Information
            $table->string('company_email')->nullable();
            $table->text('company_address')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('business_hours')->nullable();
            
            // Marketing Position
            $table->text('target_audience')->nullable();
            $table->text('customer_pain_points')->nullable();
            $table->text('brand_promise')->nullable();
            $table->text('brand_values')->nullable();
            $table->text('brand_description')->nullable();
            $table->text('competitive_advantage')->nullable();
            $table->text('unique_selling_proposition')->nullable();
            $table->text('risks_of_inaction')->nullable();
            $table->text('call_to_action')->nullable();
            
            // Metadata
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['tenant_id', 'is_active']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('brand_voices');
    }
};
