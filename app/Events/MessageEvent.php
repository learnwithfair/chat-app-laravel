<?php
namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class MessageEvent implements ShouldBroadcast
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

    public function broadcastOn()
    {
        return new PrivateChannel('conversation.' . $this->conversationId);
    }

    public function broadcastWith(): array
    {
        return [
            'type'    => $this->type,
            'payload' => $this->payload,
        ];
    }
}
