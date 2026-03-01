<?php

namespace App\Http\Controllers\Api\Chat;

use App\Enum\NotificationType;
use App\Events\ConversationEvent;
use App\Events\MessageSentEvent;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\User;
use App\Notifications\ChatingNotification;
use App\Services\FCMService;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CreateGroupController extends Controller
{
    use ApiResponse;

    /**
     * Handle the incoming request to create a group.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'members' => 'required|array',
            'members.*' => 'exists:users,id',
            'type' => 'nullable|in:public,private',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), $validator->errors()->first(), 422);
        }

        $user = auth()->user();
        if (!$user) {
            return $this->error([], 'Unauthorized', 401);
        }

        // Add participants (authenticated user + selected members)
        $participantData = collect($request->members)
            ->filter(fn($memberId) => $memberId != $user->id) // avoid duplicate
            ->map(fn($id) => [
                'participant_id' => $id,
                'participant_type' => get_class($user),
                'role' => 'member',
                'joined_at' => Carbon::now(),
            ])
            ->toArray();

        $participantData[] = [
            'participant_id' => $user->id,
            'participant_type' => get_class($user),
            'role' => 'super_admin',
            'joined_at' => Carbon::now(),
        ];

        if ((empty($participantData) || count($participantData) < 3) && count($request->members) >= 100) {
            return $this->error([], 'At least three member is required', 422);
        }

        $maxParticipants = config('chat.groupParticipateLimit');
        $newCount = count($participantData);
        if ($newCount > $maxParticipants) {
            return $this->error([], "A group can have a maximum of {$maxParticipants} participants", 422);
        }

        // Create the conversation
        $conversation = Conversation::create([
            'type' => 'group',
        ]);

        $conversation->participants()->createMany($participantData);

        // Create system message
        $conversation->messages()->create([
            'sender_id' => $user->id,
            'message' => 'Group created',
            'message_type' => 'system',
        ]);

        // Save avatar if provided
        $avatarPath = null;
        if ($request->hasFile('avatar')) {
            $avatarPath = 'storage/' . $request->file('avatar')->store('group/avatars', 'public');
        }

        // Create group info
        $group = $conversation->group()->create([
            'name' => $request->input('name'),
            'avatar' => $avatarPath,
            'type' => $request->input('type', 'private'),
        ]);

        foreach ($conversation->participants as $participant) {
            # Broadcast the Conversation and Unread Message Count
            broadcast(new ConversationEvent('group_created', $group, $participant->participant_id));

            if ($participant->is_muted == 1) {
                $fcmService = new FCMService();
                $fcmService->sendMessage(
                    $participant->participant->firebaseTokens->token,
                    $user->name . ' created the group "' . $group->name . '".',
                    $request->input('name'),
                    [
                        'type'       => 'group_created',
                        'conversation_id' => (string)$conversation->id,
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
                    'subject' => 'Group Created',
                    'message' => $user->name . ' created the group "' . $group->name . '".',
                    'actionText' => 'Visit Now',
                    'actionURL' => 'https://example.com',
                    'type' => NotificationType::SUCCESS,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $this->success([
            'conversation_id' => $conversation->id,
            'group' => $group,
            'participants' => $conversation->participants()->with('participant:id,name,avatar')->get(),
        ], 'Group created successfully');
    }
}
