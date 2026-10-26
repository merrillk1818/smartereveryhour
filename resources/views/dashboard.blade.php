@extends('layouts.app')
@section('title', 'Dashboard — Smarter Every Hour')

@section('content')
<div class="page-header">
  <h1>Dashboard</h1>
</div>

<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-number">{{ $stats['curricula'] }}</div>
    <div class="stat-label">Curricula</div>
  </div>
  <div class="stat-card">
    <div class="stat-number">{{ $stats['topics'] }}</div>
    <div class="stat-label">Topics</div>
  </div>
  <div class="stat-card">
    <div class="stat-number">{{ $stats['sent_today'] }}</div>
    <div class="stat-label">Sent today</div>
  </div>
  <div class="stat-card">
    <div class="stat-number">{{ $stats['sent_total'] }}</div>
    <div class="stat-label">Total sent</div>
  </div>
</div>

@if($curricula->isNotEmpty())
<div class="card manual-send-card">
  <div class="card-header">
    <h2>Send a lesson now</h2>
  </div>
  <div class="manual-send-form">
    <select id="manual-curriculum" onchange="loadTopics(this.value)">
      <option value="">Choose a curriculum…</option>
      @foreach($curricula as $curriculum)
        <option value="{{ $curriculum->id }}">{{ $curriculum->name }}</option>
      @endforeach
    </select>
    <select id="manual-topic" disabled>
      <option value="">Random topic</option>
    </select>
    <button id="manual-send-btn" class="btn btn-primary" onclick="sendManualMessage()" disabled>Send</button>
  </div>
  <div id="manual-send-result" style="display:none;margin-top:1rem"></div>
</div>

<script id="curricula-data" type="application/json">
  @json($curricula->map(fn($c) => ['id' => $c->id, 'topics' => $c->topics->map(fn($t) => ['id' => $t->id, 'title' => $t->title])]))
</script>
@endif

<div class="dashboard-grid">
  <div class="card">
    <div class="card-header">
      <h2>Schedule status</h2>
      <a href="{{ route('schedule.index') }}" class="btn btn-ghost btn-sm">Edit</a>
    </div>
    @if($schedule && $schedule->is_active)
      <p class="status-active">&#9679; Active — {{ $schedule->start_time }} to {{ $schedule->end_time }}, every {{ $schedule->frequency_minutes }} min</p>
      <p class="text-muted">Timezone: {{ $schedule->timezone }}</p>
    @elseif($schedule)
      <p class="status-paused">&#9675; Paused</p>
      <a href="{{ route('schedule.index') }}" class="btn btn-primary btn-sm">Resume</a>
    @else
      <p class="text-muted">No schedule configured yet.</p>
      <a href="{{ route('schedule.index') }}" class="btn btn-primary btn-sm">Set up schedule</a>
    @endif

    @if(Auth::user()->pushover_user_key)
    <div class="sms-test-section">
      <hr class="section-divider" />
      <div class="card-header" style="padding:0;margin-bottom:.75rem">
        <div>
          <strong>Pushover</strong>
          @if($schedule?->pushover_enabled)
            <span class="status-active" style="font-size:.8rem;margin-left:.5rem">&#9679; enabled</span>
          @else
            <span class="text-muted" style="font-size:.8rem;margin-left:.5rem">disabled</span>
          @endif
        </div>
        <button id="pushover-test-btn" class="btn btn-ghost btn-sm" onclick="sendPushoverTest()">Send test</button>
      </div>
      <div id="pushover-test-result" style="display:none"></div>
    </div>
    @else
    <p class="text-muted" style="margin-top:.75rem;font-size:.875rem">
      <a href="{{ route('schedule.index') }}">Connect Pushover</a> to enable mobile notifications.
    </p>
    @endif
  </div>

  <div class="card">
    <div class="card-header">
      <h2>Recent messages</h2>
    </div>
    @forelse($recentLogs as $log)
      <div class="log-item">
        <span class="log-topic">{{ $log->topic?->title ?? 'Unknown topic' }}</span>
        <span class="log-preview">{{ $log->message_preview }}</span>
        <span class="log-time">{{ $log->sent_at->diffForHumans() }}</span>
      </div>
    @empty
      <p class="text-muted">No messages sent yet. Set up your curriculum and schedule to get started.</p>
    @endforelse
  </div>
</div>

@push('scripts')
<script>
const curriculaData = JSON.parse(document.getElementById('curricula-data')?.textContent || '[]');

