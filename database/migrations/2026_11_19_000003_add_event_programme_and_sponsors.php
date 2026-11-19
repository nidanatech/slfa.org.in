<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('venue_name')->nullable();
            $table->text('venue_address')->nullable();
            $table->string('timezone')->default('Asia/Kolkata');
            $table->string('dress_code')->nullable();
            $table->unsignedInteger('adult_price')->nullable();
            $table->unsignedInteger('child_price')->nullable();
            $table->string('child_age_range')->nullable();
            $table->string('contact_details')->nullable();
        });

        Schema::create('event_programme_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('time', 5);
            $table->string('title');
            $table->text('details')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('event_sponsors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('website_url', 2048)->nullable();
            $table->string('logo_path')->nullable();
            $table->string('tier')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_sponsors');
        Schema::dropIfExists('event_programme_items');
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['venue_name', 'venue_address', 'timezone', 'dress_code', 'adult_price', 'child_price', 'child_age_range', 'contact_details']);
        });
    }
};
