<?php

use App\Jobs\CleanupExpiredSessions;
use App\Jobs\DispatchLearningMessages;
use Illuminate\Support\Facades\Schedule;

// Check every 5 minutes if any user is due for a learning message
Schedule::job(new DispatchLearningMessages, 'notifications')->everyFiveMinutes();

// Clean up expired sessions daily at 3am UTC
Schedule::job(new CleanupExpiredSessions)->dailyAt('03:00');
