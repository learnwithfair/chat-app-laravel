<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversation_invites', function (Blueprint $table) {
            $table->id();

            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();

            $table->string('token', 64)->unique();
            $table->timestamp('expires_at')->nullable(); // null = never expire
            $table->integer('max_uses')->nullable(); // null = unlimited

            $table->integer('used_count')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['conversation_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversation_invites');
    }
};
