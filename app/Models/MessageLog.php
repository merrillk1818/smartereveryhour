<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'topic_id', 'sent_at', 'message_preview'];

    protected function casts(): array
    {
        return ['sent_at' => 'datetime'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function topic()
    {
        return $this->belongsTo(Topic::class);
    }
}
