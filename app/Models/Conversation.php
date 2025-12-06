<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'is_group',
        'created_by',
        'last_message_at',
    ];

    protected $casts = [
        'is_group' => 'boolean',
        'last_message_at' => 'datetime',
    ];

    // Relationship with participants
    public function participants()
    {
        return $this->belongsToMany(User::class, 'participants') // FIX: Changed to 'participants'
            ->withTimestamps()
            ->withPivot('last_read_at');
    }

    // Relationship with messages
    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    // Relationship with latest message
    public function latestMessage()
    {
        return $this->hasOne(Message::class)->latest();
    }

    // Relationship with deleted conversations
    public function deletedByUsers()
    {
        return $this->belongsToMany(User::class, 'deleted_conversations')
            ->withTimestamps()
            ->withPivot('deleted_at');
    }

    // Scope to get conversations for a specific user (excluding deleted ones)
    public function scopeForUser(Builder $query, $userId)
    {
        return $query->whereHas('participants', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })->whereDoesntHave('deletedByUsers', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        });
    }

    // Check if conversation is deleted for a user
    public function isDeletedForUser($userId)
    {
        return $this->deletedByUsers()->where('user_id', $userId)->exists();
    }

    // Mark conversation as deleted for a user
    public function markAsDeletedForUser($userId)
    {
        $this->deletedByUsers()->syncWithoutDetaching([
            $userId => ['deleted_at' => now()],
        ]);
    }

    // Restore conversation for a user
    public function restoreForUser($userId)
    {
        $this->deletedByUsers()->detach($userId);
    }

    // Get active participants (excluding those who deleted the conversation)
    public function activeParticipants()
    {
        return $this->participants()->whereDoesntHave('deletedConversations', function ($q) {
            $q->where('conversation_id', $this->id);
        });
    }

    public function scopeFindWithDeleted(Builder $query, $id, $userId)
    {
        return $query->with(['participants', 'messages.user'])
            ->where('id', $id)
            ->whereHas('participants', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            });
    }
}
