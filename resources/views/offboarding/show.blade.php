{{-- resources/views/offboarding/show.blade.php --}}
@extends('layouts.app')

@section('page_title', 'Offboarding #'.$offboarding->id)

@push('styles')
<style>
  .ofb-status-pill{
    text-transform: capitalize;
    font-weight: 600;
    letter-spacing:.25px;
  }
  .ofb-card{
    border:1px solid #edf0f4;
    border-radius:14px;
    box-shadow:0 6px 18px rgba(18,38,63,.06);
  }
  .ofb-meta dt{
    width:140px;
    color:#6b7a90;
  }
  .ofb-meta dd{
    margin-left:160px;
    font-weight:600;
    color:#24324a;
  }
  .ofb-actions .btn{
    min-width:140px;
  }
  .ofb-actions .btn i{
    margin-right:6px;
  }
  .ofb-toolbar{
    position:sticky;
    bottom:0;
    background:#fff;
    border-top:1px solid #eef2f7;
    padding:10px 16px;
    z-index:100;
  }
  @media (max-width: 576px){
    .ofb-meta dt{ width:120px; }
    .ofb-meta dd{ margin-left:130px; }
  }
</style>
@endpush

@push('scripts')
<script>
  // Optional: auto-submit on ENTER in date field
  document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('ofb-schedule-form');
    const input = document.getElementById('scheduled_at');
    if(form && input){
      input.addEventListener('keydown', (e) => {
        if(e.key === 'Enter'){ e.preventDefault(); form.submit(); }
      });
    }
  });
</script>
@endpush

@section('content')
<div class="container-fluid">

  <div class="d-flex align-items-center justify-content-between mb-3">
    <div>
      <h3 class="mb-1">Offboarding #{{ $offboarding->id }}</h3>
      <span class="badge bg-secondary ofb-status-pill">
        {{ str_replace('_',' ', $offboarding->status) }}
      </span>
    </div>
    <a href="{{ route('offboarding.index') }}" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-arrow-left"></i> Back
    </a>
  </div>

  <div class="ofb-card p-4 mb-4">
    <div class="row g-4">

      {{-- LEFT: main facts --}}
      <div class="col-12 col-lg-7">
        <dl class="ofb-meta mb-0">
          <dt>Employee</dt>
          <dd>
            {{ $offboarding->employee->employee_code }}
            — {{ $offboarding->employee->name }}
          </dd>

          <dt>Type</dt>
          <dd>{{ $offboarding->type ? ucfirst($offboarding->type) : '—' }}</dd>

          <dt>Reason</dt>
          <dd>{{ $offboarding->reason ?: '—' }}</dd>

          <dt>Schedule</dt>
          <dd>
            {{ $offboarding->scheduled_at ? $offboarding->scheduled_at->format('d/m/Y h:i a') : '—' }}
          </dd>
        </dl>
      </div>

    <div class="card border-0 shadow-sm">
  <div class="card-body">
    <h6 class="fw-bold mb-3">Set Offboarding Appointment</h6>

    {{-- Schedule --}}
    <form action="{{ route('offboarding.schedule', $offboarding->id) }}" method="POST" class="d-flex align-items-center gap-2">
      @csrf
      @method('PATCH')
      <input type="datetime-local" name="effective_date" class="form-control" required>
      <button class="btn btn-primary">
        <i class="bi bi-calendar-event me-1"></i> Schedule
      </button>
    </form>

    <div class="mt-3 d-flex flex-wrap gap-2">
      {{-- Pending Clearance --}}
      <form action="{{ route('offboarding.pendingClearance', $offboarding->id) }}" method="POST">
        @csrf
        @method('PATCH')
        <button class="btn btn-outline-secondary">
          <i class="bi bi-hourglass-split me-1"></i> Pending Clearance
        </button>
      </form>

      {{-- Complete --}}
      <form action="{{ route('offboarding.complete', $offboarding->id) }}" method="POST">
        @csrf
        @method('PATCH')
        <button class="btn btn-success">
          <i class="bi bi-check2-circle me-1"></i> Complete
        </button>
      </form>

      {{-- Cancel --}}
      <form action="{{ route('offboarding.cancel', $offboarding->id) }}" method="POST">
        @csrf
        @method('PATCH')
        <button class="btn btn-outline-danger">
          <i class="bi bi-x-circle me-1"></i> Cancel
        </button>
      </form>
    </div>
  </div>
</div>

    </div>
  </div>

  {{-- bottom sticky toolbar (quick actions) --}}
  <div class="ofb-toolbar d-flex align-items-center gap-2">
    <strong class="me-auto">Offboarding #{{ $offboarding->id }}</strong>
    <a class="btn btn-light btn-sm" href="{{ route('offboarding.index') }}">
      <i class="bi bi-list-ul"></i> All Offboarding
    </a>
    <a class="btn btn-light btn-sm" href="{{ route('employees.index') }}">
      <i class="bi bi-people"></i> Employee List
    </a>
  </div>

</div>
@endsection
