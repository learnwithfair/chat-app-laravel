<?php

namespace App\Http\Controllers\Api\Chat;

use App\Events\ConversationEvent;
use App\Events\MessageSentEvent;
use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\MessageReaction;
use App\Models\Participant;
use App\Models\UserMessageReact;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ReactMessageController extends Controller
{
    use ApiResponse;

    /**
     * React to a message (add or update reaction).
     * @param int $message_id
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function reactToggle(int $message_id, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'emoji' => 'required|in:like,love,laugh,surprised,sad,angry'
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), $validator->errors()->first(), 422);
        }

        $user = $request->user();
        $emoji = $request->input('emoji');

        // Check if message exists
        $message = Message::with('reactions')->find($message_id);
        if (!$message) {
            return $this->error([], "Message not found", 404);
        }

        // Check if user belongs to this conversation
        $isParticipant = Participant::where('conversation_id', $message->conversation_id)
            ->where('participant_id', $user->id)
            ->exists();

        if (!$isParticipant) {
            return $this->error([], 'Unauthorized', 403);
        }


        DB::beginTransaction();
        try {
            // Find existing reaction
            $existingReaction = MessageReaction::where('message_id', $message->id)
                ->where('user_id', $user->id)
                ->first();

            // If same emoji → remove reaction
            if ($existingReaction && $existingReaction->emoji === $emoji) {

                # Broadcast the message
                broadcast(new MessageSentEvent('message_react_remove', $message));

                foreach ($message->conversation->participants as $participant) {
                    # Broadcast the Conversation and Unread Message Count
                    broadcast(new ConversationEvent('message_react_remove', $message, $participant->participant_id));
                }

                $existingReaction->delete();
                DB::commit();

                return $this->success([], 'Reaction removed successfully', 200);
            }

            // Otherwise insert or update
            $react = MessageReaction::updateOrCreate(
                [
                    'message_id' => $message->id,
                    'user_id' => $user->id
                ],
                [
                    'emoji' => $emoji
                ]
            );

            DB::commit();

            $message->load('reactions');

            # Broadcast the message
            broadcast(new MessageSentEvent('message_react_send', $message));

            foreach ($message->conversation->participants as $participant) {
                # Broadcast the Conversation and Unread Message Count
                broadcast(new ConversationEvent('message_react_send', $message, $participant->participant_id));
            }

            return $this->success([], 'Reaction updated successfully', 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error($e, $e->getMessage(), 500);
        }
    }
}
