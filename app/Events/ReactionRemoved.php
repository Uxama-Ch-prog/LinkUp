<?php
// app/Events/ReactionRemoved.php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReactionRemoved implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $messageId,
        public int $userId,
        public string $emoji
    ) {
    }

public function broadcastOn(): array
{
    $message = \App\Models\Message::find($this->messageId);
    return [
        new PrivateChannel('conversation.' . ($message ? $message->conversation_id : 0)),
    ];
}

public function broadcastAs(): string
{
    return 'ReactionRemoved';
}
    public function broadcastWith(): array
    {
        return [
            'message_id' => $this->messageId,
            'user_id' => $this->userId,
            'emoji' => $this->emoji,
        ];
    }

private function getConversationId()
{
    // Load message with conversation
    $message = \App\Models\Message::with('conversation')->find($this->messageId);
    return $message ? $message->conversation_id : null;
}
}