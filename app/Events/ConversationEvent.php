<?php
namespace App\Events;

use App\Models\Conversation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class ConversationEvent implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public Conversation $conversation;
    public string $action; // added | removed | left | updated | deleted |read
    public int $targetUserId;

    public function __construct(
        Conversation $conversation,
        string $action,
        int $targetUserId
    ) {
        $this->conversation = $conversation;
        $this->action       = $action;
        $this->targetUserId = $targetUserId;
    }

    public function broadcastOn(): PrivateChannel
    {
        // Send ONLY to specific user
        return new PrivateChannel('user.' . $this->targetUserId);
    }

    public function broadcastWith(): array
    {
        return [
            'action'       => $this->action, // added | removed | left | updated | deleted
            'conversation' => [
                'id'   => $this->conversation->id,
                'name' => $this->conversation->name,
                'type' => $this->conversation->type,
                'meta' => $this->conversation->meta ?? null,
            ],
        ];
    }
}
