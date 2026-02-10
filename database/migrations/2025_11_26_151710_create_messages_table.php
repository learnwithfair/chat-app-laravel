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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('receiver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('conversation_id')->constrained('conversations')->cascadeOnDelete();

            $table->foreignId('reply_to_message_id')->nullable()->constrained('messages')->cascadeOnDelete();
            $table->foreignId('forward_to_message_id')->nullable()->constrained('messages')->cascadeOnDelete();

            $table->text('message')->nullable();

            $table->enum('message_type', ['text', 'image', 'video', 'audio', 'file', 'multiple', 'system'])->default('text');

            $table->boolean('is_deleted_for_everyone')->default(false);
            $table->boolean('is_restricted')->default(false);
            $table->boolean('is_pinned')->default(false);
            $table->timestamp('edited_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
