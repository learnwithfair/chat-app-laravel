<?php

namespace App\Http\Controllers\Api\Chat;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Participant;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class ConversationSettingController extends Controller
{
    use ApiResponse;

    /**
     * Toggle notification setting for the authenticated user in a conversation.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function notificationSetting(Request $request, int $id)
    {
        $user = auth()->user();

        if (!$user) {
            return $this->error([], 'Unauthorized', 401);
        }

        $participant = Participant::where('conversation_id', $id)
            ->where('participant_id', $user->id)
            ->first();

        if (!$participant) {
            return $this->error([], 'You are not a participant in this conversation or it does not exist.', 404);
        }

        $participant->is_muted = !$participant->is_muted;
        $participant->save();

        $status = $participant->is_muted ? 'muted' : 'unmuted';

        return $this->success([
            'is_muted' => $participant->is_muted
        ], "Notifications have been {$status} successfully.");
    }
}
