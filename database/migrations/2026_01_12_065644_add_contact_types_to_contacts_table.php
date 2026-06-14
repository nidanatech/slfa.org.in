<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->json('contact_types')->nullable()->after('contact_type');
        });
        
        // Migrate existing contact_type to contact_types array
        DB::statement("
            UPDATE contacts 
            SET contact_types = JSON_ARRAY(contact_type) 
            WHERE contact_type IS NOT NULL
        ");
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn('contact_types');
        });
    }
};
