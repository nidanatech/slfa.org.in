<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('landing_pages')) {
            Schema::table('landing_pages', function (Blueprint $table) {
                if (!Schema::hasColumn('landing_pages', 'views_count')) {
                    $table->unsignedBigInteger('views_count')->default(0);
                }
                if (!Schema::hasColumn('landing_pages', 'submissions_count')) {
                    $table->unsignedBigInteger('submissions_count')->default(0);
                }
                if (!Schema::hasColumn('landing_pages', 'clicks_count')) {
                    $table->unsignedBigInteger('clicks_count')->default(0);
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('landing_pages')) {
            Schema::table('landing_pages', function (Blueprint $table) {
                $columns = [];
                if (Schema::hasColumn('landing_pages', 'views_count')) $columns[] = 'views_count';
                if (Schema::hasColumn('landing_pages', 'submissions_count')) $columns[] = 'submissions_count';
                if (Schema::hasColumn('landing_pages', 'clicks_count')) $columns[] = 'clicks_count';
                
                if (!empty($columns)) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};
