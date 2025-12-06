<?php

use App\Models\Conversation;
use Illuminate\Support\Facades\Broadcast;

// Private user channel for video calls
Broadcast::channel('user.{id}', function ($user, $id) {
    \Log::info('Authenticating user channel', [
        'authenticated_user_id' => $user->id,
        'requested_channel_id' => $id,
        'is_authorized' => (int) $user->id === (int) $id,
    ]);

    $isAuthorized = (int) $user->id === (int) $id;

    if (! $isAuthorized) {
        \Log::warning('User channel authentication FAILED', [
            'user_id' => $user->id,
            'channel_id' => $id,
        ]);
    }

    return $isAuthorized;
});

// Private conversation channel
Broadcast::channel('chat.{conversationId}', function ($user, $conversationId) {
    \Log::info('Authenticating conversation channel', [
        'user_id' => $user->id,
        'conversation_id' => $conversationId,
    ]);

    $isParticipant = Conversation::where('id', $conversationId)
        ->whereHas('participants', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
        ->exists();

    \Log::info('Conversation auth result', [
        'is_participant' => $isParticipant,
        'user_id' => $user->id,
        'conversation_id' => $conversationId,
    ]);

    return $isParticipant;
});
