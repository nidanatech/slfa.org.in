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
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->date('date_of_application')->nullable();
            $table->date('entrance_fee_received')->nullable();
            $table->date('membership_fee_received')->nullable();
            $table->string('mode_of_payment')->nullable();
            $table->string('payment_reference_number')->nullable();
            $table->string('receipt_number')->nullable();
            $table->date('payment_date')->nullable();
            
            $table->string('proposed_by')->nullable();
            $table->string('seconded_by')->nullable();
            $table->string('approved_by')->nullable();
            
            $table->date('date_of_enrollment')->nullable();
            $table->string('membership_number')->nullable();
            $table->text('admin_remarks')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'date_of_application',
                'entrance_fee_received',
                'membership_fee_received',
                'mode_of_payment',
                'payment_reference_number',
                'receipt_number',
                'payment_date',
                'proposed_by',
                'seconded_by',
                'approved_by',
                'date_of_enrollment',
                'membership_number',
                'admin_remarks'
            ]);
        });
    }
};
