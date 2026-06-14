<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('contract_number', 50);
            
            // Relationships
            $table->foreignId('contact_id')->constrained()->restrictOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            
            // Contract Details
            $table->string('title');
            $table->text('description')->nullable();
            
            // Dates
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('signed_date')->nullable();
            
            // Renewal
            $table->boolean('auto_renew')->default(false);
            $table->enum('renewal_period', ['monthly', 'quarterly', 'yearly'])->nullable();
            $table->integer('renewal_notice_days')->nullable();
            
            // Financial
            $table->decimal('contract_value', 15, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->enum('billing_frequency', ['one_time', 'monthly', 'quarterly', 'yearly'])->nullable();
            
            // Status
            $table->enum('status', ['draft', 'pending_signature', 'active', 'expired', 'cancelled', 'renewed'])->default('draft');
            
            // Documents
            $table->string('document_url', 500)->nullable();
            
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            
            $table->timestamps();
            
            $table->unique(['tenant_id', 'contract_number']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'start_date', 'end_date']);
            $table->index(['tenant_id', 'contact_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
