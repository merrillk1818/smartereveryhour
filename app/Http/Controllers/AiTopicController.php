<?php

namespace App\Http\Controllers;

use App\Models\Curriculum;
use App\Models\LearningSession;
use App\Models\Topic;
use App\Services\GeminiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AiTopicController extends Controller
{
    public function suggest(Request $request, Curriculum $curriculum, GeminiService $gemini): JsonResponse
    {
        abort_if($curriculum->user_id !== Auth::id(), 403);

        $request->validate([
            'skill_level' => 'required|in:novice,intermediate,advanced,expert',
        ]);

        try {
            $topics = $gemini->suggestTopics(
                $curriculum->name,
                $curriculum->description ?? '',
                $request->skill_level
            );

            if (empty($topics)) {
                return response()->json(['error' => 'AI could not generate suggestions. Try again.'], 500);
            }

            return response()->json(['topics' => $topics]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function bulkStore(Request $request, Curriculum $curriculum): JsonResponse
    {
        abort_if($curriculum->user_id !== Auth::id(), 403);

        $request->validate([
            'topics'        => 'required|array|min:1|max:8',
            'topics.*'      => 'required|string|max:255',
            'skill_level'   => 'required|in:novice,intermediate,advanced,expert',
        ]);

        $nextOrder = $curriculum->topics()->max('sort_order') + 1;

        foreach ($request->topics as $title) {
            $curriculum->topics()->create([
                'title'       => trim($title),
                'skill_level' => $request->skill_level,
                'sort_order'  => $nextOrder++,
            ]);
        }

        return response()->json(['added' => count($request->topics)]);
    }

    public function suggestFromChat(Request $request, string $token, GeminiService $gemini): JsonResponse
    {
        $session = LearningSession::where('session_token', $token)->firstOrFail();

        try {
            $suggestion = $gemini->suggestTopicFromChat(
                $session->topic?->title ?? 'this topic',
                $session->messages ?? []
            );

            return response()->json(['suggestion' => $suggestion]);
        } catch (\Throwable $e) {
            return response()->json(['suggestion' => null]);
        }
    }

    public function saveFromChat(Request $request, string $token): JsonResponse
    {
        $session = LearningSession::where('session_token', $token)->firstOrFail();

        abort_if($session->user_id !== Auth::id(), 403);

        $request->validate([
            'title'         => 'required|string|max:255',
            'description'   => 'nullable|string|max:2000',
            'curriculum_id' => 'required|integer',
            'skill_level'   => 'required|in:novice,intermediate,advanced,expert',
        ]);

        $curriculum = Curriculum::where('id', $request->curriculum_id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $nextOrder = $curriculum->topics()->max('sort_order') + 1;

        $topic = $curriculum->topics()->create([
            'title'       => $request->title,
            'description' => $request->description,
            'skill_level' => $request->skill_level,
            'sort_order'  => $nextOrder,
        ]);

        return response()->json([
            'topic'      => $topic->title,
            'curriculum' => $curriculum->name,
        ]);
    }
}
