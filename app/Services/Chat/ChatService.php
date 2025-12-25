<?php
namespace App\Services\Chat;

use App\Actions\Chat\CreateConversationAction;
use App\Actions\Chat\MarkMessageReadAction;
use App\Actions\Chat\SendMessageAction;
use App\Events\MessageEvent;
use App\Events\UserStatusEvent;
use App\Models\Message;
use App\Models\MessageReaction;
use App\Models\MessageStatus;
use App\Models\User;
use App\Repositories\Chat\ConversationRepository;
use App\Repositories\Chat\MessageRepository;

class ChatService
{
    public function __construct(
        protected ConversationRepository $conversationRepo,
        protected MessageRepository $messageRepo,
        protected CreateConversationAction $createConversation,
        protected SendMessageAction $sendMessage,
        protected MarkMessageReadAction $markRead
    ) {}

    // -------------------------------
    // Conversations
    // -------------------------------
    public function listConversations(User $user, int $perPage, ?string $query = null)
    {
        return $this->conversationRepo->listFor($user, $perPage, $query);
    }

    public function startConversation(User $user, int $receiverId)
    {
        return $this->createConversation->execute($user, $receiverId);
    }

    public function createGroup(User $user, array $data)
    {
        return $this->conversationRepo->createGroupConversation($data, $user->id);
    }

    public function deleteConversationForUser(int $userId, int $conversationId): bool
    {
        return $this->conversationRepo->deleteForUser($userId, $conversationId);
    }

    // -------------------------------
    // Messages
    // -------------------------------
    public function getMessages(User $user, int $conversationId, ?string $query = null)
    {
        return $this->messageRepo->getByConversation($user, $conversationId, $query);
    }

    public function sendMessage(User $user, array $data)
    {
        $message = $this->sendMessage->execute($user, $data);

        event(new MessageEvent('sent', $message->conversation_id, ['message' => $message]));

        return $message;
    }
    public function updateMessage(User $user, array $data, Message $message)
    {
        $updatemessage = $this->sendMessage->update($user, $data, $message);

        event(new MessageEvent('updated', $message->conversation_id, ['message' => $message]));

        return $updatemessage;
    }

    public function typing(User $user, int $conversationId, bool $isTyping)
    {
        broadcast(new UserStatusEvent('typing', [
            'user_id'         => $user->id,
            'conversation_id' => $conversationId,
        ]))->toOthers();

        return $isTyping;
    }
    public function deleteForMe(User $user, array $data)
    {
        // Normalize IDs
        $ids = $data['message_ids'] ?? ($data['message_id'] ? [$data['message_id']] : []);
        if (empty($ids)) {
            return response()->json(['error' => 'No messages provided'], 422);
        }

        return $this->messageRepo->deleteMessagesForUser($user->id, $ids);
    }
    public function deleteForEveryone(User $user, array $data)
    {
        // Normalize IDs
        $ids = $data['message_ids'] ?? ($data['message_id'] ? [$data['message_id']] : []);
        if (empty($ids)) {
            return response()->json(['error' => 'No messages provided'], 422);
        }

        return $this->messageRepo->deleteMessagesForEveryone($user->id, $ids);
    }

    public function markConversationAsRead(User $user, int $conversationId)
    {
        return $this->markRead->execute($user, $conversationId);
    }

    public function markDelivered(User $user, int $conversationId)
    {
        MessageStatus::where('user_id', $user->id)
            ->whereHas('message', fn($q) =>
                $q->where('conversation_id', $conversationId)
            )
            ->where('status', 'sent')
            ->update(['status' => 'delivered']);

        broadcast(new MessageEvent(
            'delivered',
            $conversationId,
            ['user_id' => $user->id]
        ));
    }

    // -------------------------------
    // Reactions
    // -------------------------------

