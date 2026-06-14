<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opportunity_charters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opportunity_id')->constrained('opportunities')->onDelete('cascade');
            $table->text('objectives')->nullable();
            $table->text('deliverables')->nullable();
            $table->text('in_scope')->nullable();
            $table->text('out_of_scope')->nullable();
            $table->text('assumptions')->nullable();
            $table->text('constraints')->nullable();
            $table->string('document_url')->nullable();
            $table->boolean('is_signed')->default(false);
            $table->timestamp('signed_at')->nullable();
            $table->string('signed_by_ip')->nullable();
            $table->string('signature_hash')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opportunity_charters');
    }
};
