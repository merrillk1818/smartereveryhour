<?php

namespace App\Jobs;

use App\Models\LearningSession;
use App\Models\MessageLog;
use App\Models\NotificationSchedule;
use App\Models\Topic;
use App\Services\GeminiService;
use App\Services\PushoverService;
use App\Services\VonageService;
use App\Services\WebPushService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DispatchLearningMessages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function handle(GeminiService $gemini, WebPushService $push, PushoverService $pushover, VonageService $vonage): void
    {
        $schedules = NotificationSchedule::with(['user.curricula.topics', 'user.pushSubscriptions'])
            ->where('is_active', true)
            ->get();

        foreach ($schedules as $schedule) {
            $this->processUser($schedule, $gemini, $push, $pushover, $vonage);
        }
    }

    private function processUser(NotificationSchedule $schedule, GeminiService $gemini, WebPushService $push, PushoverService $pushover, VonageService $vonage): void
    {
        $user = $schedule->user;

        $hasPush     = $user && $user->pushSubscriptions->isNotEmpty();
        $hasPushover = $user && $user->pushover_user_key && $schedule->pushover_enabled;
        $hasSms      = $user && $user->sms_phone && $schedule->sms_enabled;

        if (!$hasPush && !$hasPushover && !$hasSms) {
            return;
        }

        if (!$schedule->isWithinWindow()) {
            return;
        }

        // Check if enough time has passed since the last message
        $lastLog = MessageLog::where('user_id', $user->id)
            ->orderByDesc('sent_at')
            ->first();

        if ($lastLog && $lastLog->sent_at->diffInMinutes(now()) < $schedule->frequency_minutes) {
            return;
        }

        $topic = $this->pickTopic($user, $schedule->frequency_minutes);
        if (!$topic) {
            return;
        }

        try {
            $message = $gemini->generateNotificationMessage($topic->title, $topic->keywords ?? '', $topic->skill_level ?? 'intermediate');

            $session = LearningSession::create([
                'session_token'   => Str::random(48),
                'user_id'         => $user->id,
                'topic_id'        => $topic->id,
                'initial_message' => $message,
                'messages'        => [],
                'expires_at'      => now()->addHours(24),
            ]);

            $learnUrl = config('app.url') . '/learn/' . $session->session_token;

            $sentPush     = $hasPush     ? $push->sendToUser($user, 'Smarter Every Hour', $message, $learnUrl) : false;
            $sentPushover = $hasPushover ? $pushover->sendToUser($user, $message, $learnUrl) : false;
            $sentSms      = $hasSms      ? $vonage->sendToUser($user->sms_phone, $message, $learnUrl) : false;

            if ($sentPush || $sentPushover || $sentSms) {
                MessageLog::create([
                    'user_id'         => $user->id,
                    'topic_id'        => $topic->id,
                    'sent_at'         => now(),
                    'message_preview' => mb_substr($message, 0, 100),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('DispatchLearningMessages failed for user', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    /**
     * Pick a random topic, preferring those least recently sent.
     */
    private function pickTopic($user, int $frequencyMinutes): ?Topic
    {
        $topics = Topic::whereHas('curriculum', fn ($q) => $q->where('user_id', $user->id)->where('is_active', true))
            ->get();

        if ($topics->isEmpty()) {
            return null;
        }

        // Get recently sent topic IDs
        $recentTopicIds = MessageLog::where('user_id', $user->id)
            ->where('sent_at', '>=', now()->subHours(24))
            ->pluck('topic_id')
            ->toArray();

        // Prefer topics not recently sent
        $fresh = $topics->whereNotIn('id', $recentTopicIds);

        return $fresh->isNotEmpty()
            ? $fresh->random()
            : $topics->random();
    }
}
