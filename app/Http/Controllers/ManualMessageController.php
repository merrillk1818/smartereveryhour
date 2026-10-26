<?php

namespace App\Http\Controllers;

use App\Models\Curriculum;
use App\Models\LearningSession;
use App\Models\MessageLog;
use App\Models\Topic;
use App\Services\GeminiService;
use App\Services\PushoverService;
use App\Services\VonageService;
use App\Services\WebPushService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ManualMessageController extends Controller
{
    public function send(Request $request, GeminiService $gemini, WebPushService $push, PushoverService $pushover, VonageService $vonage): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'curriculum_id' => 'required|integer',
            'topic_id'      => 'nullable|integer',
        ]);

        $curriculum = Curriculum::where('id', $request->curriculum_id)
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->firstOrFail();

        if ($request->topic_id) {
            $topic = Topic::where('id', $request->topic_id)
                ->where('curriculum_id', $curriculum->id)
                ->firstOrFail();
        } else {
            $topic = $curriculum->topics()->inRandomOrder()->first();
        }

        if (!$topic) {
            return response()->json(['error' => 'This curriculum has no topics yet.'], 422);
        }

        $hasPush     = $user->pushSubscriptions()->exists();
        $hasPushover = (bool) $user->pushover_user_key;
        $hasSms      = (bool) $user->sms_phone;

        if (!$hasPush && !$hasPushover && !$hasSms) {
            return response()->json(['error' => 'No notification channels configured. Enable push notifications, connect Pushover, or add your mobile number for SMS.'], 422);
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

            return response()->json([
                'topic'         => $topic->title,
                'curriculum'    => $curriculum->name,
                'message'       => $message,
                'url'           => $learnUrl,
                'sent_push'     => $sentPush,
                'sent_pushover' => $sentPushover,
                'sent_sms'      => $sentSms,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
