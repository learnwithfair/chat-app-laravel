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
        Schema::create('group_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->string('avatar')->nullable();
            $table->text('description')->nullable();
            $table->enum('type', ['public', 'private'])->default('private');
            $table->boolean('allow_members_to_send_messages')->default(true);
            $table->boolean('allow_members_to_add_remove_participants')->default(true);
            $table->boolean('allow_members_to_change_group_info')->default(true);
            $table->boolean('admins_must_approve_new_members')->default(true);
            $table->boolean('allow_invite_users_via_link')->default(true);
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_settings');
    }
};
