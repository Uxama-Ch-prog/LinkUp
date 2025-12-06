<?php
// app/Events/ReactionAdded.php

namespace App\Events;

use App\Models\Reaction;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReactionAdded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Reaction $reaction)
    {
    }

public function broadcastOn(): array
{
    return [
        new PrivateChannel('conversation.' . $this->reaction->message->conversation_id),
    ];
}
    public function broadcastAs(): string
    {
        return 'ReactionAdded';
    }

    public function broadcastWith(): array
    {
        return [
            'reaction' => $this->reaction->load(['user', 'message']),
            'message_id' => $this->reaction->message_id,
        ];
    }
}