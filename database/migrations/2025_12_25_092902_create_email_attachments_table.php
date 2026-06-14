<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_id')->constrained()->cascadeOnDelete();
            $table->string('filename');
            $table->string('mime_type', 100)->nullable();
            $table->integer('size_bytes')->nullable();
            $table->string('attachment_id')->nullable();
            $table->string('file_path', 500)->nullable();
            $table->timestamps();
            
            $table->index('email_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_attachments');
    }
};
