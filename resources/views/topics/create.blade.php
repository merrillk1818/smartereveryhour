@extends('layouts.app')
@section('title', 'Add Topic — Smarter Every Hour')

@section('content')
<div class="page-header">
  <a href="{{ route('curriculum.topics.index', $curriculum) }}" class="back-link">&larr; {{ $curriculum->name }}</a>
  <h1>Add topic</h1>
</div>

<div class="card form-card">
  <form method="POST" action="{{ route('curriculum.topics.store', $curriculum) }}">
    @csrf
    <div class="form-group">
      <label>Topic title <span class="required">*</span></label>
      <input type="text" name="title" value="{{ old('title') }}" placeholder="e.g. CPU Architecture, Treaty of Versailles" required autofocus />
    </div>
    <div class="form-group">
      <label>Skill level <span class="required">*</span></label>
      <div class="skill-level-selector">
        @foreach(['novice' => 'Novice', 'intermediate' => 'Intermediate', 'advanced' => 'Advanced', 'expert' => 'Expert'] as $value => $label)
          <label class="skill-option skill-{{ $value }} {{ old('skill_level', 'intermediate') === $value ? 'selected' : '' }}">
            <input type="radio" name="skill_level" value="{{ $value }}" {{ old('skill_level', 'intermediate') === $value ? 'checked' : '' }} />
            {{ $label }}
          </label>
        @endforeach
      </div>
    </div>
    <div class="form-group">
      <label>Description <span class="optional">(optional)</span></label>
      <textarea name="description" rows="3" placeholder="More context to help the AI generate better explanations...">{{ old('description') }}</textarea>
    </div>
    <div class="form-group">
      <label>Keywords <span class="optional">(optional)</span></label>
      <input type="text" name="keywords" value="{{ old('keywords') }}" placeholder="comma, separated, keywords" />
      <p class="field-hint">Keywords guide the AI toward specific aspects of this topic.</p>
    </div>
    <div class="form-actions">
      <a href="{{ route('curriculum.topics.index', $curriculum) }}" class="btn btn-ghost">Cancel</a>
      <button type="submit" class="btn btn-primary">Add topic</button>
    </div>
  </form>
</div>
@endsection
