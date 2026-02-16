<?php
namespace App\Events;

use App\Models\Conversation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class ConversationEvent implements ShouldBroadcastNow
{
    use InteractsWithSockets, SerializesModels;

    public Conversation $conversation;
    public string $action;     // added | removed | left  | unblocked | blocked  | unmuted
                               // For global broadcast => updated | member_added | member_left | updated | deleted | admin_removed |admin_added
    public ?int $targetUserId; // if null then broadcast to group's all members
    public ?array $meta;

    public function __construct(
        Conversation $conversation,
        string $action,
        ?int $targetUserId = null,
        ?array $meta = null
    ) {
        $this->conversation = $conversation;
        $this->action       = $action;
        $this->targetUserId = $targetUserId;
        $this->meta         = $meta;
    }

    public function broadcastOn()
    {
        // Send ONLY to specific user
        if ($this->targetUserId) {
            return new PrivateChannel('user.' . $this->targetUserId);
        }

        // if null then broadcast to group's all members
        return new PresenceChannel('conversation.' . $this->conversation->id);
    }

    public function broadcastAs()
    {
        return 'ConversationEvent';
    }

    public function broadcastWith(): array
    {
        return [
            'action'       => $this->action,
            'conversation' => [
                'id'     => $this->conversation->id,
                'name'   => $this->conversation->name,
                'type'   => $this->conversation->type,
                'avatar' => $this->conversation?->group_setting->avatar ?? null,

                //  Merge dynamic meta here
                'meta'   => array_merge(
                    $this->conversation->meta ?? [],
                    $this->meta ?? []
                ),
            ],
        ];
    }

}
