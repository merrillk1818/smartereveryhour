<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationSchedule extends Model
{
    protected $fillable = [
        'user_id',
        'start_time',
        'end_time',
        'frequency_minutes',
        'timezone',
        'days_of_week',
        'is_active',
        'pushover_enabled',
        'sms_enabled',
    ];

    protected function casts(): array
    {
        return [
            'days_of_week'     => 'array',
            'is_active'        => 'boolean',
            'pushover_enabled' => 'boolean',
            'sms_enabled'      => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isWithinWindow(): bool
    {
        $now = now()->setTimezone($this->timezone);
        $start = \Carbon\Carbon::parse($this->start_time, $this->timezone);
        $end   = \Carbon\Carbon::parse($this->end_time, $this->timezone);

        $startToday = $now->copy()->setTimeFrom($start);
        $endToday   = $now->copy()->setTimeFrom($end);

        if (!in_array($now->dayOfWeek, $this->days_of_week ?? [0,1,2,3,4,5,6])) {
            return false;
        }

        return $now->between($startToday, $endToday);
    }
}
