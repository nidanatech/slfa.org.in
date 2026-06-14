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
        Schema::create('channel_types', function (Blueprint $table) {
            $table->string('id')->primary(); // e.g. 'whatsapp', 'messenger'
            $table->string('name');
            $table->string('icon')->nullable();
            $table->boolean('is_global_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('channel_types');
    }
};
