<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Http\Requests\StoreMessageRequest;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\ChatService;
use App\Events\MessageDeleted;
use App\Events\MessageUpdated;
use App\Events\MessageRead;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{
    public function __construct(protected ChatService $chatService)
    {
    }

    public function index($conversationId, Request $request)
    {
        try {
            $user = $request->user();
            
            // Verify user is part of conversation
            $conversation = Conversation::forUser($user->id)
                ->findOrFail($conversationId);
            
            // Get pagination parameters
            $page = $request->input('page', 1);
            $perPage = $request->input('per_page', 50);
            
            // Calculate offset for manual pagination
            $offset = ($page - 1) * $perPage;
            
            // Get messages with eager loading - CHANGED ORDER TO 'asc'
            $messages = Message::where('conversation_id', $conversationId)
                ->with(['user', 'reactions.user'])
                ->orderBy('created_at', 'asc') // CHANGED FROM 'desc' TO 'asc'
                ->skip($offset)
                ->take($perPage)
                ->get()
                ->map(function ($message) use ($user) {
                    // Add reactions summary
                    $message->reactions_summary = $message->reactions
                        ->groupBy('emoji')
                        ->map(function ($reactions, $emoji) {
                            return [
                                'emoji' => $emoji,
                                'count' => $reactions->count(),
                                'user_ids' => $reactions->pluck('user_id')->toArray()
                            ];
                        })
                        ->values()
                        ->toArray();
                    
                    // Format attachments URLs
                    if ($message->attachments) {
                        $message->attachments_urls = collect(json_decode($message->attachments, true))
                            ->map(function ($attachment) {
                                return [
                                    'name' => $attachment['name'],
                                    'url' => Storage::url($attachment['path']),
                                    'mime_type' => $attachment['mime_type'],
                                    'size' => $attachment['size']
                                ];
                            })
                            ->toArray();
                    } else {
                        $message->attachments_urls = [];
                    }
                    
                    return $message;
                });
            
            return response()->json($messages);
        } catch (\Exception $e) {
            \Log::error('Error fetching messages: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to fetch messages',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            \Log::info('Sending message with data:', $request->all());
            
            $validated = $request->validate([
                'conversation_id' => 'required|exists:conversations,id',
                'body' => 'nullable|string|max:5000',
                'attachments' => 'nullable|array',
                'attachments.*' => 'file|max:10240', // 10MB max per file
            ]);

            $user = $request->user();
            
            // Verify user is a participant in the conversation
            $isParticipant = DB::table('participants')
                ->where('conversation_id', $validated['conversation_id'])
                ->where('user_id', $user->id)
                ->exists();
            
            if (!$isParticipant) {
                \Log::warning('User not in conversation', [
                    'user_id' => $user->id,
                    'conversation_id' => $validated['conversation_id']
                ]);
                return response()->json(['message' => 'You are not a participant in this conversation'], 403);
            }
            
            \Log::info('Creating message via ChatService');
            
            // Use ChatService to send message
        $message = $this->chatService->sendMessage(
            $validated['conversation_id'],
            $user->id,
            $validated['body'] ?? '',
            'text',
            $request->hasFile('attachments') ? $request->file('attachments') : null
        );
            
            // Update conversation last message time
            Conversation::where('id', $validated['conversation_id'])
            ->update(['last_message_at' => now()]);
            
            // Load the message with user relationship
            $message->load('user');
                  // Handle conversation restoration if needed
        $this->chatService->handleNewMessage($message, $validated['conversation_id'], $user->id);
        
            
            // Broadcast the message to all participants
            broadcast(new MessageSent($message))->toOthers();
            
            return response()->json($message);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Message validation failed:', ['errors' => $e->errors()]);
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error sending message: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to send message',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        \Log::info('Deleting message:', ['message_id' => $id, 'user_id' => auth()->id()]);
        
        $message = Message::where('id', $id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$message) {
            \Log::warning('Message not found or user not authorized', [
                'message_id' => $id,
                'user_id' => auth()->id()
            ]);
            return response()->json([
                'error' => 'Message not found or you are not authorized to delete it'
            ], 404);
        }

        $conversationId = $message->conversation_id;
        
        // Store message data before deletion for broadcasting
        $messageData = [
            'id' => $message->id,
            'conversation_id' => $message->conversation_id,
            'user_id' => $message->user_id,
            'deleted_at' => now()->toISOString()
        ];
        
        // Soft delete the message
        $message->delete();

        // Broadcast deletion event to all participants
        broadcast(new MessageDeleted($messageData))->toOthers();

        return response()->json([
            'success' => true,
            'message' => 'Message deleted successfully'
        ]);
    }

 // In MessageController.php - Ensure markAsRead broadcasts
public function markAsRead($messageId)
{
    try {
        $message = Message::findOrFail($messageId);
        $user = Auth::user();
        
        // Mark the specific message as read
        if (!$message->read_at) {
            $message->update(['read_at' => now()]);
            
            // Broadcast read receipt
            broadcast(new MessageRead($message->conversation_id, $user->id, $messageId));
        }
        
        return response()->json(['message' => 'Message marked as read']);
    } catch (\Exception $e) {
        \Log::error('Error marking message as read: ' . $e->getMessage());
        return response()->json([
            'message' => 'Failed to mark message as read',
            'error' => $e->getMessage()
        ], 500);
    }
}

    public function update(Request $request, $id)
    {
        $request->validate([
            'body' => 'required|string|max:5000',
        ]);

        $message = Message::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        // Check if message can be edited (within 15 minutes)
        $messageAge = now()->diffInMinutes($message->created_at);
        if ($messageAge > 15) {
            return response()->json([
                'error' => 'Message can only be edited within 15 minutes of sending'
            ], 403);
        }

        $originalBody = $message->body;
        $message->body = $request->body;
        $message->edited_at = now();
        $message->save();

        // Load the updated message with user relationship
        $message->load('user');

        // Broadcast update event to all participants
        broadcast(new MessageUpdated($message, $originalBody))->toOthers();

        return response()->json([
            'message' => $message,
            'original_body' => $originalBody
        ]);
    }
    
}