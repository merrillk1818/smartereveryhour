@extends('layouts.app')
@section('title', 'Curriculum — Smarter Every Hour')

@section('content')
<div class="page-header">
  <h1>My Curriculum</h1>
  <a href="{{ route('curriculum.create') }}" class="btn btn-primary">+ New curriculum</a>
</div>

@forelse($curricula as $curriculum)
  <div class="card curriculum-card">
    <div class="curriculum-info">
      <div>
        <h2>{{ $curriculum->name }}</h2>
        @if($curriculum->description)
          <p class="text-muted">{{ $curriculum->description }}</p>
        @endif
        <span class="badge {{ $curriculum->is_active ? 'badge-active' : 'badge-paused' }}">
          {{ $curriculum->is_active ? 'Active' : 'Paused' }}
        </span>
        <span class="text-muted"> &mdash; {{ $curriculum->topics_count }} topic{{ $curriculum->topics_count !== 1 ? 's' : '' }}</span>
      </div>
      <div class="curriculum-actions">
        <a href="{{ route('curriculum.topics.index', $curriculum) }}" class="btn btn-ghost btn-sm">Topics</a>
        <a href="{{ route('curriculum.edit', $curriculum) }}" class="btn btn-ghost btn-sm">Edit</a>
        <form method="POST" action="{{ route('curriculum.destroy', $curriculum) }}" onsubmit="return confirm('Delete this curriculum and all its topics?')">
          @csrf @method('DELETE')
          <button type="submit" class="btn btn-danger btn-sm">Delete</button>
        </form>
      </div>
    </div>
  </div>
@empty
  <div class="empty-state">
    <p>No curricula yet. Create your first one to start learning.</p>
    <a href="{{ route('curriculum.create') }}" class="btn btn-primary">Create curriculum</a>
  </div>
@endforelse
@endsection
