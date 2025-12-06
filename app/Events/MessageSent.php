<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Services\ChatService;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    protected $chatService;

    public function __construct($message)
    {
        $this->message = $message;
        $this->chatService = app(ChatService::class);
    }

public function broadcastOn()
{
    // Broadcast to conversation channel
    return new PrivateChannel('conversation.' . $this->message->conversation_id);
}

    public function broadcastWith()
    {
        return [
            'message' => $this->message->load('user'),
        ];
    }

    public function broadcastAs()
    {
        return 'MessageSent';
    }
}