<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LearningSession extends Model
{
    protected $fillable = [
        'session_token',
        'user_id',
        'topic_id',
        'initial_message',
        'messages',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'messages'   => 'array',
            'expires_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function topic()
    {
        return $this->belongsTo(Topic::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function appendMessage(string $role, string $content): void
    {
        $messages = $this->messages ?? [];
        $messages[] = ['role' => $role, 'content' => $content];
        $this->update(['messages' => $messages]);
    }
}
