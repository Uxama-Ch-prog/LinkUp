<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ReactionController;
use App\Http\Controllers\VideoCallController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Broadcast routes with proper middleware
Route::post('/broadcasting/auth', function (Request $request) {
    \Log::info('Broadcast auth attempt', [
        'user' => auth()->user()?->id,
        'channel_name' => $request->channel_name,
        'socket_id' => $request->socket_id,
    ]);

    if (! auth()->check()) {
        \Log::warning('Broadcast auth failed: User not authenticated');

        return response()->json(['message' => 'Unauthenticated'], 401);
    }

    // Let Laravel handle the channel authorization via BroadcastServiceProvider
    return Broadcast::auth($request);
})->middleware(['auth:sanctum']);

Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Video Call routes
    Route::post('/video-call/initiate', [VideoCallController::class, 'initiate']);
    Route::post('/video-call/{callId}/accept', [VideoCallController::class, 'accept']);
    Route::post('/video-call/{callId}/reject', [VideoCallController::class, 'reject']);
    Route::post('/video-call/{callId}/end', [VideoCallController::class, 'end']);
    Route::post('/video-call/{callId}/signal', [VideoCallController::class, 'signal']);
    Route::get('/video-call/{conversationId}/active', [VideoCallController::class, 'getActiveCall']);
    Route::get('/video-call/{conversationId}/history', [VideoCallController::class, 'history']);

    // Chat routes
    Route::prefix('chat')->group(function () {
        // Conversations
        Route::get('/conversations', [ChatController::class, 'getConversations']);
        Route::post('/conversations', [ChatController::class, 'createConversation']);
        Route::get('/conversations/{id}', [ChatController::class, 'getConversation']);
        Route::post('/conversations/{conversationId}/read', [ChatController::class, 'markAsRead']);
        Route::post('/conversations/{conversationId}/typing', [ChatController::class, 'typing']);

        // Users
        Route::get('/users', [ChatController::class, 'getUsers']);

        // Messages - ALL message routes should be here
        Route::get('/conversations/{conversationId}/messages', [MessageController::class, 'index']);
        Route::post('/messages', [MessageController::class, 'store']);
        Route::put('/messages/{id}', [MessageController::class, 'update']);
        Route::delete('/messages/{id}', [MessageController::class, 'destroy']); // ADD THIS LINE
        Route::post('/messages/{messageId}/read', [MessageController::class, 'markAsRead']);

        // Reaction routes - also under chat prefix
        Route::post('/messages/{messageId}/reactions', [ReactionController::class, 'toggleReaction']);
        Route::get('/messages/{messageId}/reactions', [ReactionController::class, 'getMessageReactions']);

        // Conversation management
        Route::delete('/conversations/{conversation}', [ChatController::class, 'deleteConversation']);
        Route::post('/conversations/{conversation}/restore', [ChatController::class, 'restoreConversation']);
        Route::get('/deleted-conversations', [ChatController::class, 'getDeletedConversations']);

        // Search routes
        Route::get('/search/messages', [ChatController::class, 'searchMessages']);
        Route::get('/search/conversations', [ChatController::class, 'searchConversations']);
    });
});
