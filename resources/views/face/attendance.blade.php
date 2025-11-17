@extends('layouts.app')

@section('page_title', 'Face Attendance')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/face-attendance.css') }}">
<style>
  .profile-picture img {
    width: 140px;
    height: 140px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #e4e4e4;
  }
</style>
@endpush

@section('content')
@php
  use Illuminate\Support\Facades\Route;
  $attendanceAction = Route::has('attendance.logAttendance')
      ? route('attendance.logAttendance')
      : url('/attendance/log');
@endphp

<div class="container py-3">
  <div class="hero">
    <h3 class="mb-0">Face Attendance</h3>
    <p class="sub mb-1">Hold your face steady. Select “Time In” or “Time Out” before scanning.</p>
  </div>

  <hr>
<h5>🧪 Test Image Rendering</h5>
<img 
  id="testImg" 
  src="http://127.0.0.1:8000/storage/uploads/profile_picture/SAzRr7KBbIuIkYeJHO5a9k4CxaxEfULX2Da6TJtx.jpg"
  style="width:150px;border-radius:50%;border:3px solid #ccc;"
  onerror="this.style.border='3px solid red'; this.title='❌ Not loading';"
/>


  <div class="row g-3">
    <!-- Live Camera -->
    <div class="col-lg-7">
      <div class="panel mb-3">
        <h5 class="mb-2">Live Camera</h5>
        <div class="stage">
          <video id="video" autoplay muted playsinline></video>
          <canvas id="overlay"></canvas>
        </div>
        <div id="camStatus" class="mt-2 muted">Initializing camera…</div>
      </div>
    </div>

    <!-- Confirmation / Result -->
    <div class="col-lg-5">
      <div class="panel text-center">
        <div id="stateChip" class="chip info mb-3">Ready to scan</div>

        <!-- Large Profile Preview -->
 <div id="matchProfile" class="match-profile d-none text-center">
  <div class="profile-picture mx-auto mb-3">
    <img id="profilePhoto"
         src="{{ asset('images/default-profile.png') }}"
         alt="Employee Photo"
         onerror="this.src='{{ asset('images/default-profile.png') }}'">
  </div>
  <h4 id="profileName" class="mt-1 mb-1 fw-bold">Employee Name</h4>
  <p id="profileCode" class="text-muted small mb-3">Code: EMP-000</p>

  <div id="confirmationBox" class="confirmation-box">
    <span id="confirmationText">Timed In</span>
  </div>
