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
        // 1. Update calendars table
        Schema::table('calendars', function (Blueprint $table) {
            $table->unsignedBigInteger('owner_id')->nullable()->change();
        });

        // 2. Rename events to calendar_events if it exists, and update schema
        if (Schema::hasTable('events') && !Schema::hasTable('calendar_events')) {
            Schema::rename('events', 'calendar_events');
        }

        Schema::table('calendar_events', function (Blueprint $table) {
            // Add new columns if they don't exist
            if (!Schema::hasColumn('calendar_events', 'description')) {
                $table->text('description')->nullable();
            }
            if (!Schema::hasColumn('calendar_events', 'mode')) {
                $table->enum('mode', ['in_person', 'phone', 'video', 'chat'])->nullable();
            }
            if (!Schema::hasColumn('calendar_events', 'meeting_link')) {
                $table->string('meeting_link')->nullable();
            }
            if (!Schema::hasColumn('calendar_events', 'opportunity_id')) {
                $table->foreignId('opportunity_id')->nullable()->constrained('opportunities')->nullOnDelete();
            }
            if (!Schema::hasColumn('calendar_events', 'created_by')) {
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('calendar_events', 'source')) {
                $table->string('source')->default('manual'); 
            }
            // Add location if missing (it was added in model previously but verify DB)
            if (!Schema::hasColumn('calendar_events', 'location')) {
                $table->string('location')->nullable();
            }
        });

        // 3. Create calendar_availability table
        Schema::create('calendar_availability', function (Blueprint $table) {
            $table->id();
            $table->foreignId('calendar_id')->constrained('calendars')->onDelete('cascade');
            $table->unsignedTinyInteger('day_of_week'); // 0-6
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamps();
        });

        // 4. Create calendar_blocks table
        Schema::create('calendar_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('calendar_id')->constrained('calendars')->onDelete('cascade');
            $table->dateTime('start_datetime');
            $table->dateTime('end_datetime');
            $table->string('reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calendar_blocks');
        Schema::dropIfExists('calendar_availability');

        Schema::table('calendar_events', function (Blueprint $table) {
            $table->dropForeign(['opportunity_id']);
            $table->dropForeign(['created_by']);
            $table->dropColumn(['description', 'mode', 'meeting_link', 'opportunity_id', 'created_by', 'source']);
        });

        Schema::rename('calendar_events', 'events');

        Schema::table('calendars', function (Blueprint $table) {
            // Reverting to not nullable is risky if data exists with nulls
            //$table->foreignId('owner_id')->nullable(false)->change(); 
        });
    }
};
