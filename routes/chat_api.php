<?php

use App\Http\Controllers\Api\V1\Chat\ConversationController;
use App\Http\Controllers\Api\V1\Chat\GroupController;
use App\Http\Controllers\Api\V1\Chat\MessageController;
use App\Http\Controllers\Api\V1\Chat\ReactionController;
use App\Http\Controllers\Api\V1\Chat\UserBlockController;
use Illuminate\Support\Facades\Route;

// Route::prefix('v1')->middleware(['auth:sanctum', 'last_seen'])->group(function () {
Route::prefix('v1')->middleware(['auth', 'verified', 'last_seen'])->group(function () {

    // -------------------- Conversations --------------------
    // create group & list conversations
    Route::get('conversations/{conversation}/media', [ConversationController::class, 'mediaLibrary']);
    Route::apiResource('conversations', ConversationController::class)->only(['index', 'store', 'destroy']);
    Route::post('conversations/private', [ConversationController::class, 'startPrivateConversation']);

    // ----------------------- Messages ------------------------------
    Route::get('messages/{conversation}/pined-messages', [MessageController::class, 'getAllPinedMessages']);
    Route::apiResource('messages', MessageController::class)->only(['store', 'show', 'update']);

    Route::prefix('messages')->controller(MessageController::class)->group(function () {
        Route::delete('delete-for-me', 'deleteForMe');
        Route::delete('delete-for-everyone', 'deleteForEveryone'); // message unsent with message type system
        Route::post('mark-seen', 'markSeen');                      // When conversation already opend
        Route::get('seen/{conversation}', 'markAsSeen');           // when open conversation
        Route::get('delivered/{conversation}', 'markAsDelivered');
        Route::post('{message}/forward', 'forward');
        Route::post('{message}/toggle-pin', 'pinToggleMessage');
    });

    // -------------------- Reactions --------------------
    Route::controller(ReactionController::class)->group(function () {
        Route::post('messages/{message}/reaction', 'toggleReaction');
        Route::get('messages/{message}/reaction', 'index');
    });

    // -------------------- Group Management --------------------
    Route::prefix('group/{conversation}')->controller(GroupController::class)->group(function () {
        Route::post('update', 'update')->name('group.update');
        Route::post('members/add', 'addMembers')->name('group.members.add');
        Route::post('members/remove', 'removeMember')->name('group.members.remove');
        Route::get('members', 'getMembers')->name('group.members.show');
        Route::post('admins/add', 'addAdmins')->name('group.admins.add');
        Route::post('admins/remove', 'removeAdmins')->name('group.admins.remove');
        Route::post('mute', 'muteToggleGroup')->name('group.mute'); // 0 = unmute, -1 = Unlimited mute, otherwise specify miniutes
        Route::post('leave', 'leaveGroup')->name('group.leave');
        Route::delete('delete-group', 'deleteGroup')->name('group.delete');
        Route::post('regenerate-invite', 'regenerateInvite');
    });

    Route::get('/accept-invite/{token}', [GroupController::class, 'acceptInvite']);

    // -------------------- User Block / Restrict --------------------
    Route::controller(UserBlockController::class)->group(function () {
        Route::get('online-users', 'onlineUsers');
        Route::get('available-users', 'index'); // ?search=John
        Route::post('users/{user}/block-toggle', 'toggleBlock');
        Route::post('users/{user}/restrict-toggle', 'toggleRestrict');
    });
});
