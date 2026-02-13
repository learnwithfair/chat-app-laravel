<?php

use App\Models\Conversation;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

// Single user channel
Broadcast::channel('user.{userId}', function ($user, $userId) {
    Log::info('Auth user.channel', ['user_id' => $user->id, 'requested' => $userId]);
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('online', function ($user) {
    return [
        'id'          => $user->id,
        'name'        => $user->name,
        'avatar_path' => $user->avatar_path,
    ];
});

// Conversation PRESENCE channel
Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    Log::info('Auth conversation.channel', [
        'user_id'         => $user->id,
        'conversation_id' => $conversationId,
    ]);

    if (! auth()->check()) {
        Log::error('Channel auth failed: not authenticated');
        return false;
    }

    try {
        $conversation = Conversation::where('id', $conversationId)
            ->whereHas('participants', function ($q) use ($user) {
                $q->where('user_id', $user->id)
                // Comment out ->active() temporarily to test
                ->active();
            })
            ->first();

        if (! $conversation) {
            Log::warning('Conversation not found or user not participant', [
                'conversation_id' => $conversationId,
                'user_id'         => $user->id,
            ]);
            return false;
        }

        Log::info('Channel auth SUCCESS', [
            'conversation_id' => $conversationId,
            'user_id'         => $user->id,
        ]);

        return [
            'id'          => $user->id,
            'name'        => $user->name,
            'avatar_path' => $user->avatar_path,
        ];
    } catch (\Exception $e) {
        Log::error('Channel auth exception', [
            'error'           => $e->getMessage(),
            'conversation_id' => $conversationId,
            'user_id'         => $user->id,
        ]);
        return false;
    }
});
