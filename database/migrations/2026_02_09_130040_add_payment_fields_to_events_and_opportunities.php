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
        Schema::table('calendar_events', function (Blueprint $table) {
            $table->string('payment_status')->nullable()->after('status');
            $table->string('stripe_session_id')->nullable()->after('payment_status');
        });

        Schema::table('opportunities', function (Blueprint $table) {
            $table->string('payment_status')->nullable()->after('status');
            $table->string('stripe_session_id')->nullable()->after('payment_status');
            $table->foreignId('product_id')->nullable()->after('contact_id')->constrained('products')->onDelete('set null');
            $table->foreignId('product_variation_id')->nullable()->after('product_id')->constrained('product_variations')->onDelete('set null');
        });

        Schema::table('calendars', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->constrained('products')->onDelete('set null');
            $table->foreignId('product_variation_id')->nullable()->constrained('product_variations')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calendar_events', function (Blueprint $table) {
            $table->dropColumn(['payment_status', 'stripe_session_id']);
        });

        Schema::table('opportunities', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropForeign(['product_variation_id']);
            $table->dropColumn(['payment_status', 'stripe_session_id', 'product_id', 'product_variation_id']);
        });

        Schema::table('calendars', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropForeign(['product_variation_id']);
            $table->dropColumn(['product_id', 'product_variation_id']);
        });
    }
};
