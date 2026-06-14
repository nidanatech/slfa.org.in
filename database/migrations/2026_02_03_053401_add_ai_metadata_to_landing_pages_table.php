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
                if (!Schema::hasColumn('landing_pages', 'generation_prompt')) {
                    $table->text('generation_prompt')->nullable();
                }
                if (!Schema::hasColumn('landing_pages', 'brand_context')) {
                    $table->json('brand_context')->nullable();
                }
                if (!Schema::hasColumn('landing_pages', 'language')) {
                    $table->string('language')->default('en');
                }
                if (!Schema::hasColumn('landing_pages', 'seo_metadata')) {
                    $table->json('seo_metadata')->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('landing_pages')) {
            Schema::table('landing_pages', function (Blueprint $table) {
                $columns = [];
                if (Schema::hasColumn('landing_pages', 'generation_prompt')) $columns[] = 'generation_prompt';
                if (Schema::hasColumn('landing_pages', 'brand_context')) $columns[] = 'brand_context';
                if (Schema::hasColumn('landing_pages', 'language')) $columns[] = 'language';
                if (Schema::hasColumn('landing_pages', 'seo_metadata')) $columns[] = 'seo_metadata';
                
                if (!empty($columns)) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};
