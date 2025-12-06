<?php

namespace App\Providers;

use App\Events\MessageSent;
use App\Listeners\BroadcastUserStatus;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Login::class => [
            BroadcastUserStatus::class,
        ],
        Logout::class => [
            BroadcastUserStatus::class,
        ],
        MessageSent::class => [
            // Add any listeners for message sent
        ],
    ];

    public function boot(): void
    {
        //
    }
}