function loadTopics(curriculumId) {
  const topicSelect = document.getElementById('manual-topic');
  const sendBtn = document.getElementById('manual-send-btn');
  topicSelect.innerHTML = '<option value="">Random topic</option>';
  topicSelect.disabled = !curriculumId;
  sendBtn.disabled = !curriculumId;
  if (!curriculumId) return;
  const curriculum = curriculaData.find(c => c.id == curriculumId);
  curriculum?.topics.forEach(t => {
    const opt = document.createElement('option');
    opt.value = t.id;
    opt.textContent = t.title;
    topicSelect.appendChild(opt);
  });
}

function sendManualMessage() {
  const curriculumId = document.getElementById('manual-curriculum').value;
  const topicId = document.getElementById('manual-topic').value;
  const btn = document.getElementById('manual-send-btn');
  const result = document.getElementById('manual-send-result');
  if (!curriculumId) return;

  btn.disabled = true;
  btn.textContent = 'Generating…';
  result.style.display = 'none';

  fetch('{{ route('message.send') }}', {
    method: 'POST',
    headers: {
      'X-CSRF-TOKEN': '{{ csrf_token() }}',
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    body: JSON.stringify({ curriculum_id: curriculumId, topic_id: topicId || null }),
  })
  .then(r => r.json().then(data => ({ ok: r.ok, data })))
  .then(({ ok, data }) => {
    result.style.display = 'block';
    if (ok) {
      const channels = [data.sent_push && 'push', data.sent_pushover && 'Pushover'].filter(Boolean).join(' + ');
      result.className = 'sms-test-success';
      result.innerHTML = `<strong>${data.topic}</strong> → sent via ${channels}`
        + `<br><span class="sms-preview">${data.message}</span>`
        + `<br><a href="${data.url}" target="_blank" class="btn btn-ghost btn-sm" style="margin-top:.5rem">Open chat session</a>`;
    } else {
      result.className = 'sms-test-error';
      result.textContent = data.error ?? 'Something went wrong.';
    }
  })
  .catch(() => {
    result.style.display = 'block';
    result.className = 'sms-test-error';
    result.textContent = 'Request failed.';
  })
  .finally(() => {
    btn.disabled = false;
    btn.textContent = 'Send';
  });
}

function sendPushoverTest() {
  const btn = document.getElementById('pushover-test-btn');
  const result = document.getElementById('pushover-test-result');
  btn.disabled = true;
  btn.textContent = 'Sending…';
  result.style.display = 'none';

  fetch('{{ route('pushover.test') }}', {
    method: 'POST',
    headers: {
      'X-CSRF-TOKEN': '{{ csrf_token() }}',
      'Accept': 'application/json',
    },
  })
  .then(r => r.json().then(data => ({ ok: r.ok, data })))
  .then(({ ok, data }) => {
    result.style.display = 'block';
    if (ok) {
      result.className = 'sms-test-success';
      result.innerHTML = '<strong>Sent via Pushover!</strong>'
        + (data.topic ? `<br><span class="text-muted">${data.topic}</span>` : '')
        + `<br><span class="sms-preview">${data.message}</span>`;
    } else {
      result.className = 'sms-test-error';
      result.textContent = data.error ?? 'Something went wrong.';
    }
  })
  .catch(() => {
    result.style.display = 'block';
    result.className = 'sms-test-error';
    result.textContent = 'Request failed.';
  })
  .finally(() => {
    btn.disabled = false;
    btn.textContent = 'Send test';
  });
}
</script>
@endpush

@if($stats['curricula'] === 0)
<div class="onboarding-card">
  <h2>Get started in 3 steps</h2>
  <div class="onboarding-steps">
    <div class="step {{ $stats['curricula'] > 0 ? 'done' : '' }}">
      <span class="step-num">1</span>
      <div>
        <strong>Create a curriculum</strong>
        <p>Add a subject you want to study.</p>
        <a href="{{ route('curriculum.create') }}" class="btn btn-primary btn-sm">Create curriculum</a>
      </div>
    </div>
    <div class="step">
      <span class="step-num">2</span>
      <div>
        <strong>Add topics</strong>
        <p>Break your curriculum into topics or keywords.</p>
      </div>
    </div>
    <div class="step">
      <span class="step-num">3</span>
      <div>
        <strong>Set your schedule</strong>
        <p>Choose when and how often to receive messages.</p>
        <a href="{{ route('schedule.index') }}" class="btn btn-ghost btn-sm">Set schedule</a>
      </div>
    </div>
  </div>
</div>
@endif
@endsection
