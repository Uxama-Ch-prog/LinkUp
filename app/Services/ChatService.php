<?php

namespace App\Services;

use App\Events\ConversationCreated;
use App\Events\ConversationRestored;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ChatService
{
    public function getUserConversations(User $user)
    {
        return $user->activeConversations()
            ->with(['participants', 'latestMessage.user'])
            ->orderBy('last_message_at', 'desc')
            ->get()
            ->map(function ($conversation) use ($user) {
                // Add unread messages count
                $conversation->unread_messages_count = $this->getUnreadCount($conversation->id, $user->id);

                return $conversation;
            });
    }

    public function sendMessage($conversationId, $userId, $body, $type = 'text', $attachments = null)
    {
        \Log::info('ChatService sending message:', [
            'conversation_id' => $conversationId,
            'user_id' => $userId,
            'body' => $body,
            'type' => $type,
            'attachments_count' => $attachments ? count($attachments) : 0,
        ]);

        $attachmentPaths = [];

        if ($attachments) {
            foreach ($attachments as $attachment) {
                \Log::info('Processing attachment:', [
                    'original_name' => $attachment->getClientOriginalName(),
                    'size' => $attachment->getSize(),
                    'mime_type' => $attachment->getMimeType(),
                ]);

                try {
                    // Store in conversations/{id}/attachments folder
                    $path = $attachment->store("conversations/{$conversationId}/attachments", 'public');
                    $attachmentPaths[] = [
                        'name' => $attachment->getClientOriginalName(),
                        'path' => $path,
                        'mime_type' => $attachment->getMimeType(),
                        'size' => $attachment->getSize(),
                    ];
                    \Log::info('Attachment stored successfully:', ['path' => $path]);
                } catch (\Exception $e) {
                    \Log::error('Error storing attachment:', [
                        'error' => $e->getMessage(),
                        'file' => $attachment->getClientOriginalName(),
                    ]);
                    throw $e;
                }
            }
        }

        $messageData = [
            'conversation_id' => $conversationId,
            'user_id' => $userId,
            'body' => $body,
            'type' => $type,
        ];

        if (! empty($attachmentPaths)) {
            // ENCODE attachments as JSON string before saving
            $messageData['attachments'] = json_encode($attachmentPaths);
        }

        \Log::info('Creating message with data:', $messageData);

        $message = Message::create($messageData);

        return $message->load('user');
    }

    public function markConversationAsRead($conversationId, $userId)
    {
        // Update the last_read_at timestamp in the participants pivot table
        DB::table('participants')
            ->where('conversation_id', $conversationId)
            ->where('user_id', $userId)
            ->update(['last_read_at' => now()]);

        // Get unread messages for this conversation and user
        $unreadMessages = Message::where('conversation_id', $conversationId)
            ->where('user_id', '!=', $userId)
            ->whereNull('read_at')
            ->get();

        // Mark them as read
        foreach ($unreadMessages as $message) {
            $message->update(['read_at' => now()]);
        }
    }

    public function getUnreadCount($conversationId, $userId)
    {
        // Get the last time user read messages in this conversation
        $lastRead = DB::table('participants')
            ->where('conversation_id', $conversationId)
            ->where('user_id', $userId)
            ->value('last_read_at');

        if ($lastRead) {
            return Message::where('conversation_id', $conversationId)
                ->where('user_id', '!=', $userId)
                ->where('created_at', '>', $lastRead)
                ->count();
        }

        // If never read, count all messages from others
        return Message::where('conversation_id', $conversationId)
            ->where('user_id', '!=', $userId)
            ->count();
    }

    public function createConversation(User $user, array $userIds, ?string $name = null, bool $isGroup = false)
    {
        return DB::transaction(function () use ($user, $userIds, $name, $isGroup) {
            // For non-group chats (1-on-1), check if conversation already exists
            if (! $isGroup && count($userIds) === 1) {
                $otherUserId = $userIds[0];

                // Check if there's an existing conversation between these two users
                $existingConversation = $this->findExistingConversation($user->id, $otherUserId);

                if ($existingConversation) {
                    // Check if this conversation was deleted by the current user
                    if ($existingConversation->isDeletedForUser($user->id)) {
                        // Restore the conversation for this user
                        $existingConversation->restoreForUser($user->id);

                        // Load the conversation with fresh data
                        $existingConversation->load(['participants', 'latestMessage']);

                        // Broadcast restoration event to ALL participants
                        broadcast(new ConversationRestored($existingConversation));

                        return $existingConversation;
                    }

                    // Conversation exists and is not deleted, return it
                    return $existingConversation->load(['participants', 'latestMessage']);
                }
            }

            // CREATE NEW CONVERSATION - This is where you add the code
            $conversation = Conversation::create([
                'name' => $name,
                'is_group' => $isGroup,
                'created_by' => $user->id,
                'last_message_at' => now(),
            ]);

            // Add participants
            $participantIds = array_merge([$user->id], $userIds);
            $conversation->participants()->attach($participantIds);

            // Load relationships
            $conversation->load(['participants', 'latestMessage']);

            // Broadcast NEW conversation event
            broadcast(new ConversationCreated($conversation));

            return $conversation;
        });
    }

    /**
     * Find existing conversation between two users
     */
    private function findExistingConversation($userId1, $userId2)
    {
        return Conversation::where('is_group', false)
            ->whereHas('participants', function ($q) use ($userId1) {
                $q->where('user_id', $userId1);
            })
            ->whereHas('participants', function ($q) use ($userId2) {
                $q->where('user_id', $userId2);
            })
            ->whereDoesntHave('participants', function ($q) use ($userId1, $userId2) {
                $q->whereNotIn('user_id', [$userId1, $userId2]);
            })
            ->first();
    }

    /**
     * Handle new message - restore conversation if it was deleted
     */
    public function handleNewMessage($message, $conversationId, $senderId)
    {
        $conversation = Conversation::find($conversationId);

        if (! $conversation) {
            return;
        }

        // For each participant (except sender), check if conversation was deleted
        foreach ($conversation->participants as $participant) {
            if ($participant->id != $senderId && $conversation->isDeletedForUser($participant->id)) {
                // Restore the conversation for this participant
                $conversation->restoreForUser($participant->id);

                // Broadcast restoration to ALL participants
                broadcast(new ConversationRestored($conversation));

                // Break after first restoration to avoid multiple broadcasts
                break;
            }
        }
    }
}
