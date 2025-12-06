<?php

namespace App\Events;

use App\Models\VideoCall;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CallEnded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $call;

    public function __construct(VideoCall $call)
    {
        $this->call = $call;
    }

    public function broadcastOn()
    {
        // Broadcast to both caller and receiver
        return [
            new PrivateChannel('user.'.$this->call->caller_id),
            new PrivateChannel('user.'.$this->call->receiver_id),
        ];
    }

    public function broadcastAs()
    {
        return 'CallEnded';
    }

    public function broadcastWith()
    {
        return [
            'call' => $this->call,
        ];
    }
}
