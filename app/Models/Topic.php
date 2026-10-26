<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Topic extends Model
{
    const SKILL_LEVELS = ['novice', 'intermediate', 'advanced', 'expert'];

    protected $fillable = [
        'curriculum_id',
        'title',
        'description',
        'keywords',
        'skill_level',
        'sort_order',
    ];

    public function curriculum()
    {
        return $this->belongsTo(Curriculum::class);
    }

    public function messageLogs()
    {
        return $this->hasMany(MessageLog::class);
    }

    public function learningSessions()
    {
        return $this->hasMany(LearningSession::class);
    }
}
