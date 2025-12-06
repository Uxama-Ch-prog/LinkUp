<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'conversation_id',
        'user_id',
        'body',
        'type',
        'attachments',
        'read_at',
        'edited_at',
    ];

protected $casts = [
    'attachments' => 'array',
    'read_at' => 'datetime',
    'created_at' => 'datetime',
    'updated_at' => 'datetime',
    'deleted_at' => 'datetime',
];
protected $appends = ['attachments_urls','reactions_summary','is_editable'];


    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isRead()
    {
        return !is_null($this->read_at);
    }

    public function markAsRead()
    {
        if (is_null($this->read_at)) {
            $this->update(['read_at' => now()]);
        }
    }
// app/Models/Message.php - Update the getAttachmentsUrlsAttribute method:

public function getAttachmentsUrlsAttribute()
{
    if (!$this->attachments) {
        return [];
    }
    
    // First, get the attachments as an array
    $attachments = $this->attachments;
    
    // If it's a string (JSON), decode it
    if (is_string($attachments)) {
        $decoded = json_decode($attachments, true);
        
        // If JSON decode fails, return empty array
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            return [];
        }
        
        $attachments = $decoded;
    }
    
    // Now $attachments should be an array, but let's double-check
    if (!is_array($attachments)) {
        return [];
    }
    
    // Process each attachment
    $result = [];
    foreach ($attachments as $attachment) {
        // Skip if not an array or doesn't have the required data
        if (!is_array($attachment) || !isset($attachment['path'])) {
            continue;
        }
        
        $result[] = [
            'name' => $attachment['name'] ?? basename($attachment['path']),
            'url' => asset('storage/' . $attachment['path']),
            'path' => $attachment['path'],
            'mime_type' => $attachment['mime_type'] ?? 'application/octet-stream',
            'size' => $attachment['size'] ?? 0,
        ];
    }
    
    return $result;
}
public function reactions()
{
    return $this->hasMany(Reaction::class);
}

// Update the loaded relationships
public function getReactionsSummaryAttribute()
{
    $reactions = $this->reactions->groupBy('emoji')->map(function ($group) {
        return [
            'emoji' => $group->first()->emoji,
            'count' => $group->count(),
            'users' => $group->pluck('user_id')->toArray(),
        ];
    })->values();

    return $reactions;
}
    public function getIsEditableAttribute()
    {
        return $this->created_at->diffInMinutes(now()) <= 15 && $this->user_id === auth()->id();
    }
        public function getIsDeletableAttribute()
    {
        // Allow deletion within 1 hour or always for own messages
        return $this->user_id === auth()->id();
    }
}