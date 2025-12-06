<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $originalBody;

    public function __construct(Message $message, $originalBody)
    {
        $this->message = $message;
        $this->originalBody = $originalBody;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('conversation.' . $this->message->conversation_id);
    }
    public function broadcastAs()
{
    return 'MessageUpdated';
}
    public function broadcastWith()
    {
        return [
            'message' => $this->message->load('user'),
            'original_body' => $this->originalBody,
            'action' => 'updated'
        ];
    }
}