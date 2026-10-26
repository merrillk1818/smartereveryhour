<?php

namespace App\Http\Controllers;

use App\Models\Curriculum;
use App\Models\Topic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TopicController extends Controller
{
    public function index(Curriculum $curriculum)
    {
        abort_if($curriculum->user_id !== Auth::id(), 403);
        $topics = $curriculum->topics;
        return view('topics.index', compact('curriculum', 'topics'));
    }

    public function create(Curriculum $curriculum)
    {
        abort_if($curriculum->user_id !== Auth::id(), 403);
        return view('topics.create', compact('curriculum'));
    }

    public function store(Request $request, Curriculum $curriculum)
    {
        abort_if($curriculum->user_id !== Auth::id(), 403);

        $data = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'keywords'    => 'nullable|string|max:500',
            'skill_level' => 'required|in:novice,intermediate,advanced,expert',
        ]);

        $data['sort_order'] = $curriculum->topics()->max('sort_order') + 1;

        $curriculum->topics()->create($data);

        return redirect()->route('curriculum.topics.index', $curriculum)->with('success', 'Topic added.');
    }

    public function edit(Curriculum $curriculum, Topic $topic)
    {
        abort_if($curriculum->user_id !== Auth::id(), 403);
        abort_if($topic->curriculum_id !== $curriculum->id, 403);
        return view('topics.edit', compact('curriculum', 'topic'));
    }

    public function update(Request $request, Curriculum $curriculum, Topic $topic)
    {
        abort_if($curriculum->user_id !== Auth::id(), 403);
        abort_if($topic->curriculum_id !== $curriculum->id, 403);

        $data = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'keywords'    => 'nullable|string|max:500',
            'skill_level' => 'required|in:novice,intermediate,advanced,expert',
        ]);

        $topic->update($data);

        return redirect()->route('curriculum.topics.index', $curriculum)->with('success', 'Topic updated.');
    }

    public function destroy(Curriculum $curriculum, Topic $topic)
    {
        abort_if($curriculum->user_id !== Auth::id(), 403);
        abort_if($topic->curriculum_id !== $curriculum->id, 403);
        $topic->delete();
        return redirect()->route('curriculum.topics.index', $curriculum)->with('success', 'Topic deleted.');
    }
}
