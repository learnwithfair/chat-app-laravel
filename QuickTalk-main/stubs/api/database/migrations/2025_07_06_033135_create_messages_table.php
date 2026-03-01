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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('users');
            $table->foreignId('receiver_id')->nullable()->constrained('users');
            $table->foreignId('conversation_id')->constrained('conversations')->cascadeOnDelete();

            $table->foreignId('reply_to_message_id')->nullable()->constrained('messages')->onDelete('cascade');

            $table->text('message')->nullable();

            $table->enum('message_type', ['text', 'image', 'video', 'audio', 'file', 'multiple', 'system'])->default('text');

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
