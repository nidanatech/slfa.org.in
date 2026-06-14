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
        if (Schema::hasTable('landing_page_submissions')) {
            Schema::table('landing_page_submissions', function (Blueprint $table) {
                if (!Schema::hasColumn('landing_page_submissions', 'landing_page_id')) {
                    $table->foreignId('landing_page_id')->after('id')->constrained()->onDelete('cascade');
                }
                if (!Schema::hasColumn('landing_page_submissions', 'form_data')) {
                    $table->json('form_data')->after('landing_page_id');
                }
                if (!Schema::hasColumn('landing_page_submissions', 'ip_address')) {
                    $table->string('ip_address')->nullable()->after('form_data');
                }
                if (!Schema::hasColumn('landing_page_submissions', 'user_agent')) {
                    $table->string('user_agent')->nullable()->after('ip_address');
                }
            });
        } else {
            Schema::create('landing_page_submissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('landing_page_id')->constrained()->onDelete('cascade');
                $table->json('form_data');
                $table->string('ip_address')->nullable();
                $table->string('user_agent')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('landing_page_submissions');
    }
};
