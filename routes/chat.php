<?php

use App\Http\Controllers\Web\V1\Chat\ChatController;
use App\Http\Controllers\Web\V1\Chat\ConversationController;
use App\Http\Controllers\Web\V1\Chat\GroupController;
use App\Http\Controllers\Web\V1\Chat\MessageController;
use App\Http\Controllers\Web\V1\Chat\ReactionController;
use App\Http\Controllers\Web\V1\Chat\UserBlockController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('chat')->name('chat.')->group(function () {

// -------------------- Conversations --------------------
    Route::get('/', [ChatController::class, 'index'])->name('index');                             // List conversations
    Route::post('/start', [ConversationController::class, 'start'])->name('conversations.start'); // Start private conversation
    Route::delete('/{conversation}', [ConversationController::class, 'delete'])->name('conversations.delete');

    // -------------------- Messages --------------------
    Route::post('messages', [MessageController::class, 'store'])->name('messages.store');
    Route::get('messages', [MessageController::class, 'store'])->name('messages.show');
    Route::put('messages/{message}', [MessageController::class, 'update'])->name('messages.update');
    Route::delete('messages/{message}', [MessageController::class, 'deleteForMe'])->name('messages.delete');

    Route::get('messages/seen/{conversation}', [MessageController::class, 'markAsSeen'])->name('messages.seen');
    Route::get('messages/delivered/{conversation}', [MessageController::class, 'markAsDelivered'])->name('messages.delivered');

    // -------------------- Reactions --------------------
    Route::post('messages/{message}/reaction', [ReactionController::class, 'toggleReaction'])->name('reactions.toggle');
    Route::get('messages/{message}/reaction', [ReactionController::class, 'index'])->name('reactions.index');

    // -------------------- Group Management --------------------
    Route::prefix('group/{conversation}')->group(function () {
        Route::post('update', [GroupController::class, 'update'])->name('group.update');
        Route::post('members/add', [GroupController::class, 'addMembers'])->name('group.members.add');
        Route::post('members/remove', [GroupController::class, 'removeMember'])->name('group.members.remove');
        Route::get('members', [GroupController::class, 'getMembers'])->name('group.members.show');
        Route::post('admins/add', [GroupController::class, 'addAdmins'])->name('group.admins.add');
        Route::post('admins/remove', [GroupController::class, 'removeAdmins'])->name('group.admins.remove');
        Route::post('mute', [GroupController::class, 'muteToggleGroup'])->name('group.mute');
        Route::post('leave', [GroupController::class, 'leaveGroup'])->name('group.leave');
    });

    // -------------------- Block / Restrict --------------------
    Route::post('users/{user}/block-toggle', [UserBlockController::class, 'toggleBlock'])->name('users.toggleBlock');
    Route::post('users/{user}/restrict-toggle', [UserBlockController::class, 'toggleRestrict'])->name('users.toggleRestrict');
});
