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
        Schema::table('tasks', function (Blueprint $table) {
            // Renaming
            $table->renameColumn('assigned_to', 'owner_id');
            $table->renameColumn('related_type', 'object_type');
            $table->renameColumn('related_id', 'object_id');

            // New Columns
            $table->string('status')->default('open')->after('description'); // open | in_progress | completed | cancelled
            $table->string('priority')->default('medium')->after('status'); // low | medium | high | urgent
            $table->string('source')->default('manual')->after('priority'); // manual | automation | system
            
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->after('owner_id');
            $table->timestamp('completed_at')->nullable()->after('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
             // Reversing renames
             $table->renameColumn('owner_id', 'assigned_to');
             $table->renameColumn('object_type', 'related_type');
             $table->renameColumn('object_id', 'related_id');
 
             // Dropping columns
             $table->dropColumn(['status', 'priority', 'source', 'created_by', 'completed_at']);
             $table->dropForeign(['created_by']);
        });
    }
};
