<?php

use App\Models\Conversation;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
*/

// Single user channel: unread count, conversation updates, notifications
Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

// Conversation channel (messages, updated, deleted, typing, online users)
Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {

    $conversation = Conversation::where('id', $conversationId)
        ->whereHas('participants', function ($q) use ($user) {
            $q->where('user_id', $user->id)
                ->active();
        })
        ->first();

    if (! $conversation) {
        return false;
    }

    // Presence data for online indicator
    return [
        'id'          => $user->id,
        'name'        => $user->name,
        'avatar_path' => $user->avatar_path,
    ];
});


// conversations.forEach(conversation => {
//     Echo.join(`conversation.${conversation.id}`)
//         .here(users => {
//             conversation.onlineUsers = users;
//         })
//         .joining(user => {
//             conversation.onlineUsers.push(user);
//         })
//         .leaving(user => {
//             conversation.onlineUsers =
//                 conversation.onlineUsers.filter(u => u.id !== user.id);
//         });
// });
