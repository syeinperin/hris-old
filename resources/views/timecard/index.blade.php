{{-- resources/views/timecard/index.blade.php --}}
@extends('layouts.app')

@section('page_title', 'My Time Card')

@push('styles')
<style>
  /* ASIATEX-themed status badges */
  .badge-theme {
    font-weight: 600;
    letter-spacing: .2px;
    padding: .4rem .75rem;
    border-radius: 999px;
    line-height: 1;
    display: inline-flex;
    align-items: center;
    text-transform: capitalize;
    font-size: .9rem;
  }

  /* Custom AsiaTex palette */
  .bg-asiatex-primary   { background-color: #26264e !important; color: #fff !important; }
  .bg-asiatex-warning   { background-color: #f4b400 !important; color: #212529 !important; }
  .bg-asiatex-danger    { background-color: #dc3545 !important; color: #fff !important; }
  .bg-asiatex-info      { background-color: #3a3a84 !important; color: #fff !important; }
  .bg-asiatex-secondary { background-color: #6c757d !important; color: #fff !important; }

  .table-timecard td,
  .table-timecard th {
    vertical-align: middle;
  }
  @media (max-width: 768px) {
    .timecard-actions {
      gap: .5rem;
      flex-direction: column;
      align-items: stretch !important;
    }
  }
</style>
@endpush

@php
  /**
   * AsiaTex-themed badge mapping
   */
  function tc_badge(string $status): string {
      $s = strtolower($status);
      return match (true) {
          str_contains($s, 'present'),
          str_contains($s, 'on time')      => 'bg-asiatex-primary',
          str_contains($s, 'in-progress'),
          str_contains($s, 'late')         => 'bg-asiatex-warning',
          str_contains($s, 'absent'),
          str_contains($s, 'violation')    => 'bg-asiatex-danger',
          str_contains($s, 'leave')        => 'bg-asiatex-info',
          str_contains($s, 'suspend')      => 'bg-asiatex-secondary',
          str_contains($s, 'undertime')        => 'bg-asiatex-danger',

          default                          => 'bg-asiatex-secondary',
      };
  }
@endphp

@section('content')
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">
      <i class="bi bi-journal-check me-2"></i> My Time Card
    </h4>

    <form class="d-flex align-items-center gap-2 timecard-actions"
          action="{{ route('employee.timecard.index') }}"
          method="get">
      <div class="input-group">
        <span class="input-group-text"><i class="bi bi-calendar3"></i></span>
        <input type="date" name="start" class="form-control" value="{{ $start }}">
      </div>
      <div class="input-group">
        <span class="input-group-text"><i class="bi bi-calendar3"></i></span>
        <input type="date" name="end" class="form-control" value="{{ $end }}">
      </div>
      <button type="submit" class="btn btn-primary">
        <i class="bi bi-search me-1"></i> Apply
      </button>
    </form>
  </div>

  <div class="card shadow-sm">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover table-timecard mb-0">
          <thead class="table-light">
            <tr>
              <th style="width: 28%">Date</th>
              <th style="width: 22%">Time In</th>
              <th style="width: 22%">Time Out</th>
              <th style="width: 12%">Hours</th>
              <th style="width: 16%">Status</th>
            </tr>
          </thead>
          <tbody>
            @forelse($rows as $r)
              <tr>
                <td>{{ \Carbon\Carbon::parse($r['date'])->format('D, M d, Y') }}</td>
                <td>{{ $r['time_in'] }}</td>
                <td>{{ $r['time_out'] }}</td>
                <td>{{ $r['hours'] }}</td>
                <td>
                  <span class="badge badge-theme {{ tc_badge($r['status']) }}">
                    {{ $r['status'] }}
                  </span>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="text-center py-5 text-muted">
                  No records found for the selected dates.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection
