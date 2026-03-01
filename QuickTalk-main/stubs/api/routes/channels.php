<?php

use App\Models\Conversation;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| ConversationEvent
|--------------------------------------------------------------------------
| Channel: conversation-channel.{participantId}
| Purpose: Notify a specific participant (user-based)
*/

Broadcast::channel('conversation-channel.{participantId}', function ($user, $participantId) {
    return (int) $user->id === (int) $participantId;
});


/*
|--------------------------------------------------------------------------
| MessageSentEvent
|--------------------------------------------------------------------------
| Channel: chat-channel.{conversationId}
| Purpose: Broadcast messages inside a conversation
*/
Broadcast::channel('chat-channel.{conversationId}', function ($user, $conversationId) {
    return Conversation::where('id', $conversationId)
        ->whereHas('participants', function ($q) use ($user) {
            $q->where('participant_id', $user->id);
        })
        ->exists();
});


/*
|--------------------------------------------------------------------------
| ActiveUsersEvent
|--------------------------------------------------------------------------
| Channel: online-status-channel.{participantId}
| Purpose: Notify a specific participant (user-based)
*/

Broadcast::channel('online-status-channel', function ($user) {
    return [
        'id' => $user->id,
        'name' => $user->name,
        'avatar' => $user->avatar ?? null,
    ];
});


/*|--------------------------------------------------------------------------
| TypingIndicatorEvent
|--------------------------------------------------------------------------
| Channel: typing-indicator-channel.{conversationId}
| Purpose: Broadcast typing indicators inside a conversation
*/
Broadcast::channel('typing-indicator-channel.{conversationId}', function ($user, $conversationId) {
    return Conversation::where('id', $conversationId)
        ->whereHas('participants', function ($q) use ($user) {
            $q->where('participant_id', $user->id);
        })
        ->exists();
});
