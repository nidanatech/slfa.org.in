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
        Schema::table('email_accounts', function (Blueprint $table) {
            // Change enum to string to allow 'ses' and others
            $table->string('provider')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_accounts', function (Blueprint $table) {
            // Revert back to enum if needed, but risky if data exists.
            // keeping as string is safer for down too in this context, 
            // or we'd have to list all potential values.
            // For strict reversal:
            // $table->enum('provider', ['gmail', 'outlook', 'ses'])->change(); 
        });
    }
};
