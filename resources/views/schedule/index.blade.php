@extends('layouts.app')
@section('title', 'Notification Schedule — Smarter Every Hour')

@section('content')
<div class="page-header">
  <h1>Notification schedule</h1>
</div>

<div class="card form-card">
  <form method="POST" action="{{ route('schedule.save') }}">
    @csrf

    <div class="form-row">
      <div class="form-group">
        <label>Start time <span class="required">*</span></label>
        <input type="time" name="start_time" value="{{ old('start_time', $schedule?->start_time ?? '09:00') }}" required />
      </div>
      <div class="form-group">
        <label>End time <span class="required">*</span></label>
        <input type="time" name="end_time" value="{{ old('end_time', $schedule?->end_time ?? '17:00') }}" required />
      </div>
    </div>

    <div class="form-group">
      <label>Frequency <span class="required">*</span></label>
      <select name="frequency_minutes" required>
        @foreach([15=>'Every 15 minutes', 30=>'Every 30 minutes', 60=>'Every hour', 120=>'Every 2 hours', 180=>'Every 3 hours', 240=>'Every 4 hours'] as $val => $label)
          <option value="{{ $val }}" {{ old('frequency_minutes', $schedule?->frequency_minutes ?? 60) == $val ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
      </select>
    </div>

    <div class="form-group">
      <label>Timezone <span class="required">*</span></label>
      <select name="timezone" required>
        @foreach(timezone_identifiers_list() as $tz)
          <option value="{{ $tz }}" {{ old('timezone', $schedule?->timezone ?? Auth::user()->timezone) === $tz ? 'selected' : '' }}>{{ $tz }}</option>
        @endforeach
      </select>
    </div>

    <div class="form-group">
      <label>Active days</label>
      <div class="days-grid">
        @php $selectedDays = old('days_of_week', $schedule?->days_of_week ?? [0,1,2,3,4,5,6]); @endphp
        @foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $i => $day)
          <label class="day-label">
            <input type="checkbox" name="days_of_week[]" value="{{ $i }}" {{ in_array($i, $selectedDays) ? 'checked' : '' }} />
            {{ $day }}
          </label>
        @endforeach
      </div>
    </div>

    <div class="form-group">
      <label class="checkbox-label">
        <input type="hidden" name="is_active" value="0" />
        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $schedule?->is_active ?? true) ? 'checked' : '' }} />
        Schedule is active
      </label>
    </div>

    @if(Auth::user()->pushover_user_key)
    <div class="form-group">
      <label class="checkbox-label">
        <input type="hidden" name="pushover_enabled" value="0" />
        <input type="checkbox" name="pushover_enabled" value="1" {{ old('pushover_enabled', $schedule?->pushover_enabled ?? false) ? 'checked' : '' }} />
        Enable Pushover notifications
      </label>
    </div>
    @endif

    @if(Auth::user()->sms_phone)
    <div class="form-group">
      <label class="checkbox-label">
        <input type="hidden" name="sms_enabled" value="0" />
        <input type="checkbox" name="sms_enabled" value="1" {{ old('sms_enabled', $schedule?->sms_enabled ?? false) ? 'checked' : '' }} />
        Enable SMS notifications
      </label>
    </div>
    @endif

    <div class="form-actions">
      <button type="submit" class="btn btn-primary">Save settings</button>
    </div>
  </form>
</div>

