<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Smarter Every Hour</title>
  <link rel="manifest" href="/manifest.json">
  <meta name="theme-color" content="#2563eb">
  @vite(['resources/css/app.css'])
</head>
<body class="landing">

<nav class="navbar">
  <a href="/" class="navbar-brand">
    <span class="brand-icon">&#9889;</span> Smarter Every Hour
  </a>
  <button class="navbar-menu-btn" id="nav-toggle" aria-label="Menu">&#9776;</button>
  <div class="navbar-links" id="nav-links">
    <a href="{{ route('login') }}">Sign in</a>
    <a href="{{ route('register') }}" class="btn btn-primary btn-sm">Get started</a>
  </div>
</nav>
<script>
  document.getElementById('nav-toggle').addEventListener('click', function() {
    document.getElementById('nav-links').classList.toggle('open');
  });
</script>

<section class="hero">
  <div class="hero-content">
    <h1>Learn anything.<br>One message at a time.</h1>
    <p class="hero-sub">Build a curriculum of topics you want to master. Get short, AI-generated lessons sent as push notifications throughout your day. Tap to explore deeper — no app download needed.</p>
    <div class="hero-actions">
      <a href="{{ route('register') }}" class="btn btn-primary btn-lg">Start learning free</a>
      <a href="{{ route('login') }}" class="btn btn-ghost btn-lg">Sign in</a>
    </div>
  </div>
  <div class="hero-visual">
    <div class="phone-mockup">
      <div class="notification-card">
        <div class="notif-header">
          <span class="notif-icon">&#9889;</span>
          <span class="notif-app">Smarter Every Hour</span>
        </div>
        <div class="notif-body">A CPU executes billions of simple math operations per second — that speed is what makes modern software feel instant.</div>
        <div class="notif-action">Tap to learn more &rsaquo;</div>
      </div>
    </div>
  </div>
</section>

<section class="features">
  <div class="feature">
    <div class="feature-icon">&#128218;</div>
    <h3>Build your curriculum</h3>
    <p>Organize any subject into topics. From AWS certifications to history, cooking techniques to programming.</p>
  </div>
  <div class="feature">
    <div class="feature-icon">&#8987;</div>
    <h3>Set your schedule</h3>
    <p>Choose your active hours and how often you want reminders. Once an hour, every 30 minutes — you decide.</p>
  </div>
  <div class="feature">
    <div class="feature-icon">&#129302;</div>
    <h3>AI-powered micro-lessons</h3>
    <p>Each notification delivers one focused insight about a topic, then lets you ask follow-up questions in a live chat.</p>
  </div>
</section>

</body>
</html>
