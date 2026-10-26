<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Create account — Smarter Every Hour</title>
  @vite(['resources/css/app.css'])
  <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
</head>
<body class="auth-page">

<div class="auth-container">
  <a href="/" class="auth-brand">
    <span class="brand-icon">&#9889;</span> Smarter Every Hour
  </a>

  <div class="auth-box">
    <h1 class="auth-title">Start learning today</h1>

    @if($errors->any())
      <div class="alert alert-error">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('register') }}">
      @csrf
      <div class="form-group">
        <label>Name</label>
        <input type="text" name="name" value="{{ old('name') }}" placeholder="Your name" required autofocus />
      </div>
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" value="{{ old('email') }}" placeholder="you@example.com" required />
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" placeholder="Min. 8 characters" required />
      </div>
      <div class="form-group">
        <label>Confirm password</label>
        <input type="password" name="password_confirmation" placeholder="Repeat password" required />
      </div>
      <div class="form-group">
        <label>Your timezone</label>
        <select name="timezone" required>
          <option value="">Select timezone...</option>
          @foreach(timezone_identifiers_list() as $tz)
            <option value="{{ $tz }}" {{ old('timezone') === $tz ? 'selected' : '' }}>{{ $tz }}</option>
          @endforeach
        </select>
      </div>
      <div class="cf-turnstile" data-sitekey="{{ config('services.turnstile.site_key') }}" style="margin-bottom:.75rem"></div>
      <button type="submit" class="btn btn-primary btn-full">Create account</button>
    </form>

    <p class="auth-switch">Already have an account? <a href="{{ route('login') }}">Sign in</a></p>
  </div>
</div>

</body>
</html>
