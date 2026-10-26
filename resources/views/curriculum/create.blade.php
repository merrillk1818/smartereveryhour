@extends('layouts.app')
@section('title', 'New Curriculum — Smarter Every Hour')

@section('content')
<div class="page-header">
  <a href="{{ route('curriculum.index') }}" class="back-link">&larr; Curriculum</a>
  <h1>New curriculum</h1>
</div>

<div class="card form-card">
  <form method="POST" action="{{ route('curriculum.store') }}">
    @csrf
    <div class="form-group">
      <label>Name <span class="required">*</span></label>
      <input type="text" name="name" value="{{ old('name') }}" placeholder="e.g. AWS Solutions Architect, World History" required autofocus />
    </div>
    <div class="form-group">
      <label>Description <span class="optional">(optional)</span></label>
      <textarea name="description" rows="3" placeholder="Briefly describe what you want to learn...">{{ old('description') }}</textarea>
    </div>
    <div class="form-actions">
      <a href="{{ route('curriculum.index') }}" class="btn btn-ghost">Cancel</a>
      <button type="submit" class="btn btn-primary">Create curriculum</button>
    </div>
  </form>
</div>
@endsection
