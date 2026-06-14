<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            
            // Item Details
            $table->text('description');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('unit_price', 15, 2);
            
            // Tax per item (for India GST)
            $table->decimal('tax_rate', 5, 2)->nullable();
            $table->decimal('tax_amount', 15, 2)->default(0);
            
            // Totals
            $table->decimal('subtotal', 15, 2);
            $table->decimal('total', 15, 2);
            
            // Optional categorization
            $table->string('item_type', 100)->nullable();
            
            // India GST specific
            $table->string('hsn_sac_code', 20)->nullable();
            
            $table->integer('sort_order')->default(0);
            
            $table->timestamps();
            
            $table->index('invoice_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
