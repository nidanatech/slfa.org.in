<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            
            // Tax Details
            $table->string('name', 100);
            $table->enum('tax_type', ['us_sales_tax', 'india_gst_cgst', 'india_gst_sgst', 'india_gst_igst']);
            $table->decimal('rate', 5, 2);
            
            // Applicability
            $table->string('country', 2);
            $table->string('state_province', 100)->nullable();
            
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            
            $table->timestamps();
            
            $table->index(['tenant_id', 'country', 'state_province']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_rates');
    }
};
