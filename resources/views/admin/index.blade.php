@extends('layouts.app')
@section('title', 'Admin — Users')

@section('content')
<div class="page-header">
  <h1>User management</h1>
</div>

@if(session('error'))
  <div class="alert alert-error">{{ session('error') }}</div>
@endif

@foreach($users as $user)
<div class="card" style="margin-bottom:1rem">
  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.75rem">
    <div>
      <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.25rem">
        <strong>{{ $user->name }}</strong>
        <span class="skill-badge {{ $user->role === 'admin' ? 'skill-advanced' : 'skill-novice' }}">
          {{ ucfirst($user->role) }}
        </span>
        @if($user->id === Auth::id())
          <span class="text-muted" style="font-size:.8rem">(you)</span>
        @endif
      </div>
      <div class="text-muted" style="font-size:.875rem">{{ $user->email }}</div>
      <div class="text-muted" style="font-size:.8rem;margin-top:.25rem">
        Joined {{ $user->created_at->diffForHumans() }} · {{ $user->timezone }}
      </div>
    </div>
    <div style="display:flex;gap:.5rem">
      <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-ghost btn-sm">Edit</a>
      @if($user->id !== Auth::id())
        <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
              onsubmit="return confirm('Delete {{ addslashes($user->name) }}? This cannot be undone.')">
          @csrf @method('DELETE')
          <button type="submit" class="btn btn-danger btn-sm">Delete</button>
        </form>
      @endif
    </div>
  </div>
</div>
@endforeach
@endsection
