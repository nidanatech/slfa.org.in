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
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('salutation')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('gender')->nullable(); // male, female, other
            $table->date('date_of_birth')->nullable();
            $table->string('marital_status')->nullable();
            
            $table->json('emails')->nullable();
            $table->json('phones')->nullable();
            
            $table->string('contact_type')->default('lead'); // lead, customer, partner, vendor
            $table->string('time_zone')->nullable();
            
            // DND Preferences
            $table->boolean('dnd_email')->default(false);
            $table->boolean('dnd_text')->default(false);
            $table->boolean('dnd_calls')->default(false);
            $table->boolean('dnd_inbound_calls_sms')->default(false);
            
            $table->string('contact_image')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn([
                'salutation', 'first_name', 'last_name', 'gender', 'date_of_birth', 
                'marital_status', 'emails', 'phones', 'contact_type', 'time_zone',
                'dnd_email', 'dnd_text', 'dnd_calls', 'dnd_inbound_calls_sms', 'contact_image'
            ]);
        });
    }
};
