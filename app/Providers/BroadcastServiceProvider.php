<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Broadcast::routes(['middleware' => ['auth:sanctum']]);

        // Channel authorization
        Broadcast::channel('user.{id}', function ($user, $userId) {
            return (int) $user->id === (int) $userId;
        });

        Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
            // Check if user is a participant in the conversation
            return \App\Models\Participant::where('conversation_id', $conversationId)
                ->where('user_id', $user->id)
                ->exists();
        });
    }
}
