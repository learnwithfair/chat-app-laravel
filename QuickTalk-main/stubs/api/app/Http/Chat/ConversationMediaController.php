<?php

namespace App\Http\Controllers\Api\Chat;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class ConversationMediaController extends Controller
{
    use ApiResponse;

    /**
     * Handle the incoming request to get conversation media.
     *
     * @param int $conversation_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke($conversation_id)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->error([], 'Unauthorized', 401);
        }

        $conversation = Conversation::whereHas('participants', function ($query) use ($user) {
            $query->where('participant_id', $user->id);
        })->find($conversation_id);

        if (!$conversation) {
            return $this->error([], 'Conversation not found', 404);
        }

        $mediaMessages = $conversation
            ->messages()
            ->select(['id'])
            ->whereHas('attachments')
            ->with('attachments')
            ->latest()
            ->paginate(50);

        return $this->success($mediaMessages, 'Conversation media retrieved successfully', 200);
    }
}
