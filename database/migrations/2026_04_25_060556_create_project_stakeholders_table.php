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
        Schema::create('project_stakeholders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('opportunity_id'); // project
            $table->unsignedBigInteger('contact_id');     // person
            $table->string('role')->nullable();
            $table->integer('influence')->default(5);     // 1-10
            $table->integer('interest')->default(5);      // 1-10
            $table->text('strategy')->nullable();
            $table->string('classification')->nullable(); // e.g. Maintain Satisfied
            $table->timestamps();

            $table->foreign('opportunity_id')->references('id')->on('opportunities')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_stakeholders');
    }
};
