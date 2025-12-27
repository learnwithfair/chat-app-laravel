<?php

use App\Http\Controllers\Api\V1\Chat\ConversationController;
use App\Http\Controllers\Api\V1\Chat\GroupController;
use App\Http\Controllers\Api\V1\Chat\MessageController;
use App\Http\Controllers\Api\V1\Chat\ReactionController;
use App\Http\Controllers\Api\V1\Chat\UserBlockController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['auth:sanctum', 'last_seen'])->group(function () {

    // -------------------- Conversations --------------------
    Route::apiResource('conversations', ConversationController::class)->only(['index', 'store', 'destroy']);
    Route::post('conversations/private', [ConversationController::class, 'startPrivateConversation']);

// ----------------------- Messages ------------------------------
    Route::apiResource('messages', MessageController::class)->only(['store', 'show', 'update']);

    Route::prefix('messages')->controller(MessageController::class)->group(function () {
        Route::delete('delete-for-me', 'deleteForMe')->name('messages.deleteForMe');
        Route::delete('delete-for-everyone', 'deleteForEveryone')->name('messages.deleteForEveryone');
        Route::get('seen/{conversation}', 'markAsSeen');
        Route::get('delivered/{conversation}', 'markAsDelivered');
    });

    // -------------------- Reactions --------------------
    Route::controller(ReactionController::class)->group(function () {
        Route::post('messages/{message}/reaction', 'toggleReaction');
        Route::get('messages/{message}/reaction', 'index');
    });

    // -------------------- Group Management --------------------
    // Custom group routes
    Route::prefix('group/{conversation}')->controller(GroupController::class)->group(function () {
        Route::post('update', 'update')->name('group.update');
        Route::post('members/add', 'addMembers')->name('group.members.add');
        Route::post('members/remove', 'removeMember')->name('group.members.remove');
        Route::get('members', 'getMembers')->name('group.members.show');
        Route::post('admins/add', 'addAdmins')->name('group.admins.add');
        Route::post('admins/remove', 'removeAdmins')->name('group.admins.remove');
        Route::post('mute', 'muteToggleGroup')->name('group.mute'); // 0 = unmute, 1 = Unlimited mute, otherwise specify miniutes
        Route::post('leave', 'leaveGroup')->name('group.leave');
    });

    // -------------------- User Block / Restrict --------------------
    Route::controller(UserBlockController::class)->group(function () {
        Route::post('users/{user}/block-toggle', 'toggleBlock');
        Route::post('users/{user}/restrict-toggle', 'toggleRestrict');
    });
});
