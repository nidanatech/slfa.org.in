<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            
            $table->string('name');
            $table->string('category')->nullable();
            $table->text('description')->nullable();
            
            $table->longText('content_html');
            $table->longText('content_text')->nullable();
            $table->string('thumbnail_url')->nullable();
            
            $table->boolean('is_default')->default(false);
            $table->boolean('is_system')->default(false);
            
            $table->timestamps();
            
            $table->index(['tenant_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};
