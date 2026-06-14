<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_post_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('social_post_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            
            // Approval Action
            $table->enum('action', ['requested', 'approved', 'rejected', 'changes_requested']);
            $table->text('comment')->nullable();
            
            // Status Tracking
            $table->string('previous_status')->nullable();
            $table->string('new_status')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['social_post_id', 'created_at']);
            $table->index('user_id');
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_post_approvals');
    }
};
