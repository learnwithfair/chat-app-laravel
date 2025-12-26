<?php
namespace App\Actions\Chat;

use App\Events\MessageEvent;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\User;

class MarkMessageReadAction
{
    public function execute(User $user, int $conversationId)
    {
        $conversation = Conversation::with('messages')->find($conversationId);
        if (! $conversation) {
            return null;
        }

        $lastMessage = $conversation->messages()->latest('id')->first();
        if (! $lastMessage) {
            return null;
        }

        // Update participant last read message
        ConversationParticipant::where('conversation_id', $conversationId)
            ->where('user_id', $user->id)
            ->update(['last_read_message_id' => $lastMessage->id,]);

        // Optional: update message_status for each unread message
        foreach ($conversation->messages as $msg) {
            $msg->statuses()->updateOrCreate(
                ['user_id' => $user->id],
                ['status' => 'seen']
            );
        }

        // Broadcast
        broadcast(new MessageEvent(
            type: 'seen',
            conversationId: $conversationId,
            payload: [
                'user_id'              => $user->id,
                'last_read_message_id' => $lastMessage->id,
            ]
        ))->toOthers();

        return $lastMessage;
    }
}
