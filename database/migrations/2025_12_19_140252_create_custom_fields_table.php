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
        Schema::create('custom_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('object_id')->constrained('custom_field_objects')->onDelete('cascade');
            $table->foreignId('folder_id')->nullable()->constrained('field_folders')->onDelete('set null');
            $table->string('name');
            $table->string('key'); // Internal key e.g. 'business.vat_number'
            $table->enum('field_type', ['text', 'number', 'email', 'select', 'date', 'boolean', 'file']);
            $table->json('options_json')->nullable(); // For select/multiselect
            $table->boolean('is_required')->default(false);
            $table->boolean('is_unique')->default(false);
            $table->boolean('is_system')->default(false);
            $table->boolean('is_deleted')->default(false);
            $table->timestamps();

            // Ensure keys are unique within an object context
            $table->unique(['object_id', 'key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_fields');
    }
};
