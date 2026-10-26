<?php

namespace App\Http\Controllers;

use App\Models\LearningSession;
use App\Models\Topic;
use App\Services\GeminiService;
use App\Services\SlackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SlackController extends Controller
{
    public function test(Request $request, SlackService $slack, GeminiService $gemini): JsonResponse
    {
        $user = $request->user();

        if (!$user->slack_webhook_url) {
            return response()->json(['error' => 'Add a Slack webhook URL in Notification Settings first.'], 422);
        }

        $topic = Topic::whereHas('curriculum', fn ($q) => $q->where('user_id', $user->id)->where('is_active', true))
            ->inRandomOrder()
            ->first();

        try {
            if ($topic) {
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
            } else {
                $message = 'Add topics to your curriculum to get AI-generated lessons like this delivered to Slack!';
                $learnUrl = config('app.url') . '/curriculum';
            }

            $sent = $slack->sendTest($user, $message, $learnUrl);

            if (!$sent) {
                return response()->json(['error' => 'Webhook failed — check your Slack webhook URL is correct and active.'], 500);
            }

            return response()->json([
                'topic'   => $topic?->title,
                'message' => $message,
                'url'     => $learnUrl,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
