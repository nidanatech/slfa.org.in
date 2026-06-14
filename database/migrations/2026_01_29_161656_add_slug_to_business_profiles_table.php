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
        Schema::table('business_profiles', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('friendly_name');
        });

        // Backfill slugs
        $profiles = \Illuminate\Support\Facades\DB::table('business_profiles')->get();
        foreach ($profiles as $profile) {
            $slug = \Illuminate\Support\Str::slug($profile->friendly_name ?: 'business');
            
            // Handle collision
            $originalSlug = $slug;
            $count = 1;
            while (\Illuminate\Support\Facades\DB::table('business_profiles')->where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $count++;
            }

            \Illuminate\Support\Facades\DB::table('business_profiles')
                ->where('id', $profile->id)
                ->update(['slug' => $slug]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_profiles', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
