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

  <div class="chat-messages" id="chat-messages">
    @foreach($session->messages ?? [] as $msg)
      <div class="chat-message {{ $msg['role'] === 'user' ? 'user-msg' : 'ai-msg' }}">
        <div class="msg-bubble">{{ $msg['content'] }}</div>
      </div>
    @endforeach
  </div>

  <div class="chat-input-area">
    <form id="chat-form" class="chat-form">
      <input
        type="text"
        id="chat-input"
        placeholder="Ask a follow-up question..."
        autocomplete="off"
        maxlength="1000"
      />
      <button type="submit" class="btn btn-primary" id="send-btn">Send</button>
    </form>
    <p class="chat-hint">Session expires {{ \Carbon\Carbon::parse($session->expires_at)->diffForHumans() }}</p>
  </div>

  @if($curricula->isNotEmpty())
  <div class="save-topic-section">
    <button class="save-topic-toggle" onclick="toggleSaveTopic()" id="save-topic-toggle-btn">
      + Save a new topic to my curriculum
    </button>
    <div id="save-topic-panel" style="display:none">
      <p class="text-muted" style="font-size:.875rem;margin:.75rem 0 .5rem">
        Found something interesting? Save it as a topic to get future lessons about it.
      </p>
      <button class="btn btn-ghost btn-sm" id="ai-suggest-topic-btn" onclick="aiSuggestTopic()" style="margin-bottom:.75rem">
        &#10024; Suggest from this conversation
      </button>
      <div class="form-group">
        <label>Topic title</label>
        <input type="text" id="save-topic-title" placeholder="e.g. Quantum entanglement" maxlength="255" />
      </div>
      <div class="form-group">
        <label>Description <span class="optional">(optional)</span></label>
        <textarea id="save-topic-description" rows="2" placeholder="What specifically do you want to learn?"></textarea>
      </div>
      <div class="form-group">
        <label>Add to curriculum</label>
        <select id="save-topic-curriculum">
          @foreach($curricula as $c)
            <option value="{{ $c->id }}" {{ $session->topic?->curriculum?->id === $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="form-group">
        <label>Skill level</label>
        <div class="skill-level-selector">
          @foreach(['novice' => 'Novice', 'intermediate' => 'Intermediate', 'advanced' => 'Advanced', 'expert' => 'Expert'] as $value => $label)
            <label class="skill-option skill-{{ $value }} {{ ($session->topic?->skill_level ?? 'intermediate') === $value ? 'selected' : '' }}">
              <input type="radio" name="save_skill_level" value="{{ $value }}"
                {{ ($session->topic?->skill_level ?? 'intermediate') === $value ? 'checked' : '' }}
                onchange="document.querySelectorAll('.save-topic-section .skill-option').forEach(el=>el.classList.remove('selected'));this.parentElement.classList.add('selected')" />
              {{ $label }}
            </label>
          @endforeach
        </div>
      </div>
      <div style="display:flex;gap:.5rem;margin-top:.75rem">
        <button class="btn btn-primary btn-sm" onclick="saveTopic()" id="save-topic-btn">Save topic</button>
        <button class="btn btn-ghost btn-sm" onclick="toggleSaveTopic()">Cancel</button>
      </div>
      <div id="save-topic-result" style="display:none;margin-top:.75rem"></div>
    </div>
  </div>
  @endif
</div>

<script>
const token = @json($session->session_token);
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
const messagesEl = document.getElementById('chat-messages');
const form = document.getElementById('chat-form');
const input = document.getElementById('chat-input');
const sendBtn = document.getElementById('send-btn');

function appendMessage(role, content) {
  const div = document.createElement('div');
  div.className = 'chat-message ' + (role === 'user' ? 'user-msg' : 'ai-msg');
  const bubble = document.createElement('div');
  bubble.className = 'msg-bubble';
  bubble.textContent = content;
  div.appendChild(bubble);
  messagesEl.appendChild(div);
  messagesEl.scrollTop = messagesEl.scrollHeight;
}

form.addEventListener('submit', async (e) => {
  e.preventDefault();
  const message = input.value.trim();
  if (!message) return;

  appendMessage('user', message);
  input.value = '';
  sendBtn.disabled = true;
  sendBtn.textContent = '...';

  try {
    const res = await fetch(`/learn/${token}/chat`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
      body: JSON.stringify({ message }),
    });
    const data = await res.json();
    appendMessage('assistant', res.ok ? data.response : (data.error || 'Something went wrong.'));
  } catch {
    appendMessage('assistant', 'Network error. Please check your connection and try again.');
  } finally {
    sendBtn.disabled = false;
    sendBtn.textContent = 'Send';
    input.focus();
  }
});

// Save topic panel
function toggleSaveTopic() {
  const panel = document.getElementById('save-topic-panel');
  const btn = document.getElementById('save-topic-toggle-btn');
  const open = panel.style.display === 'none';
  panel.style.display = open ? 'block' : 'none';
  btn.textContent = open ? '− Close' : '+ Save a new topic to my curriculum';
}

async function aiSuggestTopic() {
  const btn = document.getElementById('ai-suggest-topic-btn');
  btn.disabled = true;
  btn.textContent = 'Thinking…';

  try {
    const res = await fetch(`/learn/${token}/suggest-topic`, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
    });
    const data = await res.json();
    if (data.suggestion) {
      document.getElementById('save-topic-title').value = data.suggestion.title ?? '';
      document.getElementById('save-topic-description').value = data.suggestion.description ?? '';
    } else {
      btn.textContent = 'No new topic detected — fill in manually';
      setTimeout(() => { btn.textContent = '✨ Suggest from this conversation'; btn.disabled = false; }, 2000);
      return;
    }
  } catch(e) {
    console.error(e);
  } finally {
    btn.disabled = false;
    btn.textContent = '✨ Suggest from this conversation';
  }
}

async function saveTopic() {
  const title = document.getElementById('save-topic-title').value.trim();
  const description = document.getElementById('save-topic-description').value.trim();
  const curriculumId = document.getElementById('save-topic-curriculum').value;
  const skillLevel = document.querySelector('input[name="save_skill_level"]:checked')?.value ?? 'intermediate';
  const result = document.getElementById('save-topic-result');

  if (!title) { document.getElementById('save-topic-title').focus(); return; }

  const btn = document.getElementById('save-topic-btn');
  btn.disabled = true;
  btn.textContent = 'Saving…';

  try {
    const res = await fetch(`/learn/${token}/save-topic`, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify({ title, description, curriculum_id: curriculumId, skill_level: skillLevel }),
    });
    const data = await res.json();
    result.style.display = 'block';
    if (res.ok) {
      result.className = 'sms-test-success';
      result.textContent = `"${data.topic}" saved to ${data.curriculum}!`;
      document.getElementById('save-topic-title').value = '';
      document.getElementById('save-topic-description').value = '';
    } else {
      result.className = 'sms-test-error';
      result.textContent = data.error ?? 'Something went wrong.';
    }
  } catch {
    result.style.display = 'block';
    result.className = 'sms-test-error';
    result.textContent = 'Request failed.';
  } finally {
    btn.disabled = false;
    btn.textContent = 'Save topic';
  }
}

messagesEl.scrollTop = messagesEl.scrollHeight;
</script>

</body>
</html>
