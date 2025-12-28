<?php

// use App\Http\Controllers\Web\V1\Chat\ChatController;
// use App\Http\Controllers\Web\V1\Chat\ConversationController;
// use App\Http\Controllers\Web\V1\Chat\GroupController;
// use App\Http\Controllers\Web\V1\Chat\MessageController;
// use App\Http\Controllers\Web\V1\Chat\ReactionController;
// use App\Http\Controllers\Web\V1\Chat\UserBlockController;
// use Illuminate\Support\Facades\Route;

// Route::middleware(['auth', 'verified'])->prefix('chat')->name('chat.')->group(function () {

// // -------------------- Conversations --------------------
//     Route::get('/', [ChatController::class, 'index'])->name('index');                             // List conversations
//     Route::post('/start', [ConversationController::class, 'start'])->name('conversations.start'); // Start private conversation
//     Route::delete('/{conversation}', [ConversationController::class, 'delete'])->name('conversations.delete');

//     // -------------------- Messages --------------------
//     Route::post('messages', [MessageController::class, 'store'])->name('messages.store');
//     Route::get('messages', [MessageController::class, 'store'])->name('messages.show');
//     Route::put('messages/{message}', [MessageController::class, 'update'])->name('messages.update');
//     Route::delete('messages/{message}', [MessageController::class, 'deleteForMe'])->name('messages.delete');

//     Route::get('messages/seen/{conversation}', [MessageController::class, 'markAsSeen'])->name('messages.seen');
//     Route::get('messages/delivered/{conversation}', [MessageController::class, 'markAsDelivered'])->name('messages.delivered');

//     // -------------------- Reactions --------------------
//     Route::post('messages/{message}/reaction', [ReactionController::class, 'toggleReaction'])->name('reactions.toggle');
//     Route::get('messages/{message}/reaction', [ReactionController::class, 'index'])->name('reactions.index');

//     // -------------------- Group Management --------------------
//     Route::prefix('group/{conversation}')->group(function () {
//         Route::post('update', [GroupController::class, 'update'])->name('group.update');
//         Route::post('members/add', [GroupController::class, 'addMembers'])->name('group.members.add');
//         Route::post('members/remove', [GroupController::class, 'removeMember'])->name('group.members.remove');
//         Route::get('members', [GroupController::class, 'getMembers'])->name('group.members.show');
//         Route::post('admins/add', [GroupController::class, 'addAdmins'])->name('group.admins.add');
//         Route::post('admins/remove', [GroupController::class, 'removeAdmins'])->name('group.admins.remove');
//         Route::post('mute', [GroupController::class, 'muteToggleGroup'])->name('group.mute');
//         Route::post('leave', [GroupController::class, 'leaveGroup'])->name('group.leave');
//     });

//     // -------------------- Block / Restrict --------------------
//     Route::post('users/{user}/block-toggle', [UserBlockController::class, 'toggleBlock'])->name('users.toggleBlock');
//     Route::post('users/{user}/restrict-toggle', [UserBlockController::class, 'toggleRestrict'])->name('users.toggleRestrict');
// });
<?php

use App\Http\Controllers\Web\V1\Chat\ChatController;
use App\Http\Controllers\Web\V1\Chat\ConversationController;
use App\Http\Controllers\Web\V1\Chat\MessageController;
use App\Http\Controllers\Web\V1\Chat\GroupController;
use App\Http\Controllers\Web\V1\Chat\ReactionController;
use App\Http\Controllers\Web\V1\Chat\UserBlockController;
use Illuminate\Support\Facades\Route;

