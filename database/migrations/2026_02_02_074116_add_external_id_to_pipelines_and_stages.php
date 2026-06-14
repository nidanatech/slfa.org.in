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
        Schema::table('pipelines', function (Blueprint $table) {
            if (!Schema::hasColumn('pipelines', 'external_id')) {
                $table->string('external_id')->nullable()->after('tenant_id');
            }
            if (!Schema::hasColumn('pipelines', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
        });

        Schema::table('pipeline_stages', function (Blueprint $table) {
            if (!Schema::hasColumn('pipeline_stages', 'external_id')) {
                $table->string('external_id')->nullable()->after('pipeline_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pipelines', function (Blueprint $table) {
            $table->dropColumn(['external_id', 'is_active']);
        });

        Schema::table('pipeline_stages', function (Blueprint $table) {
            $table->dropColumn('external_id');
        });
    }
};
