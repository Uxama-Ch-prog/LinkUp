<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class ProcessPendingWebRTCEvents implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle($event)
    {
        // This would be called when WebRTC events need to be processed
        // You can add additional logging or processing here
    }
}