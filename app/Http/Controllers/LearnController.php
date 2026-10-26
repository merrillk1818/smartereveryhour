<?php

namespace App\Http\Controllers;

use App\Models\Curriculum;
use App\Models\LearningSession;
use App\Services\GeminiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LearnController extends Controller
{
    public function show(string $token)
    {
        $session = LearningSession::where('session_token', $token)->firstOrFail();

        if ($session->isExpired()) {
            return view('learn.expired');
        }

        $curricula = Auth::check()
            ? Curriculum::where('user_id', Auth::id())->where('is_active', true)->get(['id', 'name'])
            : collect();

        return view('learn.session', compact('session', 'curricula'));
    }

    public function chat(Request $request, string $token, GeminiService $gemini)
    {
        $session = LearningSession::where('session_token', $token)->firstOrFail();

        if ($session->isExpired()) {
            return response()->json(['error' => 'Session has expired.'], 410);
        }

        $data = $request->validate(['message' => 'required|string|max:1000']);

        $userMessage = $data['message'];

        $session->appendMessage('user', $userMessage);

        try {
            $response = $gemini->chat(
                $session->topic->title ?? 'this topic',
                $session->initial_message,
                $session->messages ?? [],
                $userMessage
            );
        } catch (\Throwable $e) {
            return response()->json(['error' => 'AI service temporarily unavailable.'], 503);
        }

        $session->appendMessage('assistant', $response);

        return response()->json(['response' => $response]);
    }
}