</div>


        <!-- Time In / Out buttons -->
        <div class="cta mt-4">
          <form id="timeInForm" action="{{ $attendanceAction }}" method="POST">
            @csrf
            <input type="hidden" name="attendance_type" value="time_in">
            <input type="hidden" name="employee_code" id="empCodeIn">
            <button type="submit" class="btn-outline" id="timeInBtn" disabled>
              <i class="bi bi-box-arrow-in-right"></i> Time In
            </button>
          </form>

          <form id="timeOutForm" action="{{ $attendanceAction }}" method="POST">
            @csrf
            <input type="hidden" name="attendance_type" value="time_out">
            <input type="hidden" name="employee_code" id="empCodeOut">
            <button type="submit" class="btn-outline" id="timeOutBtn" disabled>
              <i class="bi bi-box-arrow-right"></i> Time Out
            </button>
          </form>
        </div>

        <div class="log mt-4" id="logBox">
          <div class="text-muted small">
            Scanning will automatically time employees in/out when their face matches.
          </div>
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
  const MODEL_URL = "{{ asset('face-models') }}";
  const MATCH_URL = "{{ route('kiosk.face.match') }}";
  const LOG_URL   = "{{ route('attendance.faceLog') }}";
  const CSRF      = "{{ csrf_token() }}";

  const video = document.getElementById('video');
  const camStatus = document.getElementById('camStatus');
  const stateChip = document.getElementById('stateChip');
  const logBox = document.getElementById('logBox');
  const profileBox = document.getElementById('matchProfile');
  const profileName = document.getElementById('profileName');
  const profileCode = document.getElementById('profileCode');
  const profilePhoto = document.getElementById('profilePhoto');
  const confirmationText = document.getElementById('confirmationText');

  const timeInBtn = document.getElementById('timeInBtn');
  const timeOutBtn = document.getElementById('timeOutBtn');

  let currentMode = null;
  let lastEmployee = null;
  let lastMode = null;
  let lastScan = 0;
  const COOLDOWN = 10000; // ms
  const DETECT_INTERVAL = 800;
  const THRESH = 0.45;

  function setChip(type, text) {
    stateChip.className = 'chip ' + type;
    stateChip.textContent = text;
  }

  function log(msg) {
    const p = document.createElement('div');
    p.textContent = new Date().toLocaleTimeString() + ' — ' + msg;
    logBox.appendChild(p);
    logBox.scrollTop = logBox.scrollHeight;
  }

  // Mode buttons
  timeInBtn.onclick = () => {
    currentMode = 'time_in';
    timeInBtn.disabled = true;
    timeOutBtn.disabled = false;
    setChip('info', 'Mode: Time In');
  };
  timeOutBtn.onclick = () => {
    currentMode = 'time_out';
    timeOutBtn.disabled = true;
    timeInBtn.disabled = false;
    setChip('info', 'Mode: Time Out');
  };

  // Load models
  await Promise.all([
    faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
    faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
    faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL)
  ]);

  // Start camera
  try {
    const stream = await navigator.mediaDevices.getUserMedia({ video: true });
    video.srcObject = stream;
    await video.play();
    camStatus.textContent = '📸 Camera ready — waiting for face...';
  } catch (err) {
    camStatus.textContent = '❌ Cannot access camera.';
    camStatus.classList.add('text-danger');
    return;
  }

  async function sendLog(empCode, mode) {
    const now = Date.now();
    if (lastEmployee === empCode && lastMode === mode && now - lastScan < COOLDOWN) {
      log('⏳ Skipping duplicate scan');
      return;
    }
    lastEmployee = empCode;
    lastMode = mode;
    lastScan = now;

    try {
      const res = await fetch(LOG_URL, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': CSRF
        },
        body: JSON.stringify({ employee_code: empCode, mode })
      });
      const data = await res.json();
      log(data.message || 'Response received.');

      if (data.success) {
        confirmationText.textContent = mode === 'time_in' ? 'Timed In' : 'Timed Out';
        setChip('ok', confirmationText.textContent);
      } else {
        setChip('bad', 'Error');
      }

      setTimeout(() => setChip('info', 'Ready to scan'), 4000);
    } catch (err) {
      log('❌ Error logging attendance');
    }
  }

  // Detection loop
  setInterval(async () => {
    const det = await faceapi
      .detectSingleFace(video, new faceapi.TinyFaceDetectorOptions())
      .withFaceLandmarks()
      .withFaceDescriptor();

    if (!det) return;

    try {
      const res = await fetch(MATCH_URL, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': CSRF
        },
        body: JSON.stringify({ descriptor: Array.from(det.descriptor) })
      });
      const resp = await res.json();
      if (!resp.matched || !resp.employee) return;

      const emp = resp.employee;
      profileBox.classList.remove('d-none');
      profileName.textContent = emp.name;
      profileCode.textContent = 'Code: ' + emp.employee_code;
      profilePhoto.src = emp.profile_picture_url || '{{ asset('images/default-profile.png') }}';

      if (!currentMode) {
        setChip('info', 'Matched. Select mode first.');
        return;
      }

      await sendLog(emp.employee_code, currentMode);
    } catch (err) {
      log('❌ Match or log failed: ' + err.message);
    }
  }, DETECT_INTERVAL);
});
</script>
@endpush
