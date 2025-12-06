<?php

namespace App\Http\Controllers;

use App\Events\ConversationDeleted;
use App\Events\UserTyping;
use App\Http\Requests\CreateConversationRequest;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\ChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    public function __construct(protected ChatService $chatService) {}

    public function getConversations(Request $request)
    {
        try {
            $user = $request->user();
            $conversations = $this->chatService->getUserConversations($user);

            return response()->json($conversations);
        } catch (\Exception $e) {
            \Log::error('Error fetching conversations: '.$e->getMessage());

            return response()->json([
                'message' => 'Failed to fetch conversations',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // app/Http/Controllers/ChatController.php
    public function createConversation(CreateConversationRequest $request)
    {
        $user = $request->user();

        \Log::info('Creating conversation with data:', [
            'user_ids' => $request->user_ids,
            'name' => $request->name,
            'is_group' => $request->is_group,
            'current_user' => $user->id,
        ]);

        $conversation = $this->chatService->createConversation(
            $user,
            $request->user_ids,
            $request->name,
            $request->is_group ?? false
        );

        return response()->json($conversation->load(['participants', 'latestMessage']), 201);
    }

    public function getConversation($id)
    {
        // Use the new scope that includes deleted conversations
        $conversation = Conversation::findWithDeleted($id, Auth::id())
            ->first();

        if (! $conversation) {
            \Log::error('Conversation not found', [
                'conversation_id' => $id,
                'user_id' => Auth::id(),
            ]);

            return response()->json(['error' => 'Conversation not found'], 404);
        }

        return response()->json($conversation);
    }

    // app/Http/Controllers/ChatController.php
    public function getUsers(Request $request)
    {
        try {
            // Check which columns exist
            $user = new User;
            $connection = $user->getConnection();
            $schema = $connection->getSchemaBuilder();

            $columns = ['id', 'name', 'email'];

            // Only add these columns if they exist in the table
            if ($schema->hasColumn('users', 'avatar')) {
                $columns[] = 'avatar';
            }
            if ($schema->hasColumn('users', 'is_online')) {
                $columns[] = 'is_online';
            }
            if ($schema->hasColumn('users', 'last_seen_at')) {
                $columns[] = 'last_seen_at';
            }

            $users = User::where('id', '!=', $request->user()->id)
                ->select($columns)
                ->get();

            return response()->json($users);
        } catch (\Exception $e) {
            \Log::error('Error fetching users: '.$e->getMessage());

            // Fallback: return basic user info without the extra columns
            $users = User::where('id', '!=', $request->user()->id)
                ->select('id', 'name', 'email')
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'avatar' => null,
                        'is_online' => false,
                        'last_seen_at' => null,
                    ];
                });

            return response()->json($users);
        }
    }

    public function markAsRead($conversationId)
    {
        try {
            $this->chatService->markConversationAsRead($conversationId, Auth::id());

            return response()->json(['message' => 'Conversation marked as read']);
        } catch (\Exception $e) {
            \Log::error('Error marking conversation as read: '.$e->getMessage());

            return response()->json([
                'message' => 'Failed to mark conversation as read',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // app/Http/Controllers/ChatController.php - Update typing method
    public function typing(Request $request, $conversationId)
    {
        $request->validate([
            'is_typing' => 'required|boolean',
        ]);

        // Verify user is part of the conversation
        $conversation = Conversation::forUser($request->user()->id)
            ->findOrFail($conversationId);

        broadcast(new UserTyping(
            $conversationId,
            $request->user()->id,
            $request->is_typing
        ));

        return response()->json(['message' => 'Typing status updated']);
    }

    public function searchMessages(Request $request)
    {
        $validated = $request->validate([
            'query' => 'required|string|min:1|max:100',
            'conversation_id' => 'nullable|exists:conversations,id',
        ]);

        $searchQuery = $validated['query'];
        $conversationId = $validated['conversation_id'] ?? null;

        $messages = Message::with(['user', 'conversation'])
            ->whereHas('conversation.participants', function ($q) use ($request) {
                $q->where('user_id', $request->user()->id);
            })
            ->when($conversationId, function ($q) use ($conversationId) {
                $q->where('conversation_id', $conversationId);
            })
            ->where('body', 'like', '%'.$searchQuery.'%')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json($messages);
    }

    public function searchConversations(Request $request)
    {
        $validated = $request->validate([
            'query' => 'required|string|min:1|max:100',
        ]);

        $searchQuery = $validated['query'];

        $conversations = Conversation::with(['participants', 'latestMessage'])
            ->whereHas('participants', function ($q) use ($request) {
                $q->where('user_id', $request->user()->id);
            })
            ->where(function ($q) use ($searchQuery) {
                $q->where('name', 'like', '%'.$searchQuery.'%')
                    ->orWhereHas('participants', function ($q2) use ($searchQuery) {
                        $q2->where('name', 'like', '%'.$searchQuery.'%')
                            ->where('user_id', '!=', auth()->id());
                    })
                    ->orWhereHas('messages', function ($q2) use ($searchQuery) {
                        $q2->where('body', 'like', '%'.$searchQuery.'%');
                    });
            })
            ->orderBy('last_message_at', 'desc')
            ->get();

        return response()->json($conversations);
    }

    public function deleteConversation($id)
    {
        try {
            $user = Auth::user();
            $conversation = Conversation::findOrFail($id);

            // Verify user is a participant
            if (! $conversation->participants->contains($user->id)) {
                return response()->json([
                    'message' => 'You are not a participant of this conversation',
                ], 403);
            }

            // Mark as deleted for this user
            $conversation->markAsDeletedForUser($user->id);

            // Broadcast deletion event (only to the user who deleted it)
            broadcast(new ConversationDeleted($conversation->id, $user->id));

            return response()->json([
                'message' => 'Conversation deleted successfully',
            ]);

        } catch (\Exception $e) {
            \Log::error('Error deleting conversation: '.$e->getMessage());

            return response()->json([
                'message' => 'Failed to delete conversation',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Restore deleted conversation
     */
    public function restoreConversation($id)
    {
        try {
            $user = Auth::user();
            $conversation = Conversation::findOrFail($id);

            // Verify user is a participant
            if (! $conversation->participants->contains($user->id)) {
                return response()->json([
                    'message' => 'You are not a participant of this conversation',
                ], 403);
            }

            // Restore conversation for this user
            $conversation->restoreForUser($user->id);

            // Broadcast restoration event
            broadcast(new ConversationRestored($conversation));

            return response()->json([
                'message' => 'Conversation restored successfully',
                'conversation' => $conversation->load(['participants', 'latestMessage']),
            ]);

        } catch (\Exception $e) {
            \Log::error('Error restoring conversation: '.$e->getMessage());

            return response()->json([
                'message' => 'Failed to restore conversation',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get deleted conversations (for testing/management)
     */
    public function getDeletedConversations(Request $request)
    {
        try {
            $user = $request->user();

            $deletedConversations = $user->deletedConversations()
                ->with(['participants', 'latestMessage'])
                ->get();

            return response()->json($deletedConversations);

        } catch (\Exception $e) {
            \Log::error('Error fetching deleted conversations: '.$e->getMessage());

            return response()->json([
                'message' => 'Failed to fetch deleted conversations',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
