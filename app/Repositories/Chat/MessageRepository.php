<?php
namespace App\Repositories\Chat;

use App\Events\MessageEvent;
use App\Http\Resources\Chat\MessageResource;
use App\Jobs\SendPushNotificationJob;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\MessageStatus;
use App\Models\User;
use App\Services\Chat\ChatService;
use Illuminate\Support\Str;

class MessageRepository
{

    public function getByConversation(User $user, int $conversationId, ?string $query = null)
    {
        $messages = Message::where('conversation_id', $conversationId)
            ->whereDoesntHave('deletions', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->when($query, function ($q) use ($query) {
                $q->where('message', 'like', "%{$query}%");
            })
            ->with([
                'sender:id,name',
                'reactions',
                'attachments',
                'statuses',
                'replyTo.sender:id,name',
            ])
            ->orderBy('created_at', 'asc')
            ->get();

        return MessageResource::collection($messages);
    }

    public function find(int $messageId): ?Message
    {
        return Message::with([
            'sender',
            'reactions',
            'attachments',
        ])->find($messageId);
    }

    public function storeMessage(User $user, array $data)
    {
        // 1. Auto-create private conversation
        if (empty($data['conversation_id']) && ! empty($data['receiver_id'])) {
            $data['conversation_id'] = app(ChatService::class)->startConversation($user, $data['receiver_id'])->id;
        }

        // 2. Validate membership
        $participant = ConversationParticipant::where('conversation_id', $data['conversation_id'])->where('user_id', $user->id)->active()->first();

        if (! $participant) {
            abort(403, 'You are no longer a member of this conversation.');
        }
        $conversation = Conversation::findOrFail($data['conversation_id']);

        // 3. Block check (receiver blocked sender)
        if ($conversation->type === 'private' &&
            $conversation->otherParticipant($user)?->hasBlocked($user)
        ) {
            abort(403, 'You cannot send message to this user.');
        }

        // 4. Create message
        $message = Message::create([
            'conversation_id'     => $data['conversation_id'],
            'sender_id'           => $user->id,
            'receiver_id'         => $data['receiver_id'] ?? null,
            'message'             => $data['message'] ?? null,
            'message_type'        => $data['message_type'] ?? 'text',
            'reply_to_message_id' => $data['reply_to_message_id'] ?? null,
            'is_restricted'       => ! empty($data['receiver_id']) &&
            $user->restrictedByUsers()->where('users.id', $data['receiver_id'])->exists(),
        ]);

        // 5. Attachments
        if (! empty($data['attachments'])) {
            foreach ($data['attachments'] as $file) {

                $media_path = uploadFile($file['path'], 'uploads/messages', (string) Str::uuid());
                $message->attachments()->create([
                    'path' => $media_path,
                    'type' => getFileType($media_path),
                    'size' => file_exists(public_path($media_path)) ? filesize(public_path($media_path)) : null,
                ]);
            }
        }

        // 6. Update last read
        $participant->update(['last_read_message_id' => $message->id]);

        // 7. Create message statuses (bulk)
        $participants = ConversationParticipant::where('conversation_id', $data['conversation_id'])->active()->get();

        $statuses = $participants->map(fn($p) => [
            'message_id' => $message->id,
            'user_id'    => $p->user_id,
            'status'     => $p->user_id === $user->id ? 'seen' : 'sent',
            'created_at' => now(),
            'updated_at' => now(),
        ])->toArray();

        MessageStatus::insert($statuses);

        // 8. Touch conversation for last activity
        $conversation->touch();

        // 9. Push notification
        $this->sendMessagePushNotification($conversation, $message, $user);

        return $message->load('replyTo');
    }

    public function updateMessage(User $user, array $data, Message $message)
    {
        if ($message->sender_id !== $user->id) {
            abort(403, 'You are not allowed to update this message.');
        }
        $message->update($data);
        return $message->refresh();
    }

    public function deleteMessagesForUser(int $userId, array $messageIds)
    {
        $messages = Message::whereIn('id', $messageIds)->get();

        foreach ($messages as $message) {
            // Soft delete for user
            $message->deletions()->firstOrCreate([
                'user_id' => $userId,
            ]);

            // Broadcast delete-for-me only to requester
            broadcast(new MessageEvent('deleted_for_me', $message->conversation_id, [
                'message_id' => $message->id,
                'user_id'    => $userId,
            ]));
        }

        return "Messages deleted for you.";
    }
    public function deleteMessagesForEveryone(int $userId, array $messageIds)
    {
        $messages = Message::whereIn('id', $messageIds)->get();

        foreach ($messages as $message) {
            if ($message->sender_id !== $userId) {
                return response()->json([
                    'error' => 'You can only delete your own messages for everyone.',
                ], 403);
            }

            // If already unsent earlier → delete row
            if ($message->is_deleted_for_everyone && $message->message === "Unsent") {
                $conversationId = $message->conversation_id;
                $deletedId      = $message->id;
                $message->delete();

                broadcast(new MessageEvent('deleted_permanent', $conversationId, [
                    'message_id' => $deletedId,
                ]));

                continue;
            }

            // First-time delete → convert to "Unsent"
            $message->update([
                'is_deleted_for_everyone' => true,
                'message'                 => "Unsent",
            ]);
            // attachments deletion
            deleteFiles($message->attachments->pluck('path')->toArray());
            $message->attachments()->delete();

            broadcast(new MessageEvent('deleted_for_everyone', $message->conversation_id, [
                'message_id' => $message->id,
                'unsent'     => true,
            ]));
        }

        return "Messages deleted for everyone.";
    }

    // Send push notification to conversation participants (except sender)

    private function sendMessagePushNotification(Conversation $conversation, Message $message, User $sender): void
    {
        $participants = ConversationParticipant::where('conversation_id', $conversation->id)->active()->with('user.tokens')->get();

        foreach ($participants as $participant) {
            if ($participant->user_id === $sender->id || ($participant->is_muted)) {continue;} // Skip sender & muted users

            $tokens = $participant->user?->tokens->pluck('token')->filter()->toArray();
            if (empty($tokens)) {continue;}

            $title = $sender->name ?? 'New Message';
            $body  = $message->message_type === 'text' ? ($message->message ?: 'New message received'): 'Sent you an attachment';

            $payload = [
                'type'            => 'chat_message',
                'conversation_id' => (string) $conversation->id,
                'message_id'      => (string) $message->id,
                'sender_id'       => (string) $sender->id,
            ];

            // app(PushNotificationService::class)->sendToTokens($tokens, $title, $body, $payload);
            SendPushNotificationJob::dispatch($tokens, $participant->user_id, $title, $body, $payload, null, false)->afterCommit();

        }
    }
}
