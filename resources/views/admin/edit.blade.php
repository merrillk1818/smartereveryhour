@extends('layouts.app')
@section('title', 'Edit User — Admin')

@section('content')
<div class="page-header">
  <a href="{{ route('admin.users.index') }}" class="back-link">&larr; Users</a>
  <h1>Edit user</h1>
</div>

<div class="card form-card">
  <form method="POST" action="{{ route('admin.users.update', $user) }}">
    @csrf @method('PUT')

    <div class="form-group">
      <label>Name <span class="required">*</span></label>
      <input type="text" name="name" value="{{ old('name', $user->name) }}" required autofocus />
    </div>

    <div class="form-group">
      <label>Email <span class="required">*</span></label>
      <input type="email" name="email" value="{{ old('email', $user->email) }}" required />
    </div>

    <div class="form-group">
      <label>Role <span class="required">*</span></label>
      <div style="display:flex;gap:.75rem;margin-top:.25rem">
        @foreach(['user' => 'User', 'admin' => 'Admin'] as $value => $label)
          <label style="display:flex;align-items:center;gap:.4rem;cursor:pointer">
            <input type="radio" name="role" value="{{ $value }}"
              {{ old('role', $user->role) === $value ? 'checked' : '' }} />
            {{ $label }}
          </label>
        @endforeach
      </div>
    </div>

    <div class="form-group">
      <label>Timezone <span class="required">*</span></label>
      <select name="timezone" required>
        @foreach(timezone_identifiers_list() as $tz)
          <option value="{{ $tz }}" {{ old('timezone', $user->timezone) === $tz ? 'selected' : '' }}>{{ $tz }}</option>
        @endforeach
      </select>
    </div>

    <hr class="section-divider" />

    <div class="form-group">
      <label>New password <span class="optional">(optional)</span></label>
      <input type="password" name="password" placeholder="Leave blank to keep current password" />
      <p class="field-hint">Minimum 8 characters.</p>
    </div>

    <div class="form-group">
      <label>Confirm new password</label>
      <input type="password" name="password_confirmation" placeholder="Repeat new password" />
    </div>

    <div class="form-actions">
      <a href="{{ route('admin.users.index') }}" class="btn btn-ghost">Cancel</a>
      <button type="submit" class="btn btn-primary">Save changes</button>
    </div>
  </form>
</div>
@endsection
