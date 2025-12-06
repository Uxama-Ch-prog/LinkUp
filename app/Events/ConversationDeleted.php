<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConversationDeleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $conversationId;

    public $userId;

    public function __construct($conversationId, $userId)
    {
        $this->conversationId = $conversationId;
        $this->userId = $userId;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('user.'.$this->userId);
    }

    public function broadcastAs()
    {
        return 'ConversationDeleted';
    }

    public function broadcastWith()
    {
        return [
            'conversation_id' => $this->conversationId,
            'deleted_by' => $this->userId,
        ];
    }
}
