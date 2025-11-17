@extends('layouts.app')

@section('page_title','Face Recognition')

@push('styles')
<style>
  .hero {
    background: linear-gradient(135deg, #26264e 0%, #3a3a84 100%);
    color:#fff; border-radius:16px; padding:26px 24px; margin-bottom:18px;
  }
  .hero h3 { margin:0 0 6px 0; font-weight:700 }
  .cards { display:grid; grid-template-columns:repeat(2,1fr); gap:16px; margin-bottom:24px }
  @media (max-width:900px){ .cards{grid-template-columns:1fr} }
  .cardx {
    border:1px solid #e9ecf5; border-radius:14px; padding:20px; background:#fff;
    display:flex; flex-direction:column; justify-content:space-between;
  }
  .cardx h5 { margin:0; font-weight:700; font-size:18px }
  .muted { color:#6b7380 }
  .btnx {
    display:inline-flex; align-items:center; justify-content:center; gap:10px;
    border-radius:12px; padding:12px 14px; font-weight:700;
    border:2px solid #3a3a84; background:#3a3a84; color:#fff; text-decoration:none;
  }
  .btnx:hover { background:#2c2c54; color:#fff }
  .pill { display:inline-flex; align-items:center; gap:8px; padding:6px 10px; border-radius:999px;
    background:#f2f4ff; color:#3a3a84; font-size:12px; font-weight:600; margin-bottom:10px }
  .table-container {
    background:#fff; border-radius:14px; border:1px solid #e9ecf5; padding:18px;
  }
  .table thead { background:#f8f9fb; font-weight:600 }
  .face-thumb {
    width: 55px;
    height: 55px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #e5e7f1;
  }
  .enrolled-label {
    display:block;
    font-size:12px;
    color:#1e865d;
    font-weight:600;
  }
</style>
@endpush

@section('content')
<div class="container py-3">

  {{-- Hero Section --}}
  <div class="hero">
    <h3>Face Recognition</h3>
    <div class="muted">Enroll employees and view their current face templates.</div>
  </div>

  @php
    use Illuminate\Support\Facades\Route;
    $kioskUrl = route('kiosk.face');
  @endphp

  {{-- Action Cards --}}
  <div class="cards">
    <div class="cardx">
      <div>
        <div class="pill"><i class="bi bi-person-plus"></i> Enrollment</div>
        <h5>Enroll Employee Faces</h5>
        <div class="muted">Capture a face template per employee for reliable matching.</div>
      </div>
      <div class="mt-3">
        <a class="btnx" href="{{ route('face.enroll') }}">
          <i class="bi bi-camera-video"></i> Open Enrollment
        </a>
      </div>
    </div>

    <div class="cardx">
      <div>
        <div class="pill"><i class="bi bi-display"></i> Kiosk</div>
        <h5>Public Face Kiosk</h5>
        <div class="muted">Launch the standalone kiosk for a lobby or tablet.</div>
      </div>
      <div class="mt-3">
        <a class="btnx" href="{{ $kioskUrl }}" target="_blank" rel="noopener">
          <i class="bi bi-box-arrow-up-right"></i> Open Kiosk
        </a>
      </div>
    </div>
  </div>

  {{-- Face Template Table --}}
  <div class="table-container mt-3 shadow-sm">
    <h5 class="mb-3"><i class="bi bi-person-bounding-box me-2"></i>Enrolled Face Templates</h5>
    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead>
          <tr>
            <th>#</th>
            <th>Employee Name</th>
            <th>Department</th>
            <th>Template ID</th>
            <th>Face Template</th>
            <th>Updated</th>
          </tr>
        </thead>
        <tbody>
          @forelse($templates as $t)
            <tr>
              <td>{{ $loop->iteration }}</td>
              <td>{{ $t->employee->name }}</td>
              <td>{{ $t->employee->department->name ?? '—' }}</td>
              <td>{{ $t->id }}</td>
              <td>
                @if($t->image_path && file_exists(public_path('storage/' . $t->image_path)))
                  <div class="text-center">
                    <img src="{{ asset('storage/' . $t->image_path) }}" alt="Face Template" class="face-thumb mb-1">
                    <span class="enrolled-label">Enrolled</span>
                  </div>
                @else
                  <span class="text-muted">No Image</span>
                @endif
              </td>
              <td>{{ $t->updated_at->diffForHumans() }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="text-center text-muted py-3">
                No face templates found.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

</div>
@endsection


