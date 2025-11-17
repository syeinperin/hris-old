@extends('layouts.app')

@section('page_title', 'Face Attendance')

@push('styles')
<style>
:root{
  --brand:#26264e; --brand-2:#3a3a84;
  --ink:#1f2330; --muted:#6b7380;
  --ok:#1e865d; --bad:#c0392b;
}
.hero{
  background:linear-gradient(135deg,var(--brand) 0%,var(--brand-2) 100%);
  color:#fff;border-radius:16px;padding:22px 20px;margin-bottom:18px;
}
.hero h3{margin:0;font-weight:700}
.hero .sub{opacity:.9;font-size:13px;margin:0}
.panel{background:#fff;border:1px solid #eef0f6;border-radius:14px;padding:16px}
.stage{
  background:#0b1527;border-radius:14px;position:relative;overflow:hidden;
  border:1px dashed #dbe1ef;min-height:320px
}
.stage video,.stage canvas{width:100%;height:100%;object-fit:cover}
#overlay{position:absolute;inset:0;pointer-events:none}
.chip{display:inline-flex;align-items:center;gap:8px;padding:6px 10px;border-radius:999px;font-size:12px;font-weight:600}
.chip.info{background:rgba(58,58,132,.10);color:var(--brand-2)}
.chip.ok{background:rgba(30,134,93,.12);color:var(--ok)}
.chip.bad{background:rgba(192,57,43,.10);color:var(--bad)}
.btn{background:#fff;border:2px solid var(--brand);color:var(--brand);font-weight:700;border-radius:12px;padding:10px 14px;cursor:pointer;transition:all .2s ease}
.btn.active{background:var(--brand-2);color:#fff;border-color:var(--brand-2)}
.btn:disabled{opacity:.5;cursor:not-allowed}
.log{margin-top:14px;background:#fafbff;border:1px solid #eef0f6;border-radius:12px;padding:12px;min-height:120px;font-size:13px;overflow-y:auto}

/* --- Big profile confirmation --- */
.profile-box{
  display:none;flex-direction:column;align-items:center;justify-content:center;
  margin-top:14px;text-align:center;
}
.profile-photo{
  width:220px;height:220px;border-radius:50%;
  overflow:hidden;border:6px solid #fff;
  box-shadow:0 6px 20px rgba(0,0,0,.15);
}
.profile-photo img{width:100%;height:100%;object-fit:cover}
.profile-name{font-size:1.4rem;font-weight:700;margin-top:12px;margin-bottom:4px}
.profile-code{color:var(--muted);font-size:.9rem;margin-bottom:10px}
.confirm-box{
  background:var(--brand-2);color:#fff;font-weight:700;
  padding:10px 22px;border-radius:30px;font-size:1.05rem;
}
</style>
@endpush

@section('content')
<div class="container py-3">
  <div class="hero">
    <h3>Face Attendance</h3>
    <p class="sub">Hold your face steady. Select “Time In” or “Time Out” before scanning.</p>
  </div>

  <div class="row g-3">
    <!-- Left: Camera -->
    <div class="col-lg-7">
      <div class="panel mb-3">
        <h5>Live Camera</h5>
        <div class="stage">
          <video id="video" autoplay muted playsinline></video>
          <canvas id="overlay"></canvas>
        </div>
        <div class="mt-2 text-muted" id="camStatus">Initializing camera…</div>
      </div>
    </div>

    <!-- Right: Profile + Controls -->
    <div class="col-lg-5">
      <div class="panel text-center">
        <div id="stateChip" class="chip info">No scan yet</div>

        <!-- Large profile confirmation -->
        <div id="profileBox" class="profile-box">
          <div class="profile-photo">
            <img id="profileImg" src="{{ asset('images/avatar-placeholder.png') }}" alt="Employee">
          </div>
          <div class="profile-name" id="profileName">Employee Name</div>
          <div class="profile-code" id="profileCode">EMP-000</div>
          <div class="confirm-box" id="confirmBox">Timed In</div>
        </div>

        <div class="cta mt-3 d-grid" style="grid-template-columns:1fr 1fr;gap:10px">
          <button class="btn" id="timeInBtn">Time In</button>
          <button class="btn" id="timeOutBtn">Time Out</button>
        </div>

        <div class="log" id="logBox">
          <div class="text-muted">Scanning will automatically time employees in/out when their face matches.</div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/@vladmandic/face-api/dist/face-api.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', async () => {
  const MODEL_URI = "{{ asset('face-models') }}";
  const MATCH_URL = "{{ url('/kiosk/face/match') }}";
  const LOG_URL   = "{{ url('/attendance/face-log') }}";
  const CSRF_TOKEN = "{{ csrf_token() }}";

  const video = document.getElementById('video');
  const overlay = document.getElementById('overlay');
  const ctx = overlay.getContext('2d');
  const stateChip = document.getElementById('stateChip');
  const logBox = document.getElementById('logBox');
  const camStatus = document.getElementById('camStatus');
  const timeInBtn = document.getElementById('timeInBtn');
  const timeOutBtn = document.getElementById('timeOutBtn');

  const profileBox = document.getElementById('profileBox');
  const profileImg = document.getElementById('profileImg');
  const profileName = document.getElementById('profileName');
  const profileCode = document.getElementById('profileCode');
  const confirmBox = document.getElementById('confirmBox');

  let currentMode = null, lastEmployee = null, lastScan = 0, loop = null;
  const COOLDOWN = 6000, INTERVAL = 800, THRESH = 0.45;

  const setChip = (t, txt) => {
    stateChip.className = 'chip ' + t;
    stateChip.textContent = txt;
  };

  const log = m => {
    const p = document.createElement('div');
    p.textContent = `${new Date().toLocaleTimeString()} — ${m}`;
    logBox.appendChild(p);
    logBox.scrollTop = logBox.scrollHeight;
  };

  // --- Mode Buttons ---
  timeInBtn.onclick = () => {
    currentMode = 'time_in';
    timeInBtn.classList.add('active');
    timeOutBtn.classList.remove('active');
    setChip('info', 'Mode: Time In');
  };

  timeOutBtn.onclick = () => {
    currentMode = 'time_out';
    timeOutBtn.classList.add('active');
    timeInBtn.classList.remove('active');
    setChip('info', 'Mode: Time Out');
  };

  // --- Load Models ---
  await faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URI);
  await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URI);
  await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URI);

  // --- Camera Setup ---
  try {
    const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false });
    video.srcObject = stream;
    camStatus.textContent = 'Camera ready. Face the camera to scan.';
    setChip('info', 'Ready to scan');
  } catch (e) {
    camStatus.textContent = '❌ Cannot access camera.';
    camStatus.classList.add('text-danger');
    return;
  }

  // --- Main Detection Loop ---
  loop = setInterval(async () => {
    const det = await faceapi
      .detectSingleFace(video, new faceapi.TinyFaceDetectorOptions({ inputSize: 320, scoreThreshold: 0.45 }))
      .withFaceLandmarks()
      .withFaceDescriptor();

    if (!det) {
      ctx.clearRect(0, 0, overlay.width, overlay.height);
      return;
    }

    overlay.width = video.videoWidth;
    overlay.height = video.videoHeight;
    ctx.clearRect(0, 0, overlay.width, overlay.height);
    const box = det.detection.box;
    ctx.strokeStyle = '#35b7ff';
    ctx.lineWidth = 3;
    ctx.strokeRect(box.x, box.y, box.width, box.height);

    try {
      // Match face
      const res = await fetch(MATCH_URL, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': CSRF_TOKEN
        },
        body: JSON.stringify({ descriptor: Array.from(det.descriptor) })
      });

      const data = await res.json();
      if (!data.matched || data.distance > THRESH) return;

      const now = Date.now();
      if (data.employee.employee_code === lastEmployee && now - lastScan < COOLDOWN) return;
      lastEmployee = data.employee.employee_code;
      lastScan = now;

      // Show profile
      profileBox.style.display = 'flex';
      profileName.textContent = data.employee.name;
      profileCode.textContent = `Code: ${data.employee.employee_code}`;
      profileImg.src = data.employee.profile_picture
        ? `${window.location.origin}/${data.employee.profile_picture}`
        : "{{ asset('images/avatar-placeholder.png') }}";

      confirmBox.textContent = currentMode === 'time_out' ? 'Timed Out' : 'Timed In';

      if (!currentMode) {
        setChip('info', 'Matched. Select mode first.');
        return;
      }

      // --- Log Attendance ---
      const res2 = await fetch(LOG_URL, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': CSRF_TOKEN
        },
        body: JSON.stringify({
          employee_code: data.employee.employee_code,
          mode: currentMode
        })
      });

      const r = await res2.json();

      if (r.success) {
        setChip('ok', confirmBox.textContent);
        log(`✅ ${data.employee.name} ${confirmBox.textContent}`);
        setTimeout(() => {
          profileBox.style.display = 'none';
          setChip('info', 'Ready to scan');
        }, 3000);
      } else {
        setChip('bad', 'Error');
        log('❌ ' + (r.message || 'Failed to log'));
      }
    } catch (e) {
      log('Error ' + e.message);
    }
  }, INTERVAL);

  window.addEventListener('beforeunload', () => loop && clearInterval(loop));
});
</script>
@endpush
