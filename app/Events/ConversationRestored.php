<?php

namespace App\Events;

use App\Models\Conversation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConversationRestored implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $conversation;

    public function __construct(Conversation $conversation)
    {
        $this->conversation = $conversation->load(['participants', 'latestMessage']);
    }

    public function broadcastOn()
    {
        $channels = [];

        // Send to ALL participants, even those who deleted it
        foreach ($this->conversation->participants as $participant) {
            $channels[] = new PrivateChannel('user.'.$participant->id);
        }

        return $channels;
    }

    public function broadcastAs()
    {
        return 'ConversationRestored';
    }

    public function broadcastWith()
    {
        return [
            'conversation' => $this->conversation,
            'restored_at' => now()->toISOString(),
        ];
    }
}
