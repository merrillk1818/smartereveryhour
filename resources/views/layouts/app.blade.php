<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Smarter Every Hour')</title>
  <link rel="manifest" href="/manifest.json">
  <meta name="theme-color" content="#2563eb">
  <link rel="apple-touch-icon" href="/icons/icon-192.png">
  @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

<nav class="navbar">
  <a href="{{ route('dashboard') }}" class="navbar-brand">
    <span class="brand-icon">&#9889;</span> Smarter Every Hour
  </a>
  <button class="navbar-menu-btn" id="nav-toggle" aria-label="Menu">&#9776;</button>
  <div class="navbar-links" id="nav-links">
    <a href="{{ route('curriculum.index') }}" class="{{ request()->routeIs('curriculum.*') ? 'active' : '' }}">Curriculum</a>
    <a href="{{ route('schedule.index') }}" class="{{ request()->routeIs('schedule.*') ? 'active' : '' }}">Schedule</a>
    @if(Auth::user()->role === 'admin')
      <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.*') ? 'active' : '' }}">Admin</a>
    @endif
    <div class="navbar-user">
      <span>{{ Auth::user()->name }}</span>
      <form method="POST" action="{{ route('logout') }}" style="display:inline">
        @csrf
        <button type="submit" class="btn-link">Sign out</button>
      </form>
    </div>
  </div>
</nav>
<script>
  document.getElementById('nav-toggle').addEventListener('click', function() {
    document.getElementById('nav-links').classList.toggle('open');
  });
</script>

<main class="main-content">
  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="alert alert-error">{{ session('error') }}</div>
  @endif
  @if($errors->any())
    <div class="alert alert-error">{{ $errors->first() }}</div>
  @endif

  @yield('content')
</main>

<div id="push-banner" class="push-banner" style="display:none">
  <span>Enable push notifications to receive your learning reminders.</span>
  <button id="push-enable-btn" class="btn btn-primary btn-sm">Enable Notifications</button>
  <button id="push-dismiss-btn" class="btn btn-ghost btn-sm">Dismiss</button>
</div>

@stack('scripts')
</body>
</html>
