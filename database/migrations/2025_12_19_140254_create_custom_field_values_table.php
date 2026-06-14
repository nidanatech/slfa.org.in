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
        Schema::create('custom_field_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('object_id')->constrained('custom_field_objects')->onDelete('cascade');
            $table->unsignedBigInteger('record_id'); // Polymorphic-ish, but bounded by object_id context
            $table->foreignId('field_id')->constrained('custom_fields')->onDelete('cascade');
            
            // Value columns 
            $table->text('value_text')->nullable();
            $table->decimal('value_number', 15, 2)->nullable();
            $table->dateTime('value_date')->nullable();
            $table->boolean('value_boolean')->nullable();
            
            $table->timestamps();

            $table->index(['object_id', 'record_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_field_values');
    }
};
