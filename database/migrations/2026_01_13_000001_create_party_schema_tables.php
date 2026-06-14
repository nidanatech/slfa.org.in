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
        // 1. Create Parties Table
        Schema::create('parties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete(); // Link to existing companies table
            $table->enum('party_type', ['individual', 'company'])->default('individual');
            $table->string('name');
            $table->string('gstin')->nullable();
            $table->string('pan')->nullable();
            $table->string('currency')->default('USD');
            $table->string('payment_terms')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. Create Normalized Addresses Table
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('line1');
            $table->string('line2')->nullable();
            $table->string('city');
            $table->string('state');
            $table->string('country');
            $table->string('postal_code');
            $table->timestamps();
            $table->softDeletes();
        });

        // 3. Party <-> Address Pivot
        Schema::create('party_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('party_id')->constrained('parties')->cascadeOnDelete();
            $table->foreignId('address_id')->constrained('addresses')->cascadeOnDelete();
            $table->enum('address_type', ['billing', 'shipping']);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        // 4. Party <-> Contact Pivot
        Schema::create('party_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('party_id')->constrained('parties')->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->boolean('receives_invoice')->default(false);
            $table->timestamps();
        });

        // 5. Update Invoices Table
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('party_id')->nullable()->after('tenant_id')->constrained('parties')->nullOnDelete();
            $table->foreignId('billing_address_id')->nullable()->after('party_id')->constrained('addresses')->nullOnDelete();
            $table->foreignId('shipping_address_id')->nullable()->after('billing_address_id')->constrained('addresses')->nullOnDelete();
            
            // Snapshots
            $table->string('gstin_snapshot')->nullable()->after('shipping_address_id');
            $table->json('address_snapshot')->nullable()->after('gstin_snapshot');
            
            // Make contact_id nullable if not already (it might be required currently)
            $table->unsignedBigInteger('contact_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['party_id']);
            $table->dropForeign(['billing_address_id']);
            $table->dropForeign(['shipping_address_id']);
            $table->dropColumn(['party_id', 'billing_address_id', 'shipping_address_id', 'gstin_snapshot', 'address_snapshot']);
            $table->unsignedBigInteger('contact_id')->nullable(false)->change();
        });

        Schema::dropIfExists('party_contacts');
        Schema::dropIfExists('party_addresses');
        Schema::dropIfExists('addresses');
        Schema::dropIfExists('parties');
    }
};
