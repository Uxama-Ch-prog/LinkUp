<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VideoCall extends Model
{
    use HasFactory;

    protected $fillable = [
        'call_id',
        'conversation_id',
        'caller_id',
        'receiver_id',
        'status',
        'started_at',
        'ended_at',
        'duration',
        'call_metadata',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'call_metadata' => 'array',
    ];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function caller()
    {
        return $this->belongsTo(User::class, 'caller_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function participants()
    {
        return $this->belongsToMany(User::class, 'video_call_participants')
            ->withTimestamps();
    }
}
