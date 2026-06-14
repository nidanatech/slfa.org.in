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
        Schema::create('calendars', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Dr. Sharma OPD", "Sales Demos"
            $table->enum('calendar_type', ['user', 'team', 'resource', 'widget', 'external']);
            $table->foreignId('owner_id')->constrained('users')->onDelete('cascade');
            $table->string('timezone')->default('UTC');
            $table->string('color')->nullable();
            $table->boolean('is_default')->default(false);
            $table->enum('visibility', ['private', 'team', 'public'])->default('private');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calendars');
    }
};
