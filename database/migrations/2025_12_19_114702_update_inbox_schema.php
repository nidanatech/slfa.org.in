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
        // 1. Add Columns to Conversations
        Schema::table('conversations', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_important')->default(false);
            $table->boolean('is_read')->default(true); // Default true for now
            // 'notes_id' - Keeping this simple unless user specifies creating a Notes table. 
            // Usually notes are HasMany. User said "link to notes model". I'll add notes_id assuming 1:1 or 1:Many inverse.
            // Actually, better to just allow conversations to have many notes. 
            // But if user requested "Link to notes model" inside conversation, maybe they want a specific main note?
            // Let's assume HasMany notes for now and skip notes_id, OR if they meant "link TO notes model" 
            // they might mean the conversation row has a link. 
            // I'll skip adding a column here and assume we'll just query Notes::where('conversation_id', $id).
        });

        // 2. Add Columns to Messages
        Schema::table('messages', function (Blueprint $table) {
            $table->string('sender_type')->default('user'); // user, contact, bot
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('seen_at')->nullable();
            $table->softDeletes();
        });

        // 3. Create Tags Table
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('color')->default('#cbd5e1'); // slate-300
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        // 4. Create Conversation Tags Pivot
        Schema::create('conversation_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        // 5. Create Conversation Followers Pivot (Many Users follow one Conversation)
        Schema::create('conversation_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversation_user');
        Schema::dropIfExists('conversation_tag');
        Schema::dropIfExists('tags');

        Schema::table('messages', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn(['sender_type', 'delivered_at', 'seen_at']);
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn(['created_by', 'is_important', 'is_read']);
        });
    }
};
