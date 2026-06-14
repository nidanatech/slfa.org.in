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
        Schema::create('conversation_referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            
            // Referral Source Information
            $table->string('source')->nullable();        // ADS, SHORTLINK, CUSTOMER_CHAT_PLUGIN, etc.
            $table->string('type')->nullable();          // OPEN_THREAD, etc.
            $table->string('ref')->nullable();           // Custom ref parameter from m.me links
            
            // Ad-specific data (when source = ADS)
            $table->string('ad_id')->nullable();
            $table->text('ad_title')->nullable();
            $table->string('post_id')->nullable();
            $table->text('photo_url')->nullable();
            
            // Additional metadata
            $table->json('metadata')->nullable();        // Store full referral object
            
            $table->timestamps();
            
            $table->index(['conversation_id']);
            $table->index(['tenant_id', 'source']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversation_referrals');
    }
};
