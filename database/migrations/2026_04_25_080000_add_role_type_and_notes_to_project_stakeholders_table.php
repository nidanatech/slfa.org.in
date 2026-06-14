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
        Schema::table('project_stakeholders', function (Blueprint $table) {
            $table->string('role_type')->nullable()->after('role');
            $table->text('notes')->nullable()->after('classification');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_stakeholders', function (Blueprint $table) {
            $table->dropColumn(['role_type', 'notes']);
        });
    }
};
