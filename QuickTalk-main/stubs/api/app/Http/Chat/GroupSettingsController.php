<?php

namespace App\Http\Controllers\Api\Chat;

use App\Enum\NotificationType;
use App\Events\ConversationEvent;
use App\Events\MessageSentEvent;
use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Message;
use App\Models\User;
use App\Notifications\ChatingNotification;
use App\Services\FCMService;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class GroupSettingsController extends Controller
{
    use ApiResponse;

    /**
     * Handle the incoming request to get group info.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function permissionsToggle(Request $request, int $id)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->error([], 'Unauthorized', 401);
        }

        $validator = Validator::make($request->all(), [
            'key' => 'required|string|max:255|in:allow_members_to_send_messages,allow_members_to_change_group_info,allow_members_to_add_remove_participants',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), $validator->errors()->first(), 422);
        }

        $key = $request->key ?? null;

        $group = Group::query()
            ->whereHas('conversation.participants', function ($query) use ($user) {
                $query->where('participant_id', $user->id)->whereIn('role', ['super_admin', 'admin']);
            })
            ->find($id);


        if (!$group) {
            return $this->error([], 'Group not found or unauthorized access.', 404);
        }

        $group->$key = !$group->$key;
        $group->save();

        $message = Message::create([
            'sender_id' => $user->id,
            'conversation_id' => $group->conversation_id,
            'message' => $user->name . ' change group setting',
            'message_type' => 'system',
            'created_at' => Carbon::now(),
        ]);

        # Broadcast the message
        broadcast(new MessageSentEvent('group_setting_change', $message));

        foreach ($group->conversation->participants as $participant) {
            # Broadcast the Conversation and Unread Message Count
            broadcast(new ConversationEvent('group_setting_change', $message, $participant->participant_id));

            if ($participant->is_muted == 1) {
                $fcmService = new FCMService();
                $fcmService->sendMessage(
                    $participant->participant->firebaseTokens->token,
                    $user->name . ' changed the group permissions.',
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
                    'subject' => 'Admin changed group permissions',
                    'message' => $user->name . ' changed the group permissions.',
                    'actionText' => 'Visit Now',
                    'actionURL' => 'https://example.com',
                    'type' => NotificationType::SUCCESS,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $this->success($group, 'Group setting updated successfully', 200);
    }

    /**
     * Handle the incoming request to toggle group type.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function groupTypeToggle(Request $request, int $id)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->error([], 'Unauthorized', 401);
        }

        $group = Group::query()
            ->whereHas('conversation.participants', function ($query) use ($user) {
                $query->where('participant_id', $user->id)->whereIn('role', ['super_admin', 'admin']);
            })
            ->find($id);

        if (!$group) {
            return $this->error([], 'Group not found or unauthorized access.', 404);
        }

        $group->type = $group->type === 'public' ? 'private' : 'public';
        $group->save();

        $message = Message::create([
            'sender_id' => $user->id,
            'conversation_id' => $group->conversation_id,
            'message' => $user->name . ' change group setting',
            'message_type' => 'system',
            'created_at' => Carbon::now(),
        ]);

        # Broadcast the message
        broadcast(new MessageSentEvent('group_setting_change', $message));

        foreach ($group->conversation->participants as $participant) {
            # Broadcast the Conversation and Unread Message Count
            broadcast(new ConversationEvent('group_setting_change', $message, $participant->participant_id));

            if ($participant->is_muted == 1) {
                $fcmService = new FCMService();
                $fcmService->sendMessage(
                    $participant->participant->firebaseTokens->token,
                    $user->name . ' changed the group type to "' . $group->type . '".',
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
                    'subject' => 'Admin changed group type',
                    'message' => $user->name . ' changed the group type to "' . $group->type . '".',
                    'actionText' => 'Visit Now',
                    'actionURL' => 'https://example.com',
                    'type' => NotificationType::SUCCESS,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $this->success($group, 'Group type updated successfully', 200);
    }
}
