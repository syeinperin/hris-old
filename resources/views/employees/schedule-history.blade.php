@extends('layouts.app')

@section('page_title', 'My Schedule History')

@section('content')
<div class="container-fluid py-4">
  <h3 class="mb-4">
    <i class="bi bi-clock-history me-2"></i> My Schedule History
  </h3>

  {{-- 🚫 No Employee Record --}}
  @if(!empty($noEmployeeRecord))
    <div class="alert alert-danger">
      <i class="bi bi-exclamation-octagon me-2"></i>
      Your account is not linked to any employee record. Please contact HR.
    </div>

  {{-- 📋 No Records --}}
  @elseif($rows->isEmpty())
    <div class="alert alert-info">
      <i class="bi bi-info-circle me-2"></i>
      You currently have no previous schedule history.
    </div>

  {{-- 🗓 History Table --}}
  @else
    <div class="card shadow-sm">
      <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0 text-primary">
          <i class="bi bi-calendar2-week me-2"></i>Schedule Records
        </h5>
        <a href="{{ route('employee.schedule') }}" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-arrow-left"></i> Back to My Schedule
        </a>
      </div>

      <div class="card-body table-responsive">
        <table class="table table-bordered table-hover align-middle">
          <thead class="table-light">
            <tr>
              <th>Date From</th>
              <th>Date To</th>
              <th>Shift</th>
              <th>Time In</th>
              <th>Time Out</th>
              <th>Rest Day</th>
            </tr>
          </thead>
          <tbody>
            @foreach($rows as $row)
              <tr>
                <td>{{ \Carbon\Carbon::parse($row->effective_from)->format('M d, Y (D)') }}</td>
                <td>
                  @if($row->effective_to)
                    {{ \Carbon\Carbon::parse($row->effective_to)->format('M d, Y (D)') }}
                  @else
                    <span class="text-success fw-semibold">Ongoing</span>
                  @endif
                </td>
                <td>{{ $row->schedule->name ?? '—' }}</td>
                <td>{{ $row->schedule?->time_in ? \Carbon\Carbon::parse($row->schedule->time_in)->format('h:i A') : '—' }}</td>
                <td>{{ $row->schedule?->time_out ? \Carbon\Carbon::parse($row->schedule->time_out)->format('h:i A') : '—' }}</td>
                <td>{{ $row->schedule->rest_day ?? '—' }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  @endif
</div>
@endsection
