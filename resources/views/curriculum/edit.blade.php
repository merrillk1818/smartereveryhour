@extends('layouts.app')
@section('title', 'Edit Curriculum — Smarter Every Hour')

@section('content')
<div class="page-header">
  <a href="{{ route('curriculum.index') }}" class="back-link">&larr; Curriculum</a>
  <h1>Edit curriculum</h1>
</div>

<div class="card form-card">
  <form method="POST" action="{{ route('curriculum.update', $curriculum) }}">
    @csrf @method('PUT')
    <div class="form-group">
      <label>Name <span class="required">*</span></label>
      <input type="text" name="name" value="{{ old('name', $curriculum->name) }}" required autofocus />
    </div>
    <div class="form-group">
      <label>Description <span class="optional">(optional)</span></label>
      <textarea name="description" rows="3">{{ old('description', $curriculum->description) }}</textarea>
    </div>
    <div class="form-group">
      <label class="checkbox-label">
        <input type="hidden" name="is_active" value="0" />
        <input type="checkbox" name="is_active" value="1" {{ $curriculum->is_active ? 'checked' : '' }} />
        Active (include in notification rotation)
      </label>
    </div>
    <div class="form-actions">
      <a href="{{ route('curriculum.index') }}" class="btn btn-ghost">Cancel</a>
      <button type="submit" class="btn btn-primary">Save changes</button>
    </div>
  </form>
</div>
@endsection
