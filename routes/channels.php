<?php

use App\Models\Conversation;
use Illuminate\Support\Facades\Broadcast;

// Online users
Broadcast::channel('online', function ($user) {
    return [
        'id'          => $user->id,
        'name'        => $user->name,
        'avatar_path' => $user->avatar_path,
    ];
});

// Single user channel
Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

// Conversation PRESENCE channel
Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {

    // if (! auth()->check()) {
    //     return false;
    // }

    $conversation = Conversation::where('id', $conversationId)
        ->whereHas('participants', function ($q) use ($user) {
            $q->where('user_id', $user->id)->active();
        })
        ->first();

    if (! $conversation) {
        return false;
    }
    return [
        'id'          => $user->id,
        'name'        => $user->name,
        'avatar_path' => $user->avatar_path,
    ];
});
