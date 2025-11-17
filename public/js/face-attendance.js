;(async () => {
  console.log('👉 face-attendance.js starting up (auto time in/out enabled)');

  const MODEL_URL = 'https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/weights';

  // Load models once
  await Promise.all([
    faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
    faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
    faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL)
  ]);

  const video    = document.getElementById('video');
  const statusEl = document.getElementById('status');
  const scanEl   = document.getElementById('scanIndicator');
  const distEl   = document.getElementById('distance');

  let lastEmployee = null;
  let lastScanTime = 0;

  // Start camera
  try {
    const stream = await navigator.mediaDevices.getUserMedia({ video: true });
    video.srcObject = stream;
    await video.play();
    statusEl.textContent = '📸 Camera ready — waiting for face...';
  } catch (err) {
    console.error('🚨 camera error', err);
    statusEl.textContent = '❌ Cannot access camera';
    statusEl.className = 'fs-4 text-danger';
    return;
  }

  // Helper: debounce / cooldown between scans
  const COOLDOWN_MS = 10000; // 10 seconds

  async function sendAutoLog(employee_code) {
    try {
      const res = await fetch('/attendance/face-log', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': window.CSRF_TOKEN,
          'Accept': 'application/json'
        },
        body: JSON.stringify({ employee_code })
      });
      const data = await res.json();

      if (data.success) {
        statusEl.textContent = `✅ ${data.message}`;
        statusEl.className   = 'fs-4 text-success';
        console.log(data.message);
      } else if (data.status === 'no_schedule') {
        statusEl.textContent = '⚠️ ' + data.message;
        statusEl.className   = 'fs-4 text-warning';
        alert(data.message);
      } else {
        statusEl.textContent = data.message;
        statusEl.className   = 'fs-4 text-muted';
      }
    } catch (err) {
      console.error('❌ Error logging attendance', err);
      statusEl.textContent = '❌ Error logging attendance';
      statusEl.className   = 'fs-4 text-danger';
    }
  }

  // Continuous loop
  setInterval(async () => {
    scanEl.style.visibility = 'visible';
    statusEl.textContent = 'Scanning…';
    statusEl.className = 'fs-4 text-muted';

    const det = await faceapi
      .detectSingleFace(video, new faceapi.TinyFaceDetectorOptions())
      .withFaceLandmarks()
      .withFaceDescriptor();

    if (!det) {
      scanEl.style.visibility = 'hidden';
      return;
    }

    // Call recognition endpoint
    let resp;
    try {
      const res = await fetch(window.ATT_VALIDATE, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': window.CSRF_TOKEN,
          'Accept': 'application/json'
        },
        body: JSON.stringify({ descriptor: det.descriptor })
      });
      resp = await res.json();

      if (resp.matched && resp.employee) {
  window.showMatchedProfile(resp); // ✅ triggers the UI update
}

      if (!res.ok) throw new Error('No match');
    } catch {
      statusEl.textContent = '❌ No match';
      statusEl.className   = 'fs-4 text-danger';
      scanEl.style.visibility = 'hidden';
      return;
    }

    const distance = resp.distance ?? NaN;
    if (distEl) distEl.textContent = isNaN(distance) ? '–' : distance.toFixed(4);

    // Auto log once per recognition
    if (resp.employee_code) {
      const now = Date.now();
      const cooldownPassed =
        now - lastScanTime > COOLDOWN_MS || lastEmployee !== resp.employee_code;

      if (cooldownPassed) {
        lastScanTime = now;
        lastEmployee = resp.employee_code;

        statusEl.textContent = `✅ ${resp.employee} recognized — logging…`;
        statusEl.className   = 'fs-4 text-info';
        await sendAutoLog(resp.employee_code);
      } else {
        console.log('⏳ skipping duplicate scan');
      }
    }

    scanEl.style.visibility = 'hidden';
  }, 3000);
})();
