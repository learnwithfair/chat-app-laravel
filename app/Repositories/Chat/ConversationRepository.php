<?php
namespace App\Repositories\Chat;

use App\Events\ConversationEvent;
use App\Events\MessageEvent;
use App\Http\Resources\Chat\ConversationResource;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\GroupSettings;
use App\Models\User;
use App\Services\Chat\ChatService;

class ConversationRepository
{
    public function find(int $conversationId): ?Conversation
    {
        return Conversation::with([
            'participants.user',
            'messages.sender',
        ])->find($conversationId);
    }

    public function findUser(int $userId)
    {
        return User::findOrFail($userId);
    }

    public function listFor(User $user, int $perPage = 20, ?string $query = null)
    {
        $conversations = Conversation::query()
            ->whereHas('participants', function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->where('is_active', true);
            })
            ->when($query, function ($q) use ($query, $user) {
                $q->where(function ($q2) use ($query, $user) {

                    //  Group conversation → search by group name
                    $q2->where(function ($group) use ($query) {
                        $group->where('type', 'group')
                            ->where('name', 'like', "%{$query}%");
                    })

                    //  Private conversation → search by other user's name
                        ->orWhere(function ($private) use ($query, $user) {
                            $private->where('type', 'private')
                                ->whereHas('participants.user', function ($u) use ($query, $user) {
                                    $u->where('users.id', '!=', $user->id)
                                        ->where('users.name', 'like', "%{$query}%");
                                });
                        });
                });
            })
            ->with([
                'participants' => function ($q) {
                    $q->where('is_active', true)->with('user');
                },
                'lastMessage.sender',
                'groupSetting',
            ])
            ->withCount([
                'unreadMessages as unread_count' => function ($q) use ($user) {
                    $q->where('sender_id', '!=', $user->id)
                        ->whereColumn(
                            'messages.id',
                            '>',
                            'conversation_participants.last_read_message_id'
                        )
                        ->join('conversation_participants', function ($join) use ($user) {
                            $join->on('conversation_participants.conversation_id', '=', 'messages.conversation_id')
                                ->where('conversation_participants.user_id', $user->id);
                        });
                },
            ])
            ->latest('updated_at')
            ->paginate($perPage);

