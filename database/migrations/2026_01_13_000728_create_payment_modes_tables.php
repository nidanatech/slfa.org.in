<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('payment_modes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable(); // Bank details etc
            $table->boolean('is_active')->default(true);
            $table->boolean('show_on_pdf')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('sale_agent_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('payment_mode_id')->nullable()->constrained('payment_modes')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['sale_agent_id']);
            $table->dropColumn('sale_agent_id');
            $table->dropForeign(['payment_mode_id']);
            $table->dropColumn('payment_mode_id');
        });

        Schema::dropIfExists('payment_modes');
    }
};
