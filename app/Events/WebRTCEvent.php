<?php

namespace App\Events;

use App\Models\VideoCall;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WebRTCEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $call;

    public $type;

    public $data;

    public $fromUserId;

    public $toUserId;

    public function __construct(VideoCall $call, string $type, array $data, int $fromUserId, int $toUserId)
    {
        $this->call = $call;
        $this->type = $type;
        $this->data = $data;
        $this->fromUserId = $fromUserId;
        $this->toUserId = $toUserId;
    }

    public function broadcastOn()
    {
        // Broadcast to the target user only
        return new PrivateChannel('user.'.$this->toUserId);
    }

    public function broadcastAs()
    {
        return 'WebRTCEvent';
    }

    public function broadcastWith()
    {
        return [
            'call_id' => $this->call->call_id,
            'type' => $this->type,
            'data' => $this->data,
            'from_user_id' => $this->fromUserId,
        ];
    }
}
