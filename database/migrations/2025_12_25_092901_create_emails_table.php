<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            
            // Email identifiers
            $table->string('message_id');
            $table->string('thread_id')->nullable();
            
            // Email details
            $table->text('subject')->nullable();
            $table->string('from_address');
            $table->string('from_name')->nullable();
            $table->json('to_addresses')->nullable();
            $table->json('cc_addresses')->nullable();
            $table->json('bcc_addresses')->nullable();
            $table->string('reply_to')->nullable();
            
            // Content
            $table->longText('body_text')->nullable();
            $table->longText('body_html')->nullable();
            $table->text('snippet')->nullable();
            
            // Metadata
            $table->enum('direction', ['inbound', 'outbound']);
            $table->boolean('is_read')->default(false);
            $table->boolean('is_starred')->default(false);
            $table->boolean('is_draft')->default(false);
            $table->json('labels')->nullable();
            
            // Timestamps
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->unique(['email_account_id', 'message_id']);
            $table->index(['tenant_id', 'received_at']);
            $table->index(['email_account_id', 'is_read']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emails');
    }
};
