<?php

namespace App\Http\Controllers\Api\Chat;

use App\Enum\NotificationType;
use App\Events\ConversationEvent;
use App\Events\MessageSentEvent;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Group;
use App\Models\Message;
use App\Models\Participant;
use App\Models\User;
use App\Notifications\ChatingNotification;
use App\Services\FCMService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class GroupDeleteController extends Controller
{
    use ApiResponse;

    /**
     * Delete the group.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request, int $id)
    {
        $user = auth()->user();

        if (!$user) {
            return $this->error([], 'Unauthorized', 401);
        }

        $group = Group::find($id);

        if (!$group) {
            return $this->error([], 'Group not found', 404);
        }

        // Check if the authenticated user is a super_admin of this group
        $isSuperAdmin = Participant::where('conversation_id', $group->conversation_id)
            ->where('participant_id', $user->id)
            ->where('role', 'super_admin')
            ->exists();

        if (!$isSuperAdmin) {
            return $this->error([], 'Forbidden: Only super admins can delete the group', 403);
        }

        DB::transaction(function () use ($group, $user) {

            // 1. Delete group avatar if exists
            if ($group->avatar) {
                $avatarPath = str_replace('storage/', '', $group->avatar);
                if (Storage::disk('public')->exists($avatarPath)) {
                    Storage::disk('public')->delete($avatarPath);
                }
            }

            $conversation_id = $group->conversation_id;
            $conversation = Conversation::find($conversation_id);
            $participants = Participant::where('conversation_id', $conversation_id)->select(['id', 'participant_id', 'participant_type'])->get();

            $messages = Message::with('attachments')->where('conversation_id', $conversation_id)->get();

            foreach ($messages as $message) {
                foreach ($message->attachments as $attachment) {
                    if ($attachment->file_path && Storage::disk('public')->exists($attachment->file_path)) {
                        Storage::disk('public')->delete($attachment->file_path);
                    }
                }
            }
            Message::where('conversation_id', $conversation_id)->delete();

            # Broadcast the message
            broadcast(new MessageSentEvent('group_delete', $group));

            foreach ($participants as $participant) {
                # Broadcast the Conversation and Unread Message Count
                broadcast(new ConversationEvent('group_delete', $group, $participant->participant_id));

                if ($participant->is_muted == 1) {
                    $fcmService = new FCMService();
                    $fcmService->sendMessage(
                        $participant->participant->firebaseTokens->token,
                        $user->name . ' deleted the group "' . $group->name . '".',
                        $group->name,
                        [
                            'type'       => 'group_delete',
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
                        'subject' => 'Group Deleted',
                        'message' => $user->name . ' deleted the group "' . $group->name . '".',
                        'actionText' => 'Visit Now',
                        'actionURL' => 'https://example.com',
                        'type' => NotificationType::SUCCESS,
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // 1. Delete the group (breaks FK constraint)
            $group->delete();

            // 2. Delete the conversation
            $conversation->delete();
        });

        return $this->success([], 'Group deleted successfully', 200);
    }
}