Route::prefix('chat')->middleware(['auth', 'last_seen'])->name('chat.')->group(function () {

    // -------------------- Main Chat Interface --------------------
    // Returns the main chat layout (Inertia page)
    Route::get('/', [ChatController::class, 'index'])->name('index');
    
    // Load specific conversation view
    Route::get('/conversations/{conversation}', [ChatController::class, 'show'])->name('show');

    // -------------------- Conversations --------------------
    Route::prefix('conversations')->name('conversations.')->group(function () {
        // Get all conversations (for sidebar) - AJAX/Inertia
        Route::get('/', [ConversationController::class, 'index'])->name('index');
        
        // Start private conversation
        Route::post('/private', [ConversationController::class, 'startPrivateConversation'])->name('private');
        
        // Create group conversation
        Route::post('/', [ConversationController::class, 'store'])->name('store');
        
        // Delete conversation
        Route::delete('/{conversation}', [ConversationController::class, 'destroy'])->name('destroy');
        
        // Search conversations (sidebar search)
        Route::get('/search', [ConversationController::class, 'search'])->name('search');
        
        // Get conversation details (for header, settings panel)
        Route::get('/{conversation}/details', [ConversationController::class, 'details'])->name('details');
    });

    // -------------------- Messages --------------------
    Route::prefix('messages')->name('messages.')->group(function () {
        // Get messages for a conversation (with pagination)
        Route::get('/conversation/{conversation}', [MessageController::class, 'index'])->name('index');
        
        // Send new message
        Route::post('/', [MessageController::class, 'store'])->name('store');
        
        // Update/edit message
        Route::put('/{message}', [MessageController::class, 'update'])->name('update');
        
        // Get single message details
        Route::get('/{message}', [MessageController::class, 'show'])->name('show');
        
        // Delete for me
        Route::delete('/delete-for-me', [MessageController::class, 'deleteForMe'])->name('deleteForMe');
        
        // Delete for everyone
        Route::delete('/delete-for-everyone', [MessageController::class, 'deleteForEveryone'])->name('deleteForEveryone');
        
        // Mark messages as seen
        Route::post('/seen/{conversation}', [MessageController::class, 'markAsSeen'])->name('seen');
        
        // Mark messages as delivered
        Route::post('/delivered/{conversation}', [MessageController::class, 'markAsDelivered'])->name('delivered');
        
        // Search messages within conversation
        Route::get('/conversation/{conversation}/search', [MessageController::class, 'search'])->name('search');
    });

    // -------------------- Reactions --------------------
    Route::prefix('reactions')->name('reactions.')->group(function () {
        // Toggle reaction on message
        Route::post('/messages/{message}', [ReactionController::class, 'toggleReaction'])->name('toggle');
        
        // Get all reactions for a message (for modal)
        Route::get('/messages/{message}', [ReactionController::class, 'index'])->name('index');
    });

    // -------------------- Group Management --------------------
    Route::prefix('groups/{conversation}')->name('groups.')->group(function () {
        // Update group info (name, avatar, description)
        Route::put('/', [GroupController::class, 'update'])->name('update');
        
        // Get group members list
        Route::get('/members', [GroupController::class, 'getMembers'])->name('members.index');
        
        // Add members to group
        Route::post('/members', [GroupController::class, 'addMembers'])->name('members.add');
        
        // Remove member from group
        Route::delete('/members/{user}', [GroupController::class, 'removeMember'])->name('members.remove');
        
        // Add admins
        Route::post('/admins', [GroupController::class, 'addAdmins'])->name('admins.add');
        
        // Remove admins
        Route::delete('/admins/{user}', [GroupController::class, 'removeAdmins'])->name('admins.remove');
        
        // Mute/unmute group
        Route::post('/mute', [GroupController::class, 'muteToggleGroup'])->name('mute');
        
        // Leave group
        Route::post('/leave', [GroupController::class, 'leaveGroup'])->name('leave');
    });

    // -------------------- User Block / Restrict --------------------
    Route::prefix('users/{user}')->name('users.')->group(function () {
        // Toggle block status
        Route::post('/block', [UserBlockController::class, 'toggleBlock'])->name('block.toggle');
        
        // Toggle restrict status
        Route::post('/restrict', [UserBlockController::class, 'toggleRestrict'])->name('restrict.toggle');
        
        // Get user's block/restrict status
        Route::get('/status', [UserBlockController::class, 'getStatus'])->name('status');
    });

    // -------------------- File Upload --------------------
    // Optional: Separate route for file uploads if needed
    Route::post('/upload', [MessageController::class, 'uploadFile'])->name('upload');

    // -------------------- Typing Indicator (Optional) --------------------
    // If you want to handle typing via HTTP (though WebSockets is better)
    Route::post('/typing/{conversation}', [ChatController::class, 'typing'])->name('typing');
});