{{-- Pushover connection --}}
<div class="card">
  <div class="card-header">
    <h2>Pushover notifications</h2>
    @if(Auth::user()->pushover_user_key)
      <span class="status-active">&#9679; Connected</span>
    @endif
  </div>
  <p class="text-muted">Receive lessons on your phone or desktop via the Pushover app. Get your User Key from your Pushover profile after signing up.</p>
  <p class="text-muted" style="margin-top:.25rem">
    Download Pushover:
    <a href="https://apps.apple.com/us/app/pushover-notifications/id506088175" target="_blank" rel="noopener">iOS App Store</a>
    &nbsp;&bull;&nbsp;
    <a href="https://play.google.com/store/apps/details?id=net.superblock.pushover" target="_blank" rel="noopener">Google Play</a>
    &nbsp;&bull;&nbsp;
    <a href="https://pushover.net/clients" target="_blank" rel="noopener">Setup &amp; docs &rarr;</a>
  </p>

  @if(Auth::user()->pushover_user_key)
    <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1rem;margin-top:1rem">
      <span class="text-muted" style="font-size:.875rem">Key: ••••••{{ substr(Auth::user()->pushover_user_key, -4) }}</span>
      <button class="btn btn-ghost btn-sm" id="pushover-disconnect-btn" onclick="disconnectPushover()">Disconnect</button>
    </div>
    <div id="pushover-disconnect-result" style="display:none;margin-bottom:.75rem"></div>
  @else
    <div id="pushover-step1" style="margin-top:1rem">
      <div class="form-group">
        <label>Your Pushover User Key</label>
        <input type="text" id="pushover-user-key" placeholder="uXXXXXXXXXXXXXXXXXXXXXXXX" maxlength="30" style="font-family:monospace" />
        <p class="field-hint">30-character key from your Pushover profile.</p>
      </div>
      <button class="btn btn-primary btn-sm" id="pushover-send-pin-btn" onclick="sendPushoverPin()">Send verification PIN</button>
      <div id="pushover-pin-result" style="display:none;margin-top:.75rem"></div>
    </div>

    <div id="pushover-step2" style="display:none;margin-top:1rem">
      <div class="form-group">
        <label>Enter the 6-digit PIN sent to your device</label>
        <div style="display:flex;gap:.5rem;align-items:center">
          <input type="text" id="pushover-pin-input" placeholder="123456" maxlength="6" inputmode="numeric" style="width:120px;font-size:1.25rem;letter-spacing:.25em;text-align:center" />
          <button class="btn btn-primary btn-sm" onclick="verifyPushoverPin()">Verify</button>
          <button class="btn btn-ghost btn-sm" onclick="document.getElementById('pushover-step2').style.display='none';document.getElementById('pushover-step1').style.display='block'">Back</button>
        </div>
      </div>
      <div id="pushover-verify-result" style="display:none;margin-top:.5rem"></div>
    </div>
  @endif
</div>

{{-- SMS connection --}}
<div class="card">
  <div class="card-header">
    <h2>SMS notifications</h2>
    @if(Auth::user()->sms_phone)
      <span class="status-active">&#9679; Connected</span>
    @endif
  </div>
  <p class="text-muted">Receive lessons as text messages. Enter your mobile number and we'll send a verification code to confirm it's you.</p>

  @if(Auth::user()->sms_phone)
    <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1rem;margin-top:1rem">
      <span class="text-muted" style="font-size:.875rem">Number: ••••••{{ substr(Auth::user()->sms_phone, -4) }}</span>
      <button class="btn btn-ghost btn-sm" id="sms-disconnect-btn" onclick="disconnectSms()">Disconnect</button>
    </div>
    <div id="sms-disconnect-result" style="display:none;margin-bottom:.75rem"></div>
  @else
    <div id="sms-step1" style="margin-top:1rem">
      <div class="form-group">
        <label>Your mobile number</label>
        <input type="tel" id="sms-phone" placeholder="14155551234" maxlength="20" inputmode="tel" />
        <p class="field-hint">Include country code — US numbers start with 1 (e.g. 14155551234).</p>
      </div>
      <button class="btn btn-primary btn-sm" id="sms-send-code-btn" onclick="sendSmsCode()">Send verification code</button>
      <div id="sms-send-result" style="display:none;margin-top:.75rem"></div>
    </div>

    <div id="sms-step2" style="display:none;margin-top:1rem">
      <div class="form-group">
        <label>Enter the 6-digit code sent to your phone</label>
        <div style="display:flex;gap:.5rem;align-items:center">
          <input type="text" id="sms-code-input" placeholder="123456" maxlength="6" inputmode="numeric" style="width:120px;font-size:1.25rem;letter-spacing:.25em;text-align:center" />
          <button class="btn btn-primary btn-sm" onclick="verifySmsCode()">Verify</button>
          <button class="btn btn-ghost btn-sm" onclick="document.getElementById('sms-step2').style.display='none';document.getElementById('sms-step1').style.display='block'">Back</button>
        </div>
      </div>
      <div id="sms-verify-result" style="display:none;margin-top:.5rem"></div>
    </div>
  @endif
</div>

<div class="card info-card">
  <h3>How it works</h3>
  <p>Every 5 minutes our system checks if you're due for a notification. If you are, it picks a random topic from your active curricula, generates a short lesson with AI, and delivers it to your device.</p>
  <p>The notification links to a chat page where you can ask follow-up questions. Sessions expire after 24 hours.</p>
</div>

@push('scripts')
<script>
const csrf = '{{ csrf_token() }}';

