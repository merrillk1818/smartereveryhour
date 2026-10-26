<?php

namespace App\Http\Controllers;

use App\Models\LearningSession;
use App\Models\Topic;
use App\Services\GeminiService;
use App\Services\PushoverService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class PushoverController extends Controller
{
    public function sendPin(Request $request, PushoverService $pushover): JsonResponse
    {
        $request->validate(['user_key' => 'required|string|max:30']);

        $userKey = trim($request->input('user_key'));
        $pin     = random_int(100000, 999999);

        $sent = $pushover->sendPin($userKey, $pin);

        if (!$sent) {
            return response()->json(['error' => 'Could not reach Pushover. Double-check your User Key and try again.'], 422);
        }

        Cache::put("pushover_pin:{$request->user()->id}", [
            'pin' => $pin,
            'key' => $userKey,
        ], now()->addMinutes(10));

        return response()->json(['sent' => true]);
    }

    public function verifyPin(Request $request): JsonResponse
    {
        $request->validate(['pin' => 'required|digits:6']);

        $cached = Cache::get("pushover_pin:{$request->user()->id}");

        if (!$cached || (int) $request->input('pin') !== $cached['pin']) {
            return response()->json(['error' => 'Incorrect PIN. Request a new one and try again.'], 422);
        }

        $request->user()->update(['pushover_user_key' => $cached['key']]);
        Cache::forget("pushover_pin:{$request->user()->id}");

        return response()->json(['verified' => true]);
    }

    public function disconnect(Request $request): JsonResponse
    {
        $request->user()->update(['pushover_user_key' => null]);
        return response()->json(['disconnected' => true]);
    }

    public function test(Request $request, PushoverService $pushover, GeminiService $gemini): JsonResponse
    {
        $user = $request->user();

        if (!$user->pushover_user_key) {
            return response()->json(['error' => 'Connect your Pushover account in Notification Settings first.'], 422);
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
                $message  = 'Add topics to your curriculum to receive AI-generated lessons like this!';
                $learnUrl = config('app.url') . '/curriculum';
            }

            $sent = $pushover->sendToUser($user, $message, $learnUrl);

            if (!$sent) {
                return response()->json(['error' => 'Pushover delivery failed — check your User Key is correct.'], 500);
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
