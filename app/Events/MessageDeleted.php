<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageDeleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct($message)
    {
        $this->message = $message;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('conversation.'.$this->message['conversation_id']);
    }

    public function broadcastAs()
    {
        return 'MessageDeleted';
    }

    public function broadcastWith()
    {
        return [
            'message_id' => $this->message['id'],
            'conversation_id' => $this->message['conversation_id'],
            'action' => 'deleted',
        ];
    }
}
