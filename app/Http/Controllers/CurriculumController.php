<?php

namespace App\Http\Controllers;

use App\Models\Curriculum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CurriculumController extends Controller
{
    public function index()
    {
        $curricula = Auth::user()->curricula()->withCount('topics')->get();
        return view('curriculum.index', compact('curricula'));
    }

    public function create()
    {
        return view('curriculum.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        Auth::user()->curricula()->create($data + ['is_active' => true]);

        return redirect()->route('curriculum.index')->with('success', 'Curriculum created.');
    }

    public function edit(Curriculum $curriculum)
    {
        $this->authorize($curriculum);
        return view('curriculum.edit', compact('curriculum'));
    }

    public function update(Request $request, Curriculum $curriculum)
    {
        $this->authorize($curriculum);

        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_active'   => 'boolean',
        ]);

        $curriculum->update($data);

        return redirect()->route('curriculum.index')->with('success', 'Curriculum updated.');
    }

    public function destroy(Curriculum $curriculum)
    {
        $this->authorize($curriculum);
        $curriculum->delete();
        return redirect()->route('curriculum.index')->with('success', 'Curriculum deleted.');
    }

    private function authorize(Curriculum $curriculum): void
    {
        abort_if($curriculum->user_id !== Auth::id(), 403);
    }
}
