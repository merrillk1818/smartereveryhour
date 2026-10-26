<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Session expired — Smarter Every Hour</title>
  @vite(['resources/css/app.css'])
</head>
<body class="auth-page">
<div class="auth-container">
  <a href="/" class="auth-brand">
    <span class="brand-icon">&#9889;</span> Smarter Every Hour
  </a>
  <div class="auth-box" style="text-align:center">
    <div style="font-size:3rem">&#8987;</div>
    <h1 class="auth-title">Session expired</h1>
    <p class="text-muted">This learning session has expired after 24 hours. Your next notification will start a fresh session.</p>
    <a href="{{ route('dashboard') }}" class="btn btn-primary" style="margin-top:1rem">Go to dashboard</a>
  </div>
</div>
</body>
</html>
