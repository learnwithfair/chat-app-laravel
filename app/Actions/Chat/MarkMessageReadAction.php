<?php
namespace App\Actions\Chat;

use App\Events\MessageEvent;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\MessageStatus;
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
            ->update(['last_read_message_id' => $lastMessage->id]);

        // Optional: update message_status for each unread message
        foreach ($conversation->messages as $msg) {
            $msg->statuses()->updateOrCreate(['user_id' => $user->id], ['status' => 'seen']);
        }

        // Broadcast
        broadcast(new MessageEvent(type: 'seen', conversationId: $conversationId,
            payload: [
                'user_id'    => $user->id,
                'message_id' => $lastMessage->id,
                'user'       => $user,
                'created_at' => now(),
            ]
        ))->toOthers();

        return $lastMessage;
    }

    public function markSeen(User $user, array $data)
    {
        $conversationId = (int) $data['conversation_id'];
        $messageIds     = array_unique($data['message_ids'] ?? []);
        $userId         = $user->id;

        if (empty($messageIds)) {
            return ['seen_count' => 0, 'message_ids' => []];
        }

        // Fetch valid messages in ONE query
        $messages = Message::where('conversation_id', $conversationId)
            ->whereIn('id', $messageIds)
            ->where('sender_id', '!=', $userId)
            ->select('id')->get();

        if ($messages->isEmpty()) {
            return ['seen_count' => 0, 'message_ids' => []];
        }

        $messageIds = $messages->pluck('id')->toArray();

        // Prepare bulk upsert data
        $now     = now();
        $payload = [];

        foreach ($messageIds as $id) {
            $payload[] = [
                'message_id' => $id,
                'user_id'    => $userId,
                'status'     => 'seen',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Bulk UPSERT (fast & scalable)
        MessageStatus::upsert($payload, ['message_id', 'user_id'], ['status', 'updated_at']);

        // Update participant last_read_message_id (max seen id)
        $lastReadId = max($messageIds);

        ConversationParticipant::where('conversation_id', $conversationId)
            ->where('user_id', $userId)
            ->update(['last_read_message_id' => $lastReadId]);

        // Broadcast ONE event instead of N
        broadcast(new MessageEvent('seen', $conversationId,
            [
                'user_id'    => $user->id,
                'message_id' => $lastReadId,
                'user'       => $user,
                'created_at' => now(),
            ]
        ))->toOthers();

        return [
            'seen_count'           => count($messageIds),
            'message_ids'          => $messageIds,
            'last_read_message_id' => $lastReadId,
        ];
    }

}
