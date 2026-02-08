<?php
namespace App\Http\Resources\Chat;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class MessageResource extends JsonResource
{

    public function toArray($request): array
    {

        $authId = Auth::id();
        // Handle deleted-for-everyone message
        if ($this->is_deleted_for_everyone) {
            return [
                'id'                      => $this->id,
                'conversation_id'         => $this->conversation_id,
                'sender'                  => [
                    'id'   => $this->sender->id,
                    'name' => $this->sender->name,
                ],

                'is_deleted_for_everyone' => true,
                'message'                 => $this->message,
                'message_type'            => 'system',
                'reply_to_message_id'     => null,
                'forward_to_message_id'   => null,
                'attachments'             => [],
                'reactions'               => [
                    'reactions' => [],
                    'total'     => 0,
                ],
                'statuses'                => [],
                'reply'                   => null,
                'is_mine'                 => $this->sender_id === $authId,

                'created_at'              => $this->created_at->toDateTimeString(),
                'updated_at'              => $this->updated_at->toDateTimeString(),
            ];
        }

        // Group reactions
        $groupedReactions = $this->reactions
            ->groupBy('reaction')
            ->map(fn($r) => $r->count())
            ->toArray();

        return [
            'id'                      => $this->id,
            'conversation_id'         => $this->conversation_id,

            'sender'                  => [
                'id'   => $this->sender->id,
                'name' => $this->sender->name,
            ],
            'is_deleted_for_everyone' => false,
            'message'                 => $this->is_restricted ? null : $this->message,
            'message_type'            => $this->message_type,

            'reply_to_message_id'     => $this->reply_to_message_id,

            // 'attachments'         => $this->attachments,
            'attachments'             => MessageAttachmentResource::collection($this->attachments),

            'reactions'               => [
                'reactions' => $groupedReactions,
                'total'     => array_sum($groupedReactions),
            ],

            'statuses'                => $this->statuses
                ->where('user_id', '!=', $authId)
                ->map(fn($s) => [
                    'user_id'     => $s->user_id,
                    'name'        => $s->user->name,
                    'avatar_path' => $s->user->avatar_path,
                    'status'      => $s->status,
                    'created_at'  => $s->created_at->toDateTimeString(),
                ])
                ->values(),
            'reply'                   => $this->replyTo ? [
                'id'      => $this->replyTo->id,
                'sender'  => [
                    'id'   => $this->replyTo->sender->id,
                    'name' => $this->replyTo->sender->name,
                ],
                'message' => $this->replyTo->message,
                'type'    => $this->replyTo->message_type,
            ] : null,

            'forward'                 => $this->forwardedFrom ? [
                'id'      => $this->forwardedFrom->id,
                'sender'  => [
                    'id'   => $this->forwardedFrom->sender->id,
                    'name' => $this->forwardedFrom->sender->name,
                ],
                'message' => $this->forwardedFrom->message,
                'type'    => $this->forwardedFrom->message_type,
            ] : null,
            'is_mine'                 => $this->sender_id === $authId,

            'created_at'              => $this->created_at->toDateTimeString(),
            'updated_at'              => $this->updated_at->toDateTimeString(),
        ];
    }
}
