<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ $session->topic?->title ?? 'Learning session' }} — Smarter Every Hour</title>
  <link rel="manifest" href="/manifest.json">
  <meta name="theme-color" content="#2563eb">
  @vite(['resources/css/app.css'])
</head>
<body class="learn-page">

<header class="learn-header">
  <a href="{{ route('dashboard') }}" class="learn-brand">
    <span class="brand-icon">&#9889;</span> Smarter Every Hour
  </a>
  @if($session->topic)
    <span class="learn-topic-badge">{{ $session->topic->title }}</span>
  @endif
</header>

<div class="learn-container">
  <div class="learn-initial-message">
    <div class="initial-icon">&#127891;</div>
    <div class="learn-initial-text">{{ $session->initial_message }}</div>
  </div>

  <div
    id="learn-chat-root"
    data-props='@json([
        "token" => $session->session_token,
        "csrfToken" => csrf_token(),
        "initialMessages" => $session->messages ?? [],
        "curricula" => $curricula->map(fn ($c) => ["id" => $c->id, "name" => $c->name])->values(),
        "defaultCurriculumId" => $session->topic?->curriculum?->id,
        "defaultSkillLevel" => $session->topic?->skill_level ?? "intermediate",
    ])'
  ></div>

  <p class="chat-hint">Session expires {{ \Carbon\Carbon::parse($session->expires_at)->diffForHumans() }}</p>
</div>

@vite(['resources/js/learn-chat.jsx'])

</body>
</html>
