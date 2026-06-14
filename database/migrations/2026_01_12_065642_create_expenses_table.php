<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('expense_number', 50);
            
            // Relationships
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            
            // Expense Details
            $table->date('expense_date');
            $table->string('category', 100);
            
            // Amounts
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('USD');
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2);
            
            // Tax
            $table->enum('tax_type', ['none', 'us_sales_tax', 'india_gst'])->default('none');
            $table->decimal('tax_rate', 5, 2)->nullable();
            $table->json('tax_details')->nullable();
            
            // Payment
            $table->enum('payment_method', ['cash', 'check', 'bank_transfer', 'credit_card', 'debit_card', 'upi', 'other']);
            $table->enum('payment_status', ['pending', 'paid', 'reimbursed'])->default('pending');
            
            // Documentation
            $table->string('receipt_url', 500)->nullable();
            $table->text('description');
            $table->text('notes')->nullable();
            
            // Categorization
            $table->boolean('billable')->default(false);
            $table->boolean('reimbursable')->default(false);
            
            $table->timestamps();
            
            $table->unique(['tenant_id', 'expense_number']);
            $table->index(['tenant_id', 'expense_date']);
            $table->index(['tenant_id', 'category']);
            $table->index(['tenant_id', 'contact_id']);
            $table->index(['tenant_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
