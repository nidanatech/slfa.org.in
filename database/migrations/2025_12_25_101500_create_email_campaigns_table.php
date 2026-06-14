<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('email_account_id')->constrained()->cascadeOnDelete();
            
            $table->string('name');
            $table->enum('campaign_type', ['newsletter', 'promotional', 'announcement'])->default('newsletter');
            
            $table->string('subject');
            $table->text('preheader')->nullable();
            $table->string('from_name')->nullable();
            $table->string('reply_to')->nullable();
            
            $table->longText('content_html')->nullable();
            $table->longText('content_text')->nullable();
            $table->unsignedBigInteger('template_id')->nullable();
            
            $table->enum('status', ['draft', 'scheduled', 'sending', 'sent', 'paused', 'failed'])->default('draft');
            
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            
            // Stats
            $table->integer('total_recipients')->default(0);
            $table->integer('sent_count')->default(0);
            $table->integer('delivered_count')->default(0);
            $table->integer('opened_count')->default(0);
            $table->integer('clicked_count')->default(0);
            $table->integer('bounced_count')->default(0);
            $table->integer('unsubscribed_count')->default(0);
            
            $table->timestamps();
            
            $table->index(['tenant_id', 'status']);
            $table->index('scheduled_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_campaigns');
    }
};
