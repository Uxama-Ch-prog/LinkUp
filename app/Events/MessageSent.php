<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct($message)
    {
        $this->message = $message->load('user', 'conversation.participants');
    }

    public function broadcastOn()
    {
        $channels = [];
        
        // Broadcast to conversation channel (for real-time chat updates)
        $channels[] = new PrivateChannel('conversation.' . $this->message->conversation_id);
        
        // Broadcast to each participant's private channel (for sidebar updates)
        foreach ($this->message->conversation->participants as $participant) {
            if ($participant->id != $this->message->user_id) {
                $channels[] = new PrivateChannel('user.' . $participant->id);
            }
        }
        
        return $channels;
    }

    public function broadcastWith()
    {
        return [
            'message' => $this->message,
            // Add conversation data for sidebar updates
            'conversation' => [
                'id' => $this->message->conversation_id,
                'name' => $this->message->conversation->name,
                'last_message_at' => $this->message->created_at,
            ]
        ];
    }

    public function broadcastAs()
    {
        return 'MessageSent';
    }
}