<?php
namespace App\Events;

use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class MessageEvent implements ShouldBroadcastNow
{
    use SerializesModels;

    public string $type; // sent | updated | deleted | reaction | updated deleted_for_me | deleted_for_everyone | delivered
    public array $payload;
    public int $conversationId;

    public function __construct(string $type, int $conversationId, array $payload)
    {
        $this->type           = $type;
        $this->conversationId = $conversationId;
        $this->payload        = $payload;
    }

    // public function broadcastOn()
    // {
    //     return new PrivateChannel('conversation.' . $this->conversationId);
    // }
    public function broadcastOn()
    {
        return new PresenceChannel('conversation.' . $this->conversationId);
    }

    public function broadcastAs()
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'type'    => $this->type,
            'payload' => $this->payload,
        ];
    }
}
