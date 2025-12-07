<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageRead implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $conversationId;
    public $userId;
    public $messageId;

    public function __construct($conversationId, $userId, $messageId)
    {
        $this->conversationId = $conversationId;
        $this->userId = $userId;
        $this->messageId = $messageId;
    }

    public function broadcastOn()
    {
        // Broadcast to both conversation and user channels
        return [
            new PrivateChannel('conversation.' . $this->conversationId),
            new PrivateChannel('user.' . $this->userId), // Add user channel
        ];
    }

    public function broadcastWith()
    {
        return [
            'conversationId' => $this->conversationId,
            'userId' => $this->userId,
            'messageId' => $this->messageId,
        ];
    }

    public function broadcastAs()
    {
        return 'MessageRead';
    }
}