        return ConversationResource::collection($conversations)->withQueryString();
    }

    public function findPrivateBetween(int $userId1, int $userId2): ?Conversation
    {
        return Conversation::where('type', 'private')
            ->whereHas('participants', fn($q) => $q->where('user_id', $userId1))
            ->whereHas('participants', fn($q) => $q->where('user_id', $userId2))
            ->first();
    }

    public function createPrivateConversation(int $userId1, int $userId2): Conversation
    {
        $conversation = Conversation::create([
            'type' => 'private',
        ]);

        $conversation->participants()->createMany([
            ['user_id' => $userId1, 'role' => 'member'],
            ['user_id' => $userId2, 'role' => 'member'],
        ]);

        return $conversation;
    }

    public function createGroupConversation(array $data, int $creadtedId): Conversation
    {
        $conversation = Conversation::create([
            'type'       => 'group',
            'name'       => $data['name'] ?? 'New Group',
            'created_by' => $creadtedId ?? null,
        ]);

        $participants = [];
        foreach ($data['participants'] ?? [] as $userId) {
            $participants[] = [
                'user_id' => $userId,
                'role'    => $userId === $creadtedId ? 'super_admin' : 'member',
            ];
        }

        $conversation->participants()->createMany($participants);

        app(ChatService::class)->createDefault($conversation->id);

        return $conversation;
    }

    public function deleteForUser(int $userId, int $conversationId): bool
    {
        $participant = ConversationParticipant::where('conversation_id', $conversationId)
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->first();

        if (! $participant) {
            abort(403, 'Conversation not found or already removed.');
        }

        $participant->update([
            'is_active' => false,
            'left_at'   => now(),
        ]);

        return true;
    }

    // -------------------------
    // Group member management
    // -------------------------

    // getMembers
    public function getMembers(int $userId, int $conversationId)
    {
        if (! $this->canUserPermit($conversationId, $userId)) {
            abort(403, 'You are not allowed to view this conversation.');
        }
        $conversation = $this->find($conversationId);
        return $conversation->participants()->active()->get();
    }

    public function addMembers(User $adder, int $conversationId, array $memberIds)
    {

        if (! $this->canUserManageMembers($conversationId, $adder->id)) {
            abort(403, 'Only admins can add members.');
        }

        $conversation = $this->find($conversationId);
        $lastMessage  = null;

        foreach ($memberIds as $id) {

            $conversation->participants()->firstOrCreate([
                'user_id'    => $id,
                'is_active'  => true, // for remove user
                'removed_at' => null,
                'left_at'    => null,
            ]);
            $addedUser = $this->findUser($id);

            $lastMessage = $conversation->messages()->create([
                'sender_id'    => $adder->id,
                'message'      => $adder->name . ' added ' . $addedUser->name . ' to the conversation',
                'message_type' => 'system',
            ]);

            //  Message broadcast
            event(new MessageEvent('sent', $lastMessage->conversation_id, ['message' => $lastMessage]));

            //  Send conversation to NEW user
            event(new ConversationEvent($conversation, 'added', $id));
        }

        return $lastMessage;
    }

    public function removeMember(int $userId, int $conversationId, array $memberIds)
    {
        if (! $this->canUserManageMembers($conversationId, $userId)) {
            abort(403, 'Only admins can remove members.');
        }

        $conversation = $this->find($conversationId);

        $members = User::whereIn('id', $memberIds)->get();

        $conversation->participants()
            ->whereIn('user_id', $memberIds)
            ->where('is_active', true)
            ->update([
                'is_active'  => false,
                'removed_at' => now(),
            ]);
        $lastMessage = null;
        foreach ($members as $member) {
            $lastMessage = $conversation->messages()->create([
                'sender_id' => $userId,
                'message'   => "{$member->name} was removed from the conversation",
                'message_type' => 'system',
            ]);

            event(new MessageEvent('sent', $lastMessage->conversation_id, ['message' => $lastMessage]));

            event(new ConversationEvent($conversation, 'removed', $member->id));
        }

        return $lastMessage;
    }

    public function addGroupAdmins(User $actor, int $conversationId, array $userIds)
    {
        if (! $this->canUserManageMembers($conversationId, $actor->id)) {
            abort(403, 'Only admins can add admins.');
        }

        $conversation = $this->find($conversationId);

        if ($conversation->type !== 'group') {
            abort(403, 'Admins are allowed only in group conversations.');
        }

        $participants = ConversationParticipant::where('conversation_id', $conversationId)->whereIn('user_id', $userIds)->where('role', 'member')->update(['role' => 'admin']);
        return $participants;

    }

    public function removeGroupAdmins(User $actor, int $conversationId, array $userIds)
    {
        if (! $this->canUserManageMembers($conversationId, $actor->id)) {
            abort(403, 'Only admins can remove admins.');
        }

        $conversation = $this->find($conversationId);

        if ($conversation->type !== 'group') {
            abort(403, 'Admins are allowed only in group conversations.');
        }

        $participants = ConversationParticipant::where('conversation_id', $conversationId)->whereIn('user_id', $userIds)->where('role', 'admin')->update(['role' => 'member']);
        return $participants;
    }

    public function leaveGroup(User $user, int $conversationId)
    {
        $conversation = $this->find($conversationId);

        $conversation->participants()->where('user_id', $user->id)
            ->update([
                'is_active' => false,
                'left_at'   => now(),
            ]);

        $systemMessage = $conversation->messages()->create([
            'sender_id'    => $user->id,
            'message'      => $user->name . ' left the conversation',
            'message_type' => 'system',
        ]);

        //  Broadcast system message to remaining users
        event(new MessageEvent('sent', $systemMessage->conversation_id, ['message' => $systemMessage]));

        //  REMOVE conversation from the user who left
        event(new ConversationEvent($conversation, 'left', $user->id));

        return true;
    }

    public function muteGroup(int $userId, int $conversationId, int $minutes = 0)
    {
        $conversation = $this->find($conversationId);

        $participant = $conversation->participants()->where('user_id', $userId)->firstOrFail();
        if ($minutes === 1) {
            $participant->update(['is_muted' => true, 'muted_until' => null]); // Unlimited mute
        } elseif ($minutes > 0) {
            $participant->update(['is_muted' => true, 'muted_until' => now()->addMinutes($minutes)]);
        } else {
            // Unmute
            $participant->update(['is_muted' => false, 'muted_until' => null]);
        }

        return $participant;
    }

    public function updateGroupInfo(int $userId, int $conversationId, array $data)
    {
        $participant = ConversationParticipant::where('conversation_id', $conversationId)
            ->where('user_id', $userId)
            ->firstOrFail();

        $setting = GroupSettings::where('conversation_id', $conversationId)->firstOrFail();

        // Only admins can change group info if restricted
        if (
            ! $setting->allow_members_to_change_group_info &&
            ! in_array($participant->role, ['admin', 'super_admin'])
        ) {
            abort(403, 'Only admins can update group info.');
        }

        $conversation = $this->find($conversationId);

        $conversation->update([
            'name' => $data['name'] ?? $conversation->name,
        ]);

        if (isset($data['group'])) {

            if (isset($data['group']['avatar'])) {
                deleteFile($conversation->groupSetting->avatar);
                $data['group']['avatar'] = uploadFile($data['group']['avatar'], 'uploads/groups/avatars');
            }

            $conversation->groupSetting()->update($data['group']);
        }

        return $conversation->fresh();
    }

    public function deleteGroup(int $conversationId)
    {
        $conversation = $this->find($conversationId);
        return $conversation->delete();
    }

    public function createDefault(int $conversationId): GroupSettings
    {
        return GroupSettings::create([
            'conversation_id' => $conversationId,
        ]);
    }

    public function updateSetting(int $conversationId, int $userId, array $data): GroupSettings
    {
        $participant = ConversationParticipant::where('conversation_id', $conversationId)
            ->where('user_id', $userId)
            ->firstOrFail();

        $setting = GroupSettings::where('conversation_id', $conversationId)->firstOrFail();

        // Only admins can change group info if restricted
        if (
            ! $setting->allow_members_to_change_group_info &&
            ! in_array($participant->role, ['admin', 'super_admin'])
        ) {
            abort(403, 'Only admins can update group info.');
        }

        $setting->update($data);

        return $setting;
    }

    public function toggleBlock(User $user, int $userId)
    {
        // 1. Prevent self block
        if ($user->id === $userId) {
            return response()->json(['error' => 'You cannot block yourself.'], 422);
        }

        // 2. Toggle block
        $user->blockedUsers()->toggle($userId);

        // 3. Check current block state
        $isBlocked = $user->blockedUsers()->where('users.id', $userId)->exists();

        // 4. Notify via event (only if private conversation exists)
        $conversation = app(ConversationRepository::class)->findPrivateBetween($user->id, $userId);

        if ($conversation) {
            event(new ConversationEvent($conversation, $isBlocked ? 'blocked' : 'unblocked', $userId));
        }

        return $isBlocked;
    }
    public function toggleRestrict(User $user, int $userId)
    {
        // Prevent restricting yourself
        if ($user->id === $userId) {
            return response()->json(['error' => 'You cannot restrict yourself.'], 422);
        }

        // Toggle restrict
        $user->restrictedUsers()->toggle($userId);

        $isRestricted = $user->restrictedUsers()->where('restricted_id', $userId)->exists();

        return $isRestricted;
    }

    public function canUserSendMessage(int $conversationId, int $userId): bool
    {
        $setting = GroupSettings::where('conversation_id', $conversationId)->first();

        if (! $setting) {
            return true;
        }

        if ($setting->allow_members_to_send_messages) {
            return true;
        }

        return ConversationParticipant::where('conversation_id', $conversationId)
            ->where('user_id', $userId)
            ->whereIn('role', ['admin', 'super_admin'])
            ->exists();
    }

    public function canUserManageMembers(int $conversationId, int $userId): bool
    {
        $setting = GroupSettings::where('conversation_id', $conversationId)->first();

        if (! $setting) {
            return true;
        }

        if ($setting->allow_members_to_add_remove_participants) {
            return true;
        }

        return ConversationParticipant::where('conversation_id', $conversationId)
            ->where('user_id', $userId)
            ->whereIn('role', ['admin', 'super_admin'])
            ->exists();
    }

    public function canUserPermit(int $conversationId, int $userId): bool
    {
        return ConversationParticipant::where('conversation_id', $conversationId)->where('user_id', $userId)->exists();
    }
}
