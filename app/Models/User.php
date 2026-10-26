<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'timezone',
        'pushover_user_key',
        'sms_phone',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    public function curricula()
    {
        return $this->hasMany(Curriculum::class);
    }

    public function notificationSchedule()
    {
        return $this->hasOne(NotificationSchedule::class);
    }

    public function pushSubscriptions()
    {
        return $this->hasMany(PushSubscription::class);
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
