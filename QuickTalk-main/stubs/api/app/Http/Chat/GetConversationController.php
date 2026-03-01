<?php

namespace App\Http\Controllers\Api\Chat;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\MessageStatus;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class GetConversationController extends Controller
{
    use ApiResponse;

    public function __invoke()
    {
        $user = auth()->user();
        if (!$user) {
            return $this->error([], 'Unauthorized', 401);
        }

        $name = request()->query('name') ?? null;

        $conversations = Conversation::query()

            ->with([
                'participants' => function ($query) use ($user, $name) {
                    $query->where('participant_id', '!=', $user->id)
                        ->where('participant_type', get_class($user))
                        ->with(['participant' => function ($q) use ($name) {
                            $q->select('id', 'name', 'avatar');
                        }])
                        ->take(3);
                },
                'lastMessage',
                'group'
            ])
            ->whereHas('participants', function ($query) use ($user) {
                $query->where('participant_type', get_class($user))
                    ->where('participant_id', $user->id);
            })
            ->when($name, function ($query) use ($name, $user) {
                $query->whereHas('participants.participant', function ($q) use ($name, $user) {
                    $q->where('name', 'like', "%{$name}%")
                        ->where('id', '!=', $user->id);
                });
            })
            ->withCount([
                'messages as unread_messages_count' => function ($q) use ($user) {
                    $q->where('receiver_id', $user->id)
                        ->where('created_at', '>', function ($sub) use ($user) {
                            $sub->selectRaw('COALESCE(updated_at, "1970-01-01 00:00:00")')
                                ->from('message_statuses')
                                ->whereColumn('conversation_id', 'messages.conversation_id')
                                ->where('user_id', $user->id)
                                ->limit(1);
                        });
                }
            ])
            ->latest('updated_at')
            ->paginate(15);

        $response = [
            'total_unread_messages' => $conversations->sum('unread_messages_count'),
            'total_conversations' => $conversations->count(),
            'self' => $user->only(['id', 'name', 'avatar']),
            'conversations' => $conversations,
        ];

        return $this->success($response, 'Conversations fetched successfully.', 200);
    }
}
