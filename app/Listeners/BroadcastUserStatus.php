<?php

namespace App\Listeners;

use App\Events\UserStatusUpdated;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Contracts\Queue\ShouldQueue;

class BroadcastUserStatus implements ShouldQueue
{
    public function handle(Login|Logout $event): void
    {
        $isOnline = $event instanceof Login;

        $event->user->update([
            'is_online' => $isOnline,
            'last_seen_at' => now(),
        ]);

        broadcast(new UserStatusUpdated(
            $event->user->id,
            $isOnline,
            now()->toISOString()
        ));
    }
}
