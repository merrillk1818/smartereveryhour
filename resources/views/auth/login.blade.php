<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Sign in — Smarter Every Hour</title>
  @vite(['resources/css/app.css'])
  <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
</head>
<body class="auth-page">

<div class="auth-container">
  <a href="/" class="auth-brand">
    <span class="brand-icon">&#9889;</span> Smarter Every Hour
  </a>

  <div class="auth-box">
    <h1 class="auth-title">Welcome back</h1>

    @if($errors->any())
      <div class="alert alert-error">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}">
      @csrf
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" value="{{ old('email') }}" placeholder="you@example.com" required autofocus />
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" placeholder="••••••••" required />
      </div>
      <label class="checkbox-label">
        <input type="checkbox" name="remember" /> Remember me
      </label>
      <div class="cf-turnstile" data-sitekey="{{ config('services.turnstile.site_key') }}" style="margin-bottom:.75rem"></div>
      <button type="submit" class="btn btn-primary btn-full">Sign in</button>
    </form>

    <p class="auth-switch">No account? <a href="{{ route('register') }}">Create one free</a></p>
  </div>
</div>

</body>
</html>
