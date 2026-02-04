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
use App\Traits\ApiResponse;
use Illuminate\Http\Exceptions\HttpResponseException;

class ConversationRepository
{
    use ApiResponse;
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
                    $q->where(function ($q) {
                        $q->whereNotNull('deleted_at') // for conversation removed
                            ->orWhere(function ($q) {
                                $q->whereNull('deleted_at')
                                    ->where('is_active', true);
                            });
                    })
                        ->with('user');
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

        return ConversationResource::collection($conversations);
    }

    public function findPrivateBetween(int $userId1, int $userId2): ?Conversation
    {
        return Conversation::where('type', 'private')
            ->whereHas('participants', fn($q) => $q->where('user_id', $userId1))
            ->whereHas('participants', fn($q) => $q->where('user_id', $userId2))
            ->first();
    }

    public function createPrivateConversation(int $userId1, int $userId2): ConversationResource
    {
        // Create the conversation
        $conversation = Conversation::create([
            'type' => 'private',
        ]);

        // Create participants
        $participants = [
            ['user_id' => $userId1, 'role' => 'member'],
            ['user_id' => $userId2, 'role' => 'member'],
        ];

        $conversation->participants()->createMany($participants);

        // Load relationships (same pattern as createGroupConversation)
        $conversation->load([
            'participants' => function ($q) {
                $q->where('is_active', true)->with('user');
            },
            'lastMessage.sender',
            'groupSetting', // Will be null for private conversations
        ]);

        $conversation->setRelation('unread_count', 0);

        return new ConversationResource($conversation);
    }

    public function createGroupConversation(array $data, int $creadtedId)
    {
        $conversation = Conversation::create([
            'type'       => 'group',
            'name'       => $data['name'] ?? 'New Group',
            'created_by' => $creadtedId,
        ]);

        $participants = [];

        $participants[] = ['user_id' => $creadtedId, 'role' => 'super_admin'];

        // Add other members, skip creator if included
        foreach ($data['participants'] ?? [] as $userId) {
            if ($userId == $creadtedId) {
                continue;
            }

            $participants[] = [
                'user_id' => $userId,
                'role'    => 'member',
            ];
        }

        $conversation->participants()->createMany($participants);
        app(ChatService::class)->createDefault($conversation->id);

        $conversation->groupSetting()->create([
            'description' => $data['group']['description'] ?? null,
            'type'        => $data['group']['type'] ?? 'private',
        ]);

        $conversation->load([
            'participants' => function ($q) {
                $q->where('is_active', true)->with('user');
            },
            'lastMessage.sender',
            'groupSetting',
        ]);

        $conversation->setRelation('unread_count', 0);

        //  Return wrapped in ConversationResource
        return new ConversationResource($conversation);
    }

    // need to work on it
    public function deleteForUser(int $userId, int $conversationId): bool
    {
        $participant = ConversationParticipant::where('conversation_id', $conversationId)
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->first();
        $lastMgsId = $participant->conversation->lastMessage->id;

        if (! $participant) {
            throw new HttpResponseException($this->error(null, 'Conversation not found or already removed.', 404));
        }

        $participant->update([
            'is_active'               => false,
            'last_deleted_message_id' => $lastMgsId,
            'deleted_at'              => now(),
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
            throw new HttpResponseException($this->error(null, 'You are not allowed to view this conversation.', 403));
        }
        $conversation = $this->find($conversationId);
        return $conversation->participants()->active()->with('user')->get();
    }

    // public function addMembers(User $adder, int $conversationId, array $memberIds)
    // {

    //     if (! $this->canUserManageMembers($conversationId, $adder->id)) {
    //         throw new HttpResponseException($this->error(null, 'Only admins can add members.', 403));
    //     }

    //     $conversation = $this->find($conversationId);
    //     $lastMessage  = null;

    //     foreach ($memberIds as $id) {

    //         $conversation->participants()->firstOrCreate([
    //             'user_id'    => $id,
    //             'is_active'  => true, // for remove user
    //             'removed_at' => null,
    //             'left_at'    => null,
    //         ]);
    //         $addedUser = $this->findUser($id);

    //         $lastMessage = $conversation->messages()->create([
    //             'sender_id'    => $adder->id,
    //             'message'      => $adder->name . ' added ' . $addedUser->name . ' to the conversation',
    //             'message_type' => 'system',
    //         ]);

    //         //  Message broadcast
    //         event(new MessageEvent('sent', $lastMessage->conversation_id, ['message' => $lastMessage]));

    //         //  Send conversation to NEW user
    //         event(new ConversationEvent($conversation, 'added', $id));
    //     }

    //     return $lastMessage;
    // }

    public function addMembers(User $adder, int $conversationId, array $memberIds)
    {
        if (! $this->canUserManageMembers($conversationId, $adder->id)) {
            throw new HttpResponseException(
                $this->error(null, 'Only admins can add members.', 403)
            );
        }

        $conversation = $this->find($conversationId);

        $addedMembers = [];
        $lastMessage  = null;

        foreach ($memberIds as $id) {

            // Skip self-add (optional safety)
            if ($id === $adder->id) {continue;}

            $participant = $conversation->participants()
                ->where('user_id', $id)
                ->first();

            $user = $this->findUser($id);

            $wasAdded = false;
            $action   = null;

            if ($participant) {
                //  Re-add removed / left users
                if ($participant->removed_at || $participant->left_at || ! $participant->is_active) {
                    $participant->update([
                        'is_active'  => true,
                        'removed_at' => null,
                        'left_at'    => null,
                    ]);

                    $wasAdded = true;
                    $action   = 're-added';
                }
            } else {
                // Brand new participant
                $participant = $conversation->participants()->create([
                    'user_id'    => $id,
                    'is_active'  => true,
                    'removed_at' => null,
                    'left_at'    => null,
                ]);

                $wasAdded = true;
                $action   = 'added';
            }

            // If user was already active, skip everything
            if (! $wasAdded) {continue;}

            //  Collect for frontend realtime update
            $addedMembers[] = [
                'id'     => $user->id,
                'name'   => $user->name,
                'avatar' => $user->avatar_path,
                'role'   => $participant->is_admin ? 'admin' : 'member',
            ];

            // System message
            $lastMessage = $conversation->messages()->create([
                'sender_id' => $adder->id,
                'message'   => "{$adder->name} {$action} {$user->name} to the conversation",
                'message_type' => 'system',
            ]);

            // Broadcast message
            event(new MessageEvent('sent', $conversation->id, ['message' => $lastMessage]));

            // Send conversation to added user
            event(new ConversationEvent($conversation, 'added', $id));
        }

        return $this->success(['members' => $addedMembers, 'message' => $lastMessage], 'Members added successfully');
    }

    // public function removeMember(int $userId, int $conversationId, array $memberIds)
    // {
    //     if (! $this->canUserManageMembers($conversationId, $userId)) {
    //         throw new HttpResponseException($this->error(null, 'Only admins can remove members.', 403));
    //     }

    //     $conversation = $this->find($conversationId);

    //     $members = User::whereIn('id', $memberIds)->get();

    //     $conversation->participants()
    //         ->whereIn('user_id', $memberIds)
    //         ->where('is_active', true)
    //         ->update([
    //             'is_active'  => false,
    //             'removed_at' => now(),
    //         ]);
    //     $lastMessage = null;
    //     foreach ($members as $member) {
    //         $lastMessage = $conversation->messages()->create([
    //             'sender_id' => $userId,
    //             'message'   => "{$member->name} was removed from the conversation",
    //             'message_type' => 'system',
    //         ]);

    //         event(new MessageEvent('sent', $lastMessage->conversation_id, ['message' => $lastMessage]));

    //         event(new ConversationEvent($conversation, 'removed', $member->id));
    //     }

    //     return $lastMessage;
    // }

    public function removeMember(int $actorId, int $conversationId, array $memberIds)
    {
        if (! $this->canUserManageMembers($conversationId, $actorId)) {
            throw new HttpResponseException(
                $this->error(null, 'Only admins can remove members.', 403)
            );
        }

        $conversation = $this->find($conversationId);
        $actor        = $this->findUser($actorId);

        $participants = $conversation->participants()->whereIn('user_id', $memberIds)->where('is_active', true)->get();

        $removedMembers = [];
        $lastMessage    = null;

        foreach ($participants as $participant) {

            $participant->update(['is_active' => false, 'removed_at' => now()]);
            $user             = $this->findUser($participant->user_id);
            $removedMembers[] = ['id' => $user->id, 'name' => $user->name];

            $lastMessage = $conversation->messages()->create([
                'sender_id' => $actorId,
                'message'   => "{$actor->name} removed {$user->name} from the conversation",
                'message_type' => 'system',
            ]);

            //  Realtime message
            event(new MessageEvent('sent', $conversation->id, ['message' => $lastMessage]));

            //  Realtime member removal
            event(new ConversationEvent($conversation, 'removed', $user->id));
        }

        return $this->success(['members' => $removedMembers, 'message' => $lastMessage], 'Members removed successfully');
    }

    public function addGroupAdmins(User $actor, int $conversationId, array $userIds)
    {
        if (! $this->canUserManageMembers($conversationId, $actor->id)) {
            throw new HttpResponseException($this->error(null, 'Only admins can add admins.', 403));
        }

        $conversation = $this->find($conversationId);

        if ($conversation->type !== 'group') {
            throw new HttpResponseException($this->error(null, 'Admins are allowed only in group conversations.', 403));
        }

        $participants = ConversationParticipant::where('conversation_id', $conversationId)->whereIn('user_id', $userIds)->where('role', 'member')->update(['role' => 'admin']);
        return $participants;

    }

    // public function removeGroupAdmins(User $actor, int $conversationId, array $userIds)
    // {
    //     if (! $this->canUserManageMembers($conversationId, $actor->id)) {
    //         throw new HttpResponseException($this->error(null, 'Only admins can remove admins.', 403));
    //     }

    //     $conversation = $this->find($conversationId);

    //     if ($conversation->type !== 'group') {
    //         throw new HttpResponseException($this->error(null, 'Admins are allowed only in group conversations.', 403));
    //     }

    //     $participants = ConversationParticipant::where('conversation_id', $conversationId)->whereIn('user_id', $userIds)->where('role', 'admin')->update(['role' => 'member']);
    //     return $participants;
    // }

    public function removeGroupAdmins(User $actor, int $conversationId, array $userIds)
    {
        if (! $this->canUserManageMembers($conversationId, $actor->id)) {
            throw new HttpResponseException(
                $this->error(null, 'Only admins can remove admins.', 403)
            );
        }

        $conversation = $this->find($conversationId);

        if ($conversation->type !== 'group') {
            throw new HttpResponseException(
                $this->error(null, 'Admins are allowed only in group conversations.', 403)
            );
        }

        $participants = ConversationParticipant::where('conversation_id', $conversationId)
            ->whereIn('user_id', $userIds)
            ->where('role', 'admin')
            ->get();

        $updatedMembers = [];
        $lastMessage    = null;

        foreach ($participants as $participant) {

            $participant->update(['role' => 'member']);

            $user = $this->findUser($participant->user_id);

            $updatedMembers[] = [
                'id'   => $user->id,
                'role' => 'member',
            ];

            $lastMessage = $conversation->messages()->create([
                'sender_id' => $actor->id,
                'message'   => "{$actor->name} removed admin rights from {$user->name}",
                'message_type' => 'system',
            ]);

            //  Realtime role update
            event(new ConversationEvent($conversation, 'admin_removed', $user->id));
        }
        $data = [
            'members' => $updatedMembers,
            'message' => $lastMessage,
        ];

        return $data;
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
            throw new HttpResponseException($this->error(null, 'Only admins can update group info.', 403));
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

        $conversation = $conversation->fresh(); // reload

        return $conversation->load('groupSetting');
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
            throw new HttpResponseException($this->error(null, 'Only admins can update group info.', 403));
        }

        $setting->update($data);

        return $setting;
    }

    public function toggleBlock(User $user, int $userId)
    {
        // 1. Prevent self block
        if ($user->id === $userId) {
            throw new HttpResponseException($this->error(null, 'You cannot block yourself.', 422));
        }

        // 2. Toggle block
        $user->blockedUsers()->toggle($userId);

        // 3. Check current block state
        $isBlocked = $user->blockedUsers()->where('users.id', $userId)->exists();

        // 4. Notify via event (only if private conversation exists)
        $conversation = $this->findPrivateBetween($user->id, $userId);

        if ($conversation) {
            event(new ConversationEvent($conversation, $isBlocked ? 'blocked' : 'unblocked', $userId));
        }

        return $isBlocked;
    }

    public function toggleRestrict(User $user, int $userId)
    {
        // Prevent restricting yourself
        if ($user->id === $userId) {
            throw new HttpResponseException($this->error(null, 'You cannot restrict yourself.', 422));
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
    public function canGroupDeletePermit(int $userId, int $conversationId): bool
    {
        $participant = ConversationParticipant::where('conversation_id', $conversationId)
            ->where('user_id', $userId)
            ->firstOrFail();

        if (! in_array($participant->role, ['super_admin'])) {
            throw new HttpResponseException($this->error(null, 'Only super admins can delete the group.', 403));
        }
        return true;
    }
}
