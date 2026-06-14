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
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('object_type'); // contact | conversation | event | opportunity | company
            $table->unsignedBigInteger('object_id');
            $table->index(['object_type', 'object_id']);

            $table->foreignId('author_id')->constrained('users')->onDelete('cascade');
            
            $table->text('content'); // text / rich text
            $table->string('visibility')->default('internal'); // internal | external
            $table->string('source')->default('manual'); // manual | system | automation | api
            
            $table->timestamps();
        });

        Schema::create('note_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('note_id')->constrained()->onDelete('cascade');
            $table->string('file_url');
            $table->string('file_type')->nullable(); // image / pdf / etc.
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('note_attachments');
        Schema::dropIfExists('notes');
    }
};
