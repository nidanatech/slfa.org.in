<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('email_campaigns')->cascadeOnDelete();
            $table->foreignId('recipient_id')->constrained('campaign_recipients')->cascadeOnDelete();
            
            $table->text('url');
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            
            $table->timestamp('clicked_at');
            
            $table->index(['campaign_id', 'clicked_at']);
            $table->index('recipient_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_clicks');
    }
};
