<?php
namespace App\Events;

use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class UserStatusEvent implements ShouldBroadcast
{
    use SerializesModels;

    public string $type; // online | offline | typing | typing_stop
    public array $payload;

    public function __construct(string $type, array $payload)
    {
        $this->type    = $type;
        $this->payload = $payload;
    }

    public function broadcastOn()
    {
        return new PresenceChannel('chat.users');
    }

    public function broadcastWith(): array
    {
        return [
            'type'    => $this->type,
            'payload' => $this->payload,
        ];
    }
}
