<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('conversation_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->cascadeOnDelete();
            $table->enum('role', ['admin', 'member', 'super_admin'])->default('member');
            $table->boolean('is_muted')->default(false);
            $table->timestamp('muted_until')->nullable()->comment('minutes = -1 means muted forever');
            $table->boolean('is_active')->default(true);
            $table->timestamp('left_at')->nullable();
            $table->timestamp('removed_at')->nullable();
            $table->timestamp('deleted_at')->nullable()->comment('Timestamp when the conversation was deleted for this user');
            $table->unsignedBigInteger('last_read_message_id')->nullable();
            $table->unsignedBigInteger('last_deleted_message_id')->nullable()->comment('Messages before this id are considered deleted conversatoion for this user');
            $table->timestamps();

            $table->index(['is_muted', 'muted_until'], 'idx_muted_conversations');
            $table->index(['user_id', 'conversation_id'], 'idx_user_conversation');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversation_participants', function (Blueprint $table) {
            $table->dropIndex('idx_muted_conversations');
            $table->dropIndex('idx_user_conversation');
        });
    }
};
