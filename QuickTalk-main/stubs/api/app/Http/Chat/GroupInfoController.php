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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class GroupInfoController extends Controller
{
    use ApiResponse;
    /**
     * Handle the incoming request to get group info.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getInfo(Request $request, int $id)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->error([], 'Unauthorized', 401);
        }

        $group = Group::query()
            ->whereHas('conversation.participants', function ($query) use ($user) {
                $query->where('participant_id', $user->id);
            })
            ->find($id);


        if (!$group) {
            return $this->error([], 'Group not found', 404);
        }
        return $this->success($group, 'Group info retrieved successfully', 200);
    }

    /**
     * Update group information.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateInfo(Request $request, int $id)
    {
        $user = auth()->user();

        if (!$user) {
            return $this->error([], 'Unauthorized', 401);
        }

        $group = Group::find($id);

        if (!$group) {
            return $this->error([], 'Group not found', 404);
        }

        // Check permissions based on group settings
        if ($group->allow_members_to_change_group_info == 0) {
            // Check if the authenticated user is a leader (admin or super_admin)
            $isLeader = Participant::where('conversation_id', $group->conversation_id)
                ->where('participant_id', $user->id)
                ->whereIn('role', ['admin', 'super_admin'])
                ->exists();

            if (!$isLeader) {
                return $this->error([], 'Forbidden: Only group leaders can update group info', 403);
            }
        } else {
            // Check if the user is a participant of the group
            $isParticipant = Participant::where('conversation_id', $group->conversation_id)
                ->where('participant_id', $user->id)
                ->exists();

            if (!$isParticipant) {
                return $this->error([], 'Forbidden: Only group participants can update group info', 403);
            }
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'name'        => 'sometimes|required|string|max:255',
            'avatar'      => 'sometimes|nullable|image|max:10240', // max 10MB
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), $validator->errors()->first(), 422);
        }

        // Update group info
        if ($request->has('name')) {
            $group->name = $request->name;
        }

        if ($request->hasFile('avatar')) {

            // 1. Delete old avatar if exists
            if ($group->avatar) {
                $avatarPath = str_replace('storage/', '', $group->avatar);
                if (Storage::disk('public')->exists($avatarPath)) {
                    Storage::disk('public')->delete($avatarPath);
                }
            }

            // 2. Store new avatar
            $path = $request->file('avatar')->store('group/avatars', 'public');

            // 3. Update db path with correct URL format
            $group->avatar = 'storage/' . $path;
        }

        $message = Message::create([
            'sender_id' => $user->id,
            'conversation_id' => $group->conversation_id,
            'message' => $user->name . ' updated the group info',
            'message_type' => 'system',
            'created_at' => Carbon::now(),
        ]);

        $group->save();

        # Broadcast the message
        broadcast(new MessageSentEvent('group_setting_change', $message));

        foreach ($group->conversation->participants as $participant) {
            # Broadcast the Conversation and Unread Message Count
            broadcast(new ConversationEvent('group_setting_change', $message, $participant->participant_id));

            if ($participant->is_muted == 1) {
                $fcmService = new FCMService();
                $fcmService->sendMessage(
                    $participant->participant->firebaseTokens->token,
                    $user->name . ' updated the group info.',
                    $group->name,
                    [
                        'type'       => 'group_setting_change',
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
                    'subject' => 'Group Info Updated',
                    'message' => $user->name . ' updated the group info.',
                    'actionText' => 'Visit Now',
                    'actionURL' => 'https://example.com',
                    'type' => NotificationType::SUCCESS,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $this->success($group, 'Group info updated successfully', 200);
    }
}
