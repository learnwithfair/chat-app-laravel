<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class MessageSentEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $data, $type, $conversationId = null;

    /**
     * Create a new event instance.
     */
    public function __construct($type, $data)
    {
        $this->type = $type;
        $this->data = $data;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */

    public function broadcastOn(): array
    {
        if ($this->type == 'group_create') {
            $this->conversationId = $this->data->conversation_id;
        } else if ($this->type == 'message_send') {
            $this->conversationId = $this->data->conversation->id;
        } else if ($this->type == 'message_delete_for_everyone') {
            $this->conversationId = $this->data->conversation_id;
        } else if ($this->type == 'group_delete') {
            $this->conversationId = $this->data->conversation_id;
        } else if ($this->type == 'group_setting_change') {
            $this->conversationId = $this->data->conversation_id;
        } else if ($this->type == 'group_participant_manage') {
            $this->conversationId = $this->data->conversation_id;
        } else if ($this->type == 'message_react_remove' || $this->type == 'message_react_send') {
            $this->conversationId = $this->data->conversation_id;
        } else {
            Log::warning("⚠️ Unknown event type for broadcasting: {$this->type}");
        }

        $channelName = 'chat-channel.' . $this->conversationId;

        Log::info("📢 Broadcasting MessageSentEvent: {$channelName}");

        return [
            new PrivateChannel($channelName),
        ];
    }

    /**
     * Data to broadcast with the event.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'type' => $this->type,
            'data' => $this->data,
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'message.event';
    }
}
