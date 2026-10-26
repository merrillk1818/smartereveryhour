@extends('layouts.app')
@section('title', $curriculum->name . ' Topics — Smarter Every Hour')

@section('content')
<div class="page-header">
  <a href="{{ route('curriculum.index') }}" class="back-link">&larr; Curriculum</a>
  <div>
    <h1>{{ $curriculum->name }}</h1>
    <p class="text-muted">Topics in this curriculum</p>
  </div>
  <div style="display:flex;gap:.5rem">
    <button class="btn btn-ghost" onclick="toggleAiSuggest()">&#10024; Suggest with AI</button>
    <a href="{{ route('curriculum.topics.create', $curriculum) }}" class="btn btn-primary">+ Add topic</a>
  </div>
</div>

{{-- AI Suggest Panel --}}
<div id="ai-suggest-panel" class="card" style="display:none;margin-bottom:1.5rem">
  <div class="card-header">
    <h2>AI topic suggestions</h2>
    <button class="btn btn-ghost btn-sm" onclick="toggleAiSuggest()">Close</button>
  </div>
  <p class="text-muted">Choose a skill level and let AI suggest topics for <strong>{{ $curriculum->name }}</strong>.</p>
  <div class="skill-level-selector" style="margin-bottom:1rem">
    @foreach(['novice' => 'Novice', 'intermediate' => 'Intermediate', 'advanced' => 'Advanced', 'expert' => 'Expert'] as $value => $label)
      <label class="skill-option skill-{{ $value }} {{ $value === 'intermediate' ? 'selected' : '' }}" id="suggest-skill-{{ $value }}">
        <input type="radio" name="suggest_skill_level" value="{{ $value }}" {{ $value === 'intermediate' ? 'checked' : '' }}
          onchange="document.querySelectorAll('.skill-option').forEach(el => el.classList.remove('selected')); this.parentElement.classList.add('selected')" />
        {{ $label }}
      </label>
    @endforeach
  </div>
  <button id="ai-suggest-btn" class="btn btn-primary btn-sm" onclick="fetchSuggestions()">Generate suggestions</button>
  <div id="ai-suggestions-list" style="display:none;margin-top:1rem">
    <div id="ai-topics-checkboxes"></div>
    <div style="margin-top:1rem;display:flex;gap:.5rem">
      <button class="btn btn-primary btn-sm" onclick="addSelectedTopics()">Add selected topics</button>
      <button class="btn btn-ghost btn-sm" onclick="document.getElementById('ai-suggestions-list').style.display='none'">Clear</button>
    </div>
    <div id="ai-bulk-result" style="display:none;margin-top:.75rem"></div>
  </div>
</div>

@forelse($topics as $topic)
  <div class="card topic-card">
    <div class="topic-info">
      <div>
        <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.25rem">
          <h3 style="margin:0">{{ $topic->title }}</h3>
          <span class="skill-badge skill-{{ $topic->skill_level ?? 'intermediate' }}">{{ ucfirst($topic->skill_level ?? 'intermediate') }}</span>
        </div>
        @if($topic->description)
          <p class="text-muted">{{ $topic->description }}</p>
        @endif
        @if($topic->keywords)
          <div class="keywords">
            @foreach(explode(',', $topic->keywords) as $kw)
              <span class="keyword-tag">{{ trim($kw) }}</span>
            @endforeach
          </div>
        @endif
      </div>
      <div class="topic-actions">
        <a href="{{ route('curriculum.topics.edit', [$curriculum, $topic]) }}" class="btn btn-ghost btn-sm">Edit</a>
        <form method="POST" action="{{ route('curriculum.topics.destroy', [$curriculum, $topic]) }}" onsubmit="return confirm('Delete this topic?')">
          @csrf @method('DELETE')
          <button type="submit" class="btn btn-danger btn-sm">Delete</button>
        </form>
      </div>
    </div>
  </div>
@empty
  <div class="empty-state">
    <p>No topics yet. Add topics manually or use AI suggestions to get started.</p>
    <div style="display:flex;gap:.75rem;justify-content:center;margin-top:1rem">
      <button class="btn btn-ghost" onclick="toggleAiSuggest()">&#10024; Suggest with AI</button>
      <a href="{{ route('curriculum.topics.create', $curriculum) }}" class="btn btn-primary">Add first topic</a>
    </div>
  </div>
@endforelse

@push('scripts')
<script>
const curriculumId = {{ $curriculum->id }};
const suggestUrl   = '{{ route('ai.topics.suggest', $curriculum) }}';
const bulkStoreUrl = '{{ route('ai.topics.bulk-store', $curriculum) }}';
const csrf         = '{{ csrf_token() }}';

function toggleAiSuggest() {
  const panel = document.getElementById('ai-suggest-panel');
  panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
}

async function fetchSuggestions() {
  const btn = document.getElementById('ai-suggest-btn');
  const skillLevel = document.querySelector('input[name="suggest_skill_level"]:checked')?.value ?? 'intermediate';
  btn.disabled = true;
  btn.textContent = 'Generating…';

  try {
    const res = await fetch(suggestUrl, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify({ skill_level: skillLevel }),
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.error ?? 'Failed');

    const container = document.getElementById('ai-topics-checkboxes');
    container.innerHTML = data.topics.map((t, i) => `
      <label class="ai-topic-option">
        <input type="checkbox" value="${t}" checked />
        <span>${t}</span>
      </label>
    `).join('');

    document.getElementById('ai-suggestions-list').style.display = 'block';
    document.getElementById('ai-bulk-result').style.display = 'none';
  } catch(e) {
    alert('Error: ' + e.message);
  } finally {
    btn.disabled = false;
    btn.textContent = 'Generate suggestions';
  }
}

async function addSelectedTopics() {
  const checked = [...document.querySelectorAll('#ai-topics-checkboxes input:checked')].map(el => el.value);
  if (!checked.length) return;

  const skillLevel = document.querySelector('input[name="suggest_skill_level"]:checked')?.value ?? 'intermediate';
  const result = document.getElementById('ai-bulk-result');

  try {
    const res = await fetch(bulkStoreUrl, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify({ topics: checked, skill_level: skillLevel }),
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.error ?? 'Failed');

    result.style.display = 'block';
    result.className = 'sms-test-success';
    result.textContent = `Added ${data.added} topic${data.added !== 1 ? 's' : ''}!`;
    setTimeout(() => location.reload(), 1000);
  } catch(e) {
    result.style.display = 'block';
    result.className = 'sms-test-error';
    result.textContent = 'Error: ' + e.message;
  }
}
</script>
@endpush
@endsection
