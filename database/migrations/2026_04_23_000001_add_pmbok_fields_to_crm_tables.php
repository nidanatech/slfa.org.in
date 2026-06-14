<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->string('portal_token', 64)->nullable()->unique()->after('status');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->string('milestone_name')->nullable()->after('status');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->string('milestone_name')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->dropColumn('portal_token');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('milestone_name');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('milestone_name');
        });
    }
};