    public function toggleReaction(User $user, int $messageId, string $reaction)
    {
        $message = $this->messageRepo->find($messageId);

        if (! $message) {
            throw new \Exception("Message not found");
        }

        $existing = $message->reactions()->where('user_id', $user->id)->first();

        if ($existing && $existing->reaction === $reaction) {
            $message->reactions()->where('user_id', $user->id)->delete();
        } else {
            $message->reactions()->updateOrCreate(
                [
                    'user_id'    => $user->id,
                    'message_id' => $messageId,
                ],
                [
                    'reaction' => $reaction,
                ]
            );
        }

        $reactions = $message->reactions()->with('user')->get();

        // Broadcast reaction update
        broadcast(new MessageEvent('reaction', $message->conversation_id, [
            'message_id' => $message->id,
            'reactions'  => $reactions,
        ]));

        return $reactions;
    }

    // list reactions
    public function listReaction(int $messageId)
    {
        // Fetch reactions with user info (eager loading)
        $reactions = MessageReaction::where('message_id', $messageId)
            ->with(['user:id,name,avatar_path'])
            ->get();

        // Group reactions by type with count
        $grouped = $reactions->groupBy('reaction')->map(function ($items, $reaction) {
            return [
                'count' => $items->count(),
                'users' => $items->map(function ($reactionItem) {
                    return [
                        'user_id'    => $reactionItem->user_id,
                        'name'       => $reactionItem->user->name ?? null,
                        'avatar'     => $reactionItem->user->avatar_path ?? null,
                        'created_at' => $reactionItem->created_at->toDateTimeString(),
                    ];
                })->values(), // reset keys
            ];
        });

        return [
            'total_reactions' => $reactions->count(),
            'grouped'         => $grouped,
        ];
    }

    // -------------------------------
    // User Blocking
    // -------------------------------

    public function toggleBlock(User $user, int $userId)
    {
        return $this->conversationRepo->toggleBlock($user, $userId);
    }
    public function toggleRestrict(User $user, int $userId)
    {
        return $this->conversationRepo->toggleRestrict($user, $userId);
    }

    // -------------------------------
    // Group Management
    // -------------------------------
    public function getMembers(User $user, int $groupId)
    {
        return $this->conversationRepo->getMembers($user->id, $groupId);
    }
    public function addMembers(User $user, int $groupId, array $memberIds)
    {
        return $this->conversationRepo->addMembers($user, $groupId, $memberIds);
    }

    public function removeMember(User $user, int $groupId, array $memberIds)
    {
        return $this->conversationRepo->removeMember($user->id, $groupId, $memberIds);
    }
    public function addGroupAdmins(User $actor, int $conversationId, array $userIds)
    {
        return $this->conversationRepo->addGroupAdmins($actor, $conversationId, $userIds);
    }

    public function removeGroupAdmins(User $actor, int $conversationId, array $userIds)
    {
        return $this->conversationRepo->removeGroupAdmins($actor, $conversationId, $userIds);
    }

    public function muteGroup(User $user, int $groupId, int $minutes = 0)
    {
        return $this->conversationRepo->muteGroup($user->id, $groupId, $minutes);
    }

    public function leaveGroup(User $user, int $groupId)
    {
        return $this->conversationRepo->leaveGroup($user, $groupId);
    }

    public function updateGroupInfo(User $user, int $groupId, array $data)
    {
        return $this->conversationRepo->updateGroupInfo($user->id, $groupId, $data);
    }

    public function deleteGroup(User $user, int $groupId)
    {
        return $this->conversationRepo->deleteGroup($groupId);
    }

    public function createDefault(int $conversationId)
    {
        return $this->conversationRepo->createDefault($conversationId);
    }

    public function canSendMessage(int $conversationId, int $userId): bool
    {
        return $this->conversationRepo->canUserSendMessage($conversationId, $userId);
    }

    public function canManageMembers(int $conversationId, int $userId): bool
    {
        return $this->conversationRepo->canUserManageMembers($conversationId, $userId);
    }

}
