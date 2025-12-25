<?php
namespace App\Http\Resources\Chat;

use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray($request): array
    {
        // Handle deleted-for-everyone message
        if ($this->is_deleted_for_everyone) {
            return [
                'id'                      => $this->id,
                'conversation_id'         => $this->conversation_id,
                'sender_id'               => $this->sender_id,
                'is_deleted_for_everyone' => true,
                'message'                 => $this->message,
                'message_type'            => 'system',
                'attachments'             => [],
                'reactions'               => [
                    'reactions' => [],
                    'total'     => 0,
                ],
                'statuses'                => [],
                'created_at'              => $this->created_at->toDateTimeString(),
                'reply'                   => null,
            ];
        }

        // Group reactions
        $groupedReactions = $this->reactions
            ->groupBy('reaction')
            ->map(fn($r) => $r->count())
            ->toArray();

        return [
            'id'                  => $this->id,
            'conversation_id'     => $this->conversation_id,

            'sender'              => [
                'id'   => $this->sender->id,
                'name' => $this->sender->name,
            ],

            'message'             => $this->is_restricted ? null : $this->message,
            'message_type'        => $this->message_type,

            'reply_to_message_id' => $this->reply_to_message_id,

            'attachments'         => $this->attachments,

            'reactions'           => [
                'reactions' => $groupedReactions,
                'total'     => array_sum($groupedReactions),
            ],

            'statuses'            => $this->statuses->map(fn($s) => [
                'user_id' => $s->user_id,
                'status'  => $s->status,
            ]),

            'reply'               => $this->replyTo ? [
                'id'      => $this->replyTo->id,
                'sender'  => [
                    'id'   => $this->replyTo->sender->id,
                    'name' => $this->replyTo->sender->name,
                ],
                'message' => $this->replyTo->message,
                'type'    => $this->replyTo->message_type,
            ] : null,

            'created_at'          => $this->created_at->toDateTimeString(),
        ];
    }
}
