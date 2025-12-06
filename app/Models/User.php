<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;


class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'is_online',
        'last_seen_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_online' => 'boolean',
            'last_seen_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function conversations()
    {
        return $this->belongsToMany(Conversation::class, 'participants')
                    ->withPivot('last_read_at')
                    ->withTimestamps();
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function isParticipant($conversationId)
    {
        return $this->conversations()->where('conversation_id', $conversationId)->exists();
    }
    public function getAvatarUrlAttribute()
{
    if ($this->avatar) {
        return asset('storage/' . $this->avatar);
    }
    
    // Generate default avatar based on name
    return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&color=7F9CF5&background=EBF4FF';
}
 // Relationship with deleted conversations
    public function deletedConversations()
    {
        return $this->belongsToMany(Conversation::class, 'deleted_conversations')
                    ->withTimestamps()
                    ->withPivot('deleted_at');
    }

    // Get conversations that are not deleted
    public function activeConversations()
    {
        return $this->conversations()
                    ->whereDoesntHave('deletedByUsers', function ($q) {
                        $q->where('user_id', $this->id);
                    })
                    ->withTimestamps()
                    ->withPivot('last_read_at');
    }
}