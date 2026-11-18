<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('committee_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->nullable()->constrained()->onDelete('set null');
            $table->string('name');
            $table->string('role'); // President, VP, Secretary, Treasurer, Executive Member
            $table->string('company')->nullable();
            $table->string('designation')->nullable();
            $table->string('photo_path')->nullable();
            $table->enum('tier', ['present', 'executive'])->default('executive');
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('committee_members');
    }
};