async function sendPushoverPin() {
  const userKey = document.getElementById('pushover-user-key').value.trim();
  const btn     = document.getElementById('pushover-send-pin-btn');
  const result  = document.getElementById('pushover-pin-result');
  if (!userKey) { document.getElementById('pushover-user-key').focus(); return; }

  btn.disabled = true;
  btn.textContent = 'Sending…';
  result.style.display = 'none';

  try {
    const res  = await fetch('{{ route('pushover.pin.send') }}', {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify({ user_key: userKey }),
    });
    const data = await res.json();
    if (res.ok) {
      document.getElementById('pushover-step1').style.display = 'none';
      document.getElementById('pushover-step2').style.display = 'block';
      document.getElementById('pushover-pin-input').focus();
    } else {
      result.style.display = 'block';
      result.className = 'sms-test-error';
      result.textContent = data.error ?? 'Something went wrong.';
    }
  } catch {
    result.style.display = 'block';
    result.className = 'sms-test-error';
    result.textContent = 'Request failed.';
  } finally {
    btn.disabled = false;
    btn.textContent = 'Send verification PIN';
  }
}

async function verifyPushoverPin() {
  const pin    = document.getElementById('pushover-pin-input').value.trim();
  const result = document.getElementById('pushover-verify-result');

  try {
    const res  = await fetch('{{ route('pushover.pin.verify') }}', {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify({ pin }),
    });
    const data = await res.json();
    result.style.display = 'block';
    if (res.ok) {
      result.className = 'sms-test-success';
      result.textContent = 'Pushover connected! Reload the page to enable notifications.';
      setTimeout(() => location.reload(), 1500);
    } else {
      result.className = 'sms-test-error';
      result.textContent = data.error ?? 'Verification failed.';
    }
  } catch {
    result.style.display = 'block';
    result.className = 'sms-test-error';
    result.textContent = 'Request failed.';
  }
}

async function disconnectPushover() {
  if (!confirm('Disconnect Pushover? You will stop receiving Pushover notifications.')) return;
  const btn    = document.getElementById('pushover-disconnect-btn');
  const result = document.getElementById('pushover-disconnect-result');
  btn.disabled = true;

  try {
    const res  = await fetch('{{ route('pushover.disconnect') }}', {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
    });
    if (res.ok) { location.reload(); }
    else {
      result.style.display = 'block';
      result.className = 'sms-test-error';
      result.textContent = 'Could not disconnect. Try again.';
      btn.disabled = false;
    }
  } catch {
    btn.disabled = false;
  }
}

async function sendSmsCode() {
  const phone  = document.getElementById('sms-phone').value.trim();
  const btn    = document.getElementById('sms-send-code-btn');
  const result = document.getElementById('sms-send-result');
  if (!phone) { document.getElementById('sms-phone').focus(); return; }

  btn.disabled = true;
  btn.textContent = 'Sending…';
  result.style.display = 'none';

  try {
    const res  = await fetch('{{ route('sms.code.send') }}', {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify({ phone }),
    });
    const data = await res.json();
    if (res.ok) {
      document.getElementById('sms-step1').style.display = 'none';
      document.getElementById('sms-step2').style.display = 'block';
      document.getElementById('sms-code-input').focus();
    } else {
      result.style.display = 'block';
      result.className = 'sms-test-error';
      result.textContent = data.error ?? 'Something went wrong.';
    }
  } catch {
    result.style.display = 'block';
    result.className = 'sms-test-error';
    result.textContent = 'Request failed.';
  } finally {
    btn.disabled = false;
    btn.textContent = 'Send verification code';
  }
}

async function verifySmsCode() {
  const code   = document.getElementById('sms-code-input').value.trim();
  const result = document.getElementById('sms-verify-result');

  try {
    const res  = await fetch('{{ route('sms.code.verify') }}', {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify({ code }),
    });
    const data = await res.json();
    result.style.display = 'block';
    if (res.ok) {
      result.className = 'sms-test-success';
      result.textContent = 'Phone verified! Reload the page to enable SMS notifications.';
      setTimeout(() => location.reload(), 1500);
    } else {
      result.className = 'sms-test-error';
      result.textContent = data.error ?? 'Verification failed.';
    }
  } catch {
    result.style.display = 'block';
    result.className = 'sms-test-error';
    result.textContent = 'Request failed.';
  }
}

async function disconnectSms() {
  if (!confirm('Disconnect SMS? You will stop receiving text message notifications.')) return;
  const btn    = document.getElementById('sms-disconnect-btn');
  const result = document.getElementById('sms-disconnect-result');
  btn.disabled = true;

  try {
    const res  = await fetch('{{ route('sms.disconnect') }}', {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
    });
    if (res.ok) { location.reload(); }
    else {
      result.style.display = 'block';
      result.className = 'sms-test-error';
      result.textContent = 'Could not disconnect. Try again.';
      btn.disabled = false;
    }
  } catch {
    btn.disabled = false;
  }
}
</script>
@endpush
@endsection
