<?php

namespace App\Jobs;

use App\Models\LearningSession;
use App\Models\MessageLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CleanupExpiredSessions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        LearningSession::where('expires_at', '<', now())->delete();

        // Keep message logs for 30 days for analytics
        MessageLog::where('sent_at', '<', now()->subDays(30))->delete();
    }
}
