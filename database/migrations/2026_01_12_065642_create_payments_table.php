<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('payment_number', 50);
            
            // Relationships
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('contact_id')->constrained()->restrictOnDelete();
            
            // Payment Details
            $table->date('payment_date');
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('USD');
            
            // Payment Method
            $table->enum('payment_method', ['cash', 'check', 'bank_transfer', 'credit_card', 'debit_card', 'upi', 'paypal', 'stripe', 'other']);
            $table->json('payment_method_details')->nullable();
            
            // Transaction Info
            $table->string('transaction_id')->nullable();
            $table->string('reference_number')->nullable();
            
            // Type
            $table->enum('type', ['received', 'sent']);
            
            // Status
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('completed');
            
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            $table->unique(['tenant_id', 'payment_number']);
            $table->index(['tenant_id', 'payment_date']);
            $table->index(['tenant_id', 'contact_id']);
            $table->index('invoice_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
