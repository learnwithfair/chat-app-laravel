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
        Schema::create('participants', function (Blueprint $table) {
            $table->id();

            $table->foreignId('conversation_id')->constrained()->onDelete('cascade');

            $table->unsignedBigInteger('participant_id');
            $table->string('participant_type');

            $table->enum('role', ['admin', 'super_admin', 'member'])->default('member');
            $table->boolean('is_muted')->default(false);
            $table->timestamp('joined_at')->nullable();

            $table->timestamps();

            $table->index(['participant_type', 'participant_id']);
            $table->unique(['conversation_id', 'participant_type', 'participant_id'], 'conv_participant_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('participants');
    }
};
