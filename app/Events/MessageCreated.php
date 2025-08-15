<?php
namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class MessageCreated implements ShouldBroadcastNow
{
    use InteractsWithSockets, SerializesModels;

    public function __construct(public Message $message)
    {}

    public function broadcastOn(): array
    {
        return [new PresenceChannel("presence.chat.{$this->message->conversation_id}")];
    }

    public function broadcastWith(): array
    {
        $a = $this->message->attachments->map(function ($att) {
            return [
                'url'           => Storage::url($att->path),
                'mime'          => $att->mime,
                'type'          => $att->type,
                'original_name' => $att->original_name,
                'size'          => $att->size,
            ];
        });

        return [
            'id'              => $this->message->id,
            'body'            => $this->message->body,
            'sender'          => [
                'id'   => $this->message->sender->id,
                'name' => $this->message->sender->name,
            ],
            'conversation_id' => $this->message->conversation_id,
            'created_at'      => $this->message->created_at?->toIso8601String(),
            'attachments'     => $a,
        ];
    }
}
