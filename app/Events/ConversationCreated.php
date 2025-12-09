<?php

namespace App\Events;

use App\Models\Conversation;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ConversationCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $conversation;

    public function __construct(Conversation $conversation)
    {
        $this->conversation = $conversation;
        
        // Add debug logging
        Log::info('ConversationCreated event instantiated', [
            'conversation_id' => $conversation->id,
            'participant_ids' => $conversation->participants->pluck('id')->toArray(),
            'is_group' => $conversation->is_group
        ]);
    }

    public function broadcastOn()
    {
        $channels = [];
        
        foreach ($this->conversation->participants as $participant) {
            // Broadcast to each participant's private channel
            $channels[] = new PrivateChannel('user.' . $participant->id);
            
            Log::info('Broadcasting ConversationCreated to user channel', [
                'user_id' => $participant->id,
                'conversation_id' => $this->conversation->id
            ]);
        }
        
        return $channels;
    }

    public function broadcastAs()
    {
        return 'ConversationCreated';
    }

    public function broadcastWith()
    {
        return [
            'conversation' => $this->conversation->load(['participants', 'latestMessage']),
            'timestamp' => now()->toISOString()
        ];
    }
}