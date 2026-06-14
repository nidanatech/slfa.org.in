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
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('name');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('phone')->nullable()->after('email');
            $table->string('extension')->nullable()->after('phone');
            $table->string('profile_image')->nullable()->after('extension');
            $table->foreignId('calendar_id')->nullable()->after('profile_image')->constrained('calendars')->nullOnDelete();
            $table->enum('online_status', ['active', 'inactive', 'idle'])->default('inactive')->after('calendar_id');
            $table->enum('account_status', ['active', 'inactive'])->default('active')->after('online_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['calendar_id']);
            $table->dropColumn([
                'first_name', 
                'last_name', 
                'phone', 
                'extension', 
                'profile_image', 
                'calendar_id', 
                'online_status', 
                'account_status'
            ]);
        });
    }
};
