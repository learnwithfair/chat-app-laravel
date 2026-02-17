<?php
namespace App\Repositories\Chat;

use App\Events\ConversationEvent;
use App\Events\MessageEvent;
use App\Http\Resources\Chat\ConversationResource;
use App\Http\Resources\Chat\MediaLibraryResource;
use App\Http\Resources\Chat\MessageResource;
use App\Jobs\SendPushNotificationJob;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\MessageStatus;
use App\Models\User;
use App\Services\Chat\ChatService;
use App\Traits\ApiResponse;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Str;

class MessageRepository
{
    use ApiResponse;
    public function getByConversation(User $user, int $conversationId, ?string $query = null, int $perPage = 20)
    {
        $participant = ConversationParticipant::where('conversation_id', $conversationId)
            ->where('user_id', $user->id)->active()->firstOrFail();

        $messages = Message::where('conversation_id', $conversationId)
            ->when($participant->last_deleted_message_id, function ($q) use ($participant) {
                $q->where('id', '>', $participant->last_deleted_message_id);
            })
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
                'forwardedFrom.sender:id,name',
                'forwardedFrom.conversation:id,name,type',
            ])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return MessageResource::collection($messages);
    }
    // pined messages
    public function getPinedMessagesByConversation(User $user, int $conversationId, ?string $query = null, int $perPage = 20)
    {
        $participant = ConversationParticipant::where('conversation_id', $conversationId)
            ->where('user_id', $user->id)->active()->firstOrFail();

        $messages = Message::where('conversation_id', $conversationId)
            ->pinned()
            ->when($participant->last_deleted_message_id, function ($q) use ($participant) {
                $q->where('id', '>', $participant->last_deleted_message_id);
            })
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
                'forwardedFrom.sender:id,name',
                'forwardedFrom.conversation:id,name,type',
            ])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return MessageResource::collection($messages);
    }

    public function find(int $messageId): ?Message
    {
        return Message::with(['sender', 'reactions', 'attachments'])->find($messageId);
    }

    // mediaLibrary

    public function mediaLibrary(User $user, $conversationId, int $perPage)
    {
        $participant = ConversationParticipant::where('conversation_id', $conversationId)
            ->where('user_id', $user->id)->active()->firstOrFail();

        $attachments = MessageAttachment::whereHas('message', function ($q) use ($conversationId, $participant, $user) {

            $q->where('conversation_id', $conversationId)

            //  deleted conversation logic
                ->when($participant->last_deleted_message_id, function ($q) use ($participant) {
                    $q->where('id', '>', $participant->last_deleted_message_id);
                })

            //  individual message delete (same as messages list)
                ->whereDoesntHave('deletions', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
        })
            ->latest()
            ->paginate($perPage);

        //  links also must respect delete logic
        $links = $this->getConversationLinks(
            $conversationId,
            $participant->last_deleted_message_id,
            $user->id
        );

        $data = [
            'media'      => $attachments->whereIn('type', ['image', 'video'])->values(),
            'audio'      => $attachments->where('type', 'audio')->values(),
            'files'      => $attachments->whereNotIn('type', ['image', 'video', 'audio'])->values(),
            'links'      => $links,
            'pagination' => [
                'current_page' => $attachments->currentPage(),
                'last_page'    => $attachments->lastPage(),
                'per_page'     => $attachments->perPage(),
                'total'        => $attachments->total(),
            ],
        ];

        return new MediaLibraryResource($data);
    }

    private function extractLinks(string $text): array
    {
        preg_match_all(
            '/https?:\/\/[^\s\)\]\}\>,"]+/i',
            $text,
            $matches
        );

        return array_values(array_unique($matches[0] ?? []));
    }

    private function getConversationLinks(
        int $conversationId,
        ?int $lastDeletedMessageId,
        int $userId
    ): array {
        $messages = Message::where('conversation_id', $conversationId)
            ->whereNotNull('message')
            ->whereIn('message_type', ['text', 'multiple'])

        //  conversation delete
            ->when($lastDeletedMessageId, function ($q) use ($lastDeletedMessageId) {
                $q->where('id', '>', $lastDeletedMessageId);
            })

        // per-user deleted messages
            ->whereDoesntHave('deletions', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })

            ->select('id', 'message', 'created_at')->latest()->get();

        $links = [];

        foreach ($messages as $message) {
            foreach ($this->extractLinks($message->message) as $url) {
                $links[] = [
                    'message_id' => $message->id,
                    'url'        => $url,
                    'created_at' => $message->created_at,
                ];
            }
        }

        return $links;
    }

    // store message
    public function storeMessage(User $user, array $data)
    {
        // 1. Auto-create private conversation
        if (empty($data['conversation_id']) && ! empty($data['receiver_id'])) {
            $data['conversation_id'] = app(ChatService::class)->startConversation($user, $data['receiver_id'])->id;
        }

        // 2. Validate membership
        $participant = ConversationParticipant::where('conversation_id', $data['conversation_id'])->where('user_id', $user->id)->active()->first();

        if (! $participant) {
            throw new HttpResponseException($this->error(null, 'You are no longer a member of this conversation.', 403));
        }

        // 3. Load conversation
        $conversation = Conversation::findOrFail($data['conversation_id']);

        // 4. Block check (receiver blocked sender)
        if ($conversation->type === 'private' && $conversation->otherParticipant($user)?->hasBlocked($user)) {
            throw new HttpResponseException($this->error(null, 'You cannot send message to this user.', 403));
        }

        // 5. Permission check
        if (! $conversation->canUserSendMessage($participant)) {
            throw new HttpResponseException($this->error(null, 'You are not allowed to send messages.', 403));
        }

        //  detect first message scenario
        $hadMessagesBefore = Message::where('conversation_id', $conversation->id)->lockForUpdate()->exists();

        // 6. Create message
        $message = Message::create([
            'conversation_id'       => $data['conversation_id'],
            'sender_id'             => $user->id,
            'receiver_id'           => $data['receiver_id'] ?? null,
            'message'               => $data['message'] ?? null,
            'message_type'          => $data['message_type'] ?? 'text',
            'reply_to_message_id'   => $data['reply_to_message_id'] ?? null,
            'forward_to_message_id' => $data['forward_to_message_id'] ?? null,
            'is_restricted'         => ! empty($data['receiver_id']) &&
            $user->restrictedByUsers()->where('users.id', $data['receiver_id'])->exists(),
        ]);

        // 7. Attachments (forwarded + new uploads)
        if (! empty($data['forward_to_message_id'])) {

            $original = Message::findOrFail($data['forward_to_message_id']);
            $this->cloneAttachments($original, $message);

        } elseif (! empty($data['attachments'])) {

            foreach ($data['attachments'] as $file) {
                /** @var \Illuminate\Http\UploadedFile $uploadedFile */
                $uploadedFile = $file['path'];

                $originalName = $uploadedFile->getClientOriginalName();
                $media_path   = uploadFile($file['path'], 'uploads/messages', (string) Str::uuid());
                $message->attachments()->create([
                    'path' => $media_path,
                    'type' => getFileType($media_path),
                    'name' => $originalName,
                    'size' => file_exists(public_path($media_path)) ? filesize(public_path($media_path)) : null,
                ]);
            }
        }

        // 8. Update sender last-read
        $participant->update(['last_read_message_id' => $message->id]);

        // 9. Reactivate deleted participants (bulk)
        $deletedParticipants = ConversationParticipant::where('conversation_id', $conversation->id)
            ->whereNotNull('deleted_at')->get(['id', 'user_id']);

        if ($deletedParticipants->isNotEmpty()) {
            ConversationParticipant::whereIn('id', $deletedParticipants->pluck('id'))
                ->update(['is_active' => true, 'deleted_at' => null]);
        }

        // 10. Bulk insert message statuses (HIGH SCALE SAFE)
        $participantIds = ConversationParticipant::where('conversation_id', $conversation->id)->active()->pluck('user_id');

        $now = now();

        $statuses = $participantIds->map(fn($uid) => [
            'message_id' => $message->id,
            'user_id'    => $uid,
            'status'     => $uid === $user->id ? 'seen' : 'sent',
            'created_at' => $now,
            'updated_at' => $now,
        ])->toArray();

        MessageStatus::insert($statuses);

        // 11. Touch conversation
        $conversation->touch();

        // 12. Push notification (async job recommended)
        $this->sendMessagePushNotification($conversation, $message, $user);

        // 13. Load message relations (NO N+1)
        $message->load([
            'sender:id,name',
            'reactions',
            'attachments',
            'statuses',
            'replyTo.sender:id,name',
            'forwardedFrom.sender:id,name',
            'forwardedFrom.conversation:id,name,type',
        ]);

        // 14. Broadcast rejoin event (Enterprise-grade realtime pipeline)
        if ($deletedParticipants->isNotEmpty()) {

            $conversation->fresh();
            // Eager load to prevent N+1 inside ConversationResource
            $conversation->load([
                'participants.user',
                'lastMessage.sender',
                'lastMessage.attachments',
                'creator:id,name',
                'groupSetting',
                'activeInvites',
            ]);

            foreach ($deletedParticipants as $participant) {

                $targetUser           = User::find($participant->user_id);
                $conversationResource = (new ConversationResource($conversation))->forUser($targetUser)->toArray(request());

                event(new ConversationEvent(
                    $conversation,
                    'added',
                    $participant->user_id,
                    $conversationResource
                ));
            }

        }

        // 15. FIRST PRIVATE MESSAGE REALTIME FIX
        if ($conversation->type === 'private' && ! $hadMessagesBefore) {
            $receiver = $conversation->otherParticipant($user);

            if ($receiver) {
                // Load full conversation data once
                $conversation->load([
                    'participants.user',
                    'lastMessage.sender',
                    'lastMessage.attachments',
                    'creator:id,name',
                    'groupSetting',
                    'activeInvites',
                ]);

                $conversationResource = (new ConversationResource($conversation))->forUser($receiver)->toArray(request());

                // Add conversation to receiver realtime chatlist
                event(new ConversationEvent(
                    $conversation,
                    'added',
                    $receiver->id,
                    $conversationResource
                ));
            }
        }

        return new MessageResource($message);
    }

    private function cloneAttachments(Message $from, Message $to, bool $duplicateFile = false): void
    {
        if (! $from->relationLoaded('attachments')) {
            $from->load('attachments');
        }

        if ($from->attachments->isEmpty()) {
            return;
        }

        $rows = [];
        foreach ($from->attachments as $file) {
            $rows[] = [
                'message_id' => $to->id,
                'path'       => $file->path,
                'type'       => $file->type,
                'name'       => $file->name,
                'size'       => $file->size,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        MessageAttachment::insert($rows);
    }

    public function updateMessage(User $user, array $data, Message $message)
    {
        if ($message->sender_id !== $user->id) {
            throw new HttpResponseException($this->error(null, 'You are not allowed to update this message.', 403));
        }
        $data['edited_at'] = now();
        $message->update($data);

        $message->load([
            'sender:id,name',
            'reactions',
            'attachments',
            'statuses',
            'replyTo.sender:id,name',
            'forwardedFrom.sender:id,name',
            'forwardedFrom.conversation:id,name,type',
        ]);
        return new MessageResource($message);
    }

    public function deleteMessagesForUser(int $userId, array $messageIds)
    {
        $messages = Message::whereIn('id', $messageIds)->get();

        foreach ($messages as $message) {
            // Soft delete for user
            $message->deletions()->firstOrCreate(['user_id' => $userId]);

            // Broadcast delete-for-me only to requester
            // broadcast(new MessageEvent('deleted_for_me', $message->conversation_id, [
            //     'message_id' => $message->id,
            //     'user_id'    => $userId,
            // ]));
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

                broadcast(new MessageEvent('deleted_permanent', $conversationId, ['message_id' => $deletedId]));

                continue;
            }

            // First-time delete → convert to "Unsent"
            $message->update([
                'is_deleted_for_everyone' => true,
                'message'                 => "Unsent",
                'is_pinned'               => false,
            ]);
            // attachments deletion
            deleteFiles($message->attachments->pluck('path')->toArray());
            $message->attachments()->delete();

            broadcast(new MessageEvent('deleted_for_everyone', $message->conversation_id, $message->toArray()));
            broadcast(new MessageEvent('unpinned', $message->conversation_id, $message->toArray()));
        }

        return "Messages deleted for everyone.";
    }

    // Send push notification to conversation participants (except sender)

    private function sendMessagePushNotification(Conversation $conversation, Message $message, User $sender): void
    {
        $participants = ConversationParticipant::where('conversation_id', $conversation->id)->active()->unmuted()->with('user.deviceTokens')->get();

        foreach ($participants as $participant) {
            if ($participant->user_id === $sender->id || ($participant->is_muted)) {
                continue;
            } // Skip sender & muted users

            $tokens = $participant->user?->deviceTokens->pluck('token')->filter()->toArray();
            if (empty($tokens)) {
                continue;
            }

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
