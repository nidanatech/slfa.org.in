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
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'product_id')) {
                $table->foreignId('product_id')->nullable()->after('contact_id')->constrained()->nullOnDelete();
            }
            if (!Schema::hasColumn('payments', 'product_variation_id')) {
                $table->foreignId('product_variation_id')->nullable()->after('product_id')->constrained()->nullOnDelete();
            }
            if (!Schema::hasColumn('payments', 'event_id')) {
                $table->foreignId('event_id')->nullable()->after('product_variation_id')->constrained()->nullOnDelete();
            }
            if (!Schema::hasColumn('payments', 'opportunity_id')) {
                $table->foreignId('opportunity_id')->nullable()->after('event_id')->constrained()->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            //
        });
    }
};
