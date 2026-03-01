<?php

namespace App\Http\Controllers\Api\Chat;

use App\Enum\NotificationType;
use App\Events\ConversationEvent;
use App\Events\MessageSentEvent;
use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Message;
use App\Models\Participant;
use App\Models\User;
use App\Notifications\ChatingNotification;
use App\Services\FCMService;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class GroupParticipantManageController extends Controller
{
    use ApiResponse;
    /**
     * Add members to a group.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function addParticipate(Request $request, int $id)
    {
        $user = auth()->user();

        if (!$user) {
            return $this->error([], 'Unauthorized', 401);
        }

        $group = Group::find($id);

        if (!$group) {
            return $this->error([], 'Group not found', 404);
        }

        // Check if the user has permission to add members
        if ($group->allow_members_to_add_remove_participants == 0) {
            // Check if the authenticated user is a leader (admin or super_admin)
            $isLeader = Participant::where('conversation_id', $group->conversation_id)
                ->where('participant_id', $user->id)
                ->whereIn('role', ['admin', 'super_admin'])
                ->exists();

            if (!$isLeader) {
                return $this->error([], 'Forbidden: Only group leaders can add members', 403);
            }
        } else {
            // Check if the authenticated user is a participant of the group
            $isParticipant = Participant::where('conversation_id', $group->conversation_id)
                ->where('participant_id', $user->id)
                ->exists();

            if (!$isParticipant) {
                return $this->error([], 'Forbidden: Only group participants can add members', 403);
            }
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'member_ids'   => 'required|array|min:1',
            'member_ids.*' => 'exists:users,id',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), $validator->errors()->first(), 422);
        }

        // Remove duplicate IDs in the request
        $memberIds = array_unique($request->member_ids);

        $maxParticipants = config('chat.groupParticipateLimit'); // set your group limit
        $currentCount = Participant::where('conversation_id', $group->conversation_id)->count();

        // Check total after adding requested members
        if ($currentCount + count($memberIds) > $maxParticipants) {
            $availableSlots = max(0, $maxParticipants - $currentCount);
            return $this->error([], "You can only add {$availableSlots} more participant(s) to this group (max {$maxParticipants}).", 422);
        }

        $added = [];
        $skipped = [];

        foreach ($memberIds as $memberId) {

            // Prevent leader from adding himself
            if ($memberId == $user->id) {
                $skipped[] = [
                    'member_id' => $memberId,
                    'reason'    => 'Cannot add yourself',
                ];
                continue;
            }

            // Create participant if not exists
            $participant = Participant::firstOrCreate(
                [
                    'conversation_id' => $group->conversation_id,
                    'participant_id'   => $memberId,
                ],
                [
                    'role' => 'member',
                    'participant_type' => User::class,
                    'joined_at' => Carbon::now(),
                ]
            );

            if ($participant->wasRecentlyCreated) {
                $added[] = $memberId;

                $member = User::find($memberId);
                $message = Message::create([
                    'sender_id' => $user->id,
                    'conversation_id' => $group->conversation_id,
                    'message' => $user->name . ' added participant to the conversation',
                    'message_type' => 'system',
                    'created_at' => Carbon::now(),
                ]);
            } else {
                $skipped[] = [
                    'member_id' => $memberId,
                    'reason'    => 'Already a member',
                ];
            }
        }

        # Broadcast the message
        broadcast(new MessageSentEvent('group_participant_manage', $message));

        foreach ($group->conversation->participants as $participant) {
            # Broadcast the Conversation and Unread Message Count
            broadcast(new ConversationEvent('group_participant_manage', $message, $participant->participant_id));

            if ($participant->is_muted == 1) {
                $fcmService = new FCMService();
                $fcmService->sendMessage(
                    $participant->participant->firebaseTokens->token,
                    $user->name . ' added participant to the conversation',
                    $group->name,
                    [
                        'type'       => 'group_participant_manage',
                        'conversation_id' => (string)$group->conversation_id,
                        'message_id' => null,
                    ]
                );
            }
        }

        return $this->success([
            'added'   => $added,
            'skipped' => $skipped,
        ], 'Members added successfully', 200);
    }


    /**
     * Remove a member from a group.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeParticipate(Request $request, int $id)
    {
        $user = auth()->user();

        if (!$user) {
            return $this->error([], 'Unauthorized', 401);
        }

        $group = Group::find($id);

        if (!$group) {
            return $this->error([], 'Group not found', 404);
        }

        // Check if the user has permission to remove members
        if ($group->allow_members_to_add_remove_participants == 0) {
            // Check if the authenticated user is a leader (admin or super_admin)
            $isLeader = Participant::where('conversation_id', $group->conversation_id)
                ->where('participant_id', $user->id)
                ->whereIn('role', ['admin', 'super_admin'])
                ->exists();

            if (!$isLeader) {
                return $this->error([], 'Forbidden: Only group leaders can remove members', 403);
            }
        } else {
            // Check if the authenticated user is a participant of the group
            $isParticipant = Participant::where('conversation_id', $group->conversation_id)
                ->where('participant_id', $user->id)
                ->exists();

            if (!$isParticipant) {
                return $this->error([], 'Forbidden: Only group participants can remove members', 403);
            }
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'member_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), $validator->errors()->first(), 422);
        }

        $memberId = $request->member_id;

        // Prevent leader from removing himself
        if ($memberId == $user->id) {
            return $this->error([], 'Cannot remove yourself', 422);
        }

        // Check if the member exists in the group
        $participant = Participant::where('conversation_id', $group->conversation_id)
            ->where('participant_id', $memberId)
            ->first();

        if (!$participant) {
            return $this->error([], 'Member not found in the group', 404);
        }

        $member = User::find($memberId);
        $message = Message::create([
            'sender_id' => $user->id,
            'conversation_id' => $group->conversation_id,
            'message' => $user->name . ' remove ' . $member->name . ' from the conversation',
            'message_type' => 'system',
            'created_at' => Carbon::now(),
        ]);

        # Broadcast the message
        broadcast(new MessageSentEvent('group_participant_manage', $message));

        foreach ($group->conversation->participants as $userParticipant) {
            # Broadcast the Conversation and Unread Message Count
            broadcast(new ConversationEvent('group_participant_manage', $message, $userParticipant->participant_id));

            if ($participant->is_muted == 1) {
                $fcmService = new FCMService();
                $fcmService->sendMessage(
                    $participant->participant->firebaseTokens->token,
                    $user->name . ' remove ' . $member->name . ' from the conversation',
                    $group->name,
                    [
                        'type'       => 'group_participant_manage',
                        'conversation_id' => (string)$group->conversation_id,
                        'message_id' => null,
                    ]
                );
            }
        }

        // Remove participant
        $participant->delete();

        return $this->success([], 'Member removed successfully', 200);
    }

    /**
     * Leave the group.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function leaveGroup(Request $request, int $id)
    {
        $user = auth()->user();

        if (!$user) {
            return $this->error([], 'Unauthorized', 401);
        }

        $group = Group::find($id);

        if (!$group) {
            return $this->error([], 'Group not found', 404);
        }

        // Check if the user is a participant in the group
        $participant = Participant::where('conversation_id', $group->conversation_id)
            ->where('participant_id', $user->id)
            ->first();

        if (!$participant) {
            return $this->error([], 'You are not a member of this group', 403);
        }

        // Prevent super_admin from leaving
        if ($participant->role === 'super_admin') {
            return $this->error([], 'Super admins cannot leave the group', 403);
        }

        $message = Message::create([
            'sender_id' => $user->id,
            'conversation_id' => $group->conversation_id,
            'message' => $user->name . ' has left the conversation',
            'message_type' => 'system',
            'created_at' => Carbon::now(),
        ]);

        # Broadcast the message
        broadcast(new MessageSentEvent('group_participant_manage', $message));

        foreach ($group->conversation->participants as $userParticipant) {
            # Broadcast the Conversation and Unread Message Count
            broadcast(new ConversationEvent('group_participant_manage', $message, $userParticipant->participant_id));

            if ($participant->is_muted == 1) {
                $fcmService = new FCMService();
                $fcmService->sendMessage(
                    $participant->participant->firebaseTokens->token,
                    $user->name . ' left the group "' . $group->name . '".',
                    $group->name,
                    [
                        'type'       => 'group_participant_manage',
                        'conversation_id' => (string)$group->conversation_id,
                        'message_id' => null,
                    ]
                );
            }

            DB::table('notifications')->insert([
                'id' => \Illuminate\Support\Str::uuid(),
                'type' => ChatingNotification::class,
                'notifiable_type' => User::class,
                'notifiable_id' => $participant->participant_id,
                'data' => json_encode([
                    'subject' => 'Participant Left Group',
                    'message' => $user->name . ' left the group "' . $group->name . '".',
                    'actionText' => 'Visit Now',
                    'actionURL' => 'https://example.com',
                    'type' => NotificationType::SUCCESS,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Remove participant
        $participant->delete();

        return $this->success([], 'You have left the group successfully', 200);
    }
}
