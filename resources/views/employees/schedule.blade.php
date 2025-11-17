@extends('layouts.app')

@section('page_title', 'My Schedule')

@push('styles')
<style>
  :root {
    --asiatex-primary: #3a3a84;
    --asiatex-light: #ececff;
  }

  .badge-status {
    font-weight: 600;
    padding: 0.4em 0.7em;
    border-radius: 20px;
  }
  .badge-active {
    background-color: var(--asiatex-primary);
    color: #fff;
  }
  .badge-future {
    background-color: var(--asiatex-light);
    color: var(--asiatex-primary);
    border: 1px solid var(--asiatex-primary);
  }
  .badge-expired {
    background-color: #6c757d;
    color: #fff;
  }

  .table th {
    width: 35%;
    color: #555;
    font-weight: 600;
  }
  .table td {
    font-weight: 500;
    color: #333;
  }

  .btn-theme {
    border: 1px solid var(--asiatex-primary);
    color: var(--asiatex-primary);
    background-color: #fff;
    transition: 0.2s ease;
  }
  .btn-theme:hover {
    background-color: var(--asiatex-primary);
    color: #fff;
  }
</style>
@endpush

@section('content')
<div class="container-fluid">

  {{-- Header --}}
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
      <i class="bi bi-calendar-week me-2 text-primary"></i> My Schedule
    </h1>
  </div>

  {{-- Flash Alerts --}}
  @if(!empty($noEmployeeRecord))
    <div class="alert alert-danger shadow-sm">
      <i class="bi bi-exclamation-octagon me-2"></i>
      Your account is not linked to any employee record. Please contact HR.
    </div>
  @elseif(empty($schedule))
    <div class="alert alert-info shadow-sm">
      <i class="bi bi-info-circle me-2"></i>
      You currently have no assigned schedule.
    </div>
  @else

  {{-- Current Schedule Card --}}
  <div class="card shadow-sm border-0">
    <div class="card-header bg-white border-0 pb-0">
      <h5 class="fw-bold text-dark mb-2">
        <i class="bi bi-clock-history me-2 text-primary"></i> Current Shift Details
      </h5>
    </div>

    <div class="card-body">
      <table class="table table-bordered align-middle mb-3 w-100">
        <tbody>
          <tr>
            <th>Shift</th>
            <td>{{ $schedule->name ?? '—' }}</td>
          </tr>
          <tr>
            <th>Time In</th>
            <td>{{ \Carbon\Carbon::parse($schedule->time_in)->format('h:i A') }}</td>
          </tr>
          <tr>
            <th>Time Out</th>
            <td>{{ \Carbon\Carbon::parse($schedule->time_out)->format('h:i A') }}</td>
          </tr>
          <tr>
            <th>Rest Day</th>
            <td>{{ $schedule->rest_day ?? '—' }}</td>
          </tr>
          @if($effectiveFrom)
          <tr>
            <th>Effective From</th>
            <td>{{ $effectiveFrom->format('M d, Y (D)') }}</td>
          </tr>
          @endif
          @if($effectiveTo)
          <tr>
            <th>Effective To</th>
            <td>{{ $effectiveTo->format('M d, Y (D)') }}</td>
          </tr>
          @endif
          <tr>
            <th>Status</th>
            <td>
              @php
                $statusClass = match($status) {
                    'active' => 'badge-active',
                    'future' => 'badge-future',
                    'expired' => 'badge-expired',
                    default => 'badge-secondary'
                };
              @endphp
              <span class="badge-status {{ $statusClass }}">{{ ucfirst($status) }}</span>
            </td>
          </tr>
        </tbody>
      </table>

      <div class="text-end">
        <a href="{{ route('employee.schedule.history') }}" class="btn btn-theme btn-sm">
          <i class="bi bi-clock-history me-1"></i> View Schedule History
        </a>
      </div>
    </div>
  </div>
  @endif
</div>
@endsection
