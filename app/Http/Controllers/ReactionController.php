<?php
// app/Http/Controllers/ReactionController.php

namespace App\Http\Controllers;

use App\Events\ReactionAdded;
use App\Events\ReactionRemoved;
use App\Models\Message;
use App\Models\Reaction;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ReactionController extends Controller
{
    public function toggleReaction(Request $request, $messageId)
    {
        \Log::info('Toggle reaction request:', [
            'message_id' => $messageId,
            'emoji' => $request->emoji,
            'user_id' => Auth::id()
        ]);

        $request->validate([
            'emoji' => 'required|string|max:10',
        ]);

        $message = Message::with('conversation')->findOrFail($messageId);
        
        // Verify user has access to this message's conversation
        if (!$message->conversation->participants->contains(Auth::id())) {
            \Log::warning('User not authorized for reaction', [
                'user_id' => Auth::id(),
                'message_id' => $messageId,
                'conversation_id' => $message->conversation_id
            ]);
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $existingReaction = Reaction::where('message_id', $messageId)
            ->where('user_id', Auth::id())
            ->where('emoji', $request->emoji)
            ->first();

        if ($existingReaction) {
            // Remove reaction
            $existingReaction->delete();
            
            \Log::info('Reaction removed:', [
                'message_id' => $messageId,
                'user_id' => Auth::id(),
                'emoji' => $request->emoji
            ]);
            
            // Broadcast reaction removed
            broadcast(new ReactionRemoved($messageId, Auth::id(), $request->emoji))->toOthers();
            
            return response()->json([
                'action' => 'removed',
                'message' => 'Reaction removed',
                'emoji' => $request->emoji,
                'message_id' => $messageId,
                'user_id' => Auth::id()
            ]);
        } else {
            // Add reaction
            $reaction = Reaction::create([
                'message_id' => $messageId,
                'user_id' => Auth::id(),
                'emoji' => $request->emoji,
            ]);

            // Load the reaction with user data
            $reaction->load('user');
            
            \Log::info('Reaction added:', [
                'reaction_id' => $reaction->id,
                'message_id' => $messageId,
                'user_id' => Auth::id(),
                'emoji' => $request->emoji
            ]);
            
            // Broadcast reaction added
            broadcast(new ReactionAdded($reaction))->toOthers();

            return response()->json([
                'action' => 'added',
                'reaction' => $reaction,
                'message' => 'Reaction added'
            ]);
        }
    }

    public function getMessageReactions($messageId)
    {
        try {
            $message = Message::with('reactions.user')->findOrFail($messageId);
            
            // Group reactions by emoji
            $reactionsSummary = $message->reactions
                ->groupBy('emoji')
                ->map(function ($reactions, $emoji) {
                    return [
                        'emoji' => $emoji,
                        'count' => $reactions->count(),
                        'users' => $reactions->map(function ($reaction) {
                            return [
                                'id' => $reaction->user_id,
                                'name' => $reaction->user->name
                            ];
                        })->toArray()
                    ];
                })
                ->values()
                ->toArray();
            
            return response()->json([
                'reactions' => $reactionsSummary
            ]);
        } catch (\Exception $e) {
            \Log::error('Error getting message reactions: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to get message reactions'
            ], 500);
        }
    }
}