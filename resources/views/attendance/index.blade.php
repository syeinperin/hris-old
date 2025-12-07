@extends('layouts.app')

@section('page_title', 'Attendance List')


@php
  use Carbon\Carbon;
  use Illuminate\Support\Str;

  $startDate = $startDate ?? Carbon::now()->startOfMonth()->toDateString();
@endphp

@push('styles')
<style>
  .table-responsive {
    width: 100%;
    overflow-x: visible !important;
    overflow-y: visible !important;
  }

  .table {
    width: 100%;
    border-collapse: collapse;
    table-layout: auto;
    font-size: 0.9rem;
  }

  .table th,
  .table td {
    padding: 0.65rem 0.75rem;
    vertical-align: middle;
    text-align: left;
    white-space: normal;
    word-break: break-word;
    border-top: 1px solid #e9ecef;
    line-height: 1.35;
  }

  .table th {
    font-weight: 600;
    font-size: 0.9rem;
    background-color: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
  }

  .table th:first-child,
  .table td:first-child {
    width: 45px;
    text-align: center;
  }

  .status-badge {
    font-weight: 600;
    padding: 0.4rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.8rem;
  }

  .table-sticky thead th {
    position: sticky;
    top: 0;
    z-index: 5;
    background: #fff;
  }

  .pagination {
    justify-content: flex-end;
    margin: 0.75rem 1rem 0.25rem;
  }


</style>
@endpush

@section('content')
<div class="container-fluid">

  <div class="card shadow-sm mb-4">
    <div class="card-header d-flex justify-content-between align-items-center bg-white">
      <h4 class="mb-0">
        <i class="bi bi-clock-history me-2"></i> Attendance Records
      </h4>
      <div class="d-flex align-items-center gap-2">
        <a href="{{ route('payroll.calendar') }}" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-calendar-event me-1"></i> Calendar
        </a>
        <a href="{{ route('holidays.index') }}" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-flag me-1"></i> Holidays
        </a>
      </div>
    </div>

    <div class="card-body">

      {{-- Filters --}}
      <x-search-bar
        :action="route('attendance.index')"
        placeholder="Search code or name…"
        :filters="[
          'status' => [
            '' => 'All Status',
            'On Time' => 'On Time',
            'Late' => 'Late',
            'Absent' => 'Absent',
            'Suspended' => 'Suspended',
          ],
        ]"
        :showDateRange="true"
        startName="start_date"
        endName="end_date"
      />

      {{-- Table --}}
      <div class="table-responsive mt-3">
        <table class="table table-hover align-middle table-sticky mb-0">
          <thead class="table-light">
            <tr>
              <th><input type="checkbox" id="selectAll"></th>
              <th>Employee Code</th>
              <th>Employee Name</th>
              <th>Time In</th>
              <th>Time Out</th>
              <th>Date</th>
              <th>Status</th>
              <th>Late (hr)</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            @forelse($attendances as $row)
              @php
                $status = (string) ($row['status'] ?? '');
                $badge = match(true) {
                  str_starts_with($status, 'On Time') => 'success',
                  str_starts_with($status, 'Late') => 'warning',
                  str_starts_with($status, 'Absent') => 'secondary',
                  str_starts_with($status, 'Suspended') => 'dark',
                  str_starts_with($status, 'On Leave') => 'info',
                  str_starts_with($status, 'Undertime') => 'danger',
                  default => 'secondary',
                };
              @endphp
              <tr>
                <td>
                  @if(!empty($row['id']))
                    <input type="checkbox" name="selected[]" value="{{ $row['id'] }}">
                  @endif
                </td>
                <td class="fw-semibold">{{ $row['employee_code'] }}</td>
                <td>{{ $row['employee_name'] }}</td>
                <td>{{ $row['time_in'] ?? '—' }}</td>
                <td>{{ $row['time_out'] ?? '—' }}</td>
                <td>{{ \Carbon\Carbon::parse($row['date'])->format('D, M d, Y') }}</td>
                <td>
                  <span class="badge status-badge bg-{{ $badge }}">{{ $status }}</span>
                </td>
                <td>{{ $row['late_hours'] !== '' ? number_format((float)$row['late_hours'], 2) : '—' }}</td>
                <td class="text-nowrap">
                  @if(!empty($row['id']))
                    <button class="btn btn-sm btn-outline-secondary edit-btn"
                            data-id="{{ $row['id'] }}"
                            data-emp="{{ $row['employee_name'] }}">
                      <i class="bi bi-pencil-square"></i>
                    </button>
                  @endif
                  <a href="{{ route('attendance.show', [
                        'attendance' => $row['employee_id'],
                        'month' => \Illuminate\Support\Str::substr($startDate, 0, 7)
                      ]) }}"
                     class="btn btn-sm btn-primary"
                     title="View month">
                    <i class="bi bi-eye"></i>
                  </a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="9" class="text-center text-muted py-4">
                  <i class="bi bi-inbox me-1"></i> No attendance records found.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      {{-- Pagination --}}
      <div class="d-flex justify-content-between align-items-center mt-3">
        <small class="text-muted">
          Showing {{ $attendances->firstItem() }}–{{ $attendances->lastItem() }}
          of {{ $attendances->total() }}
        </small>
        {{ $attendances->withQueryString()->links('pagination::bootstrap-5') }}
      </div>

    </div>
  </div>
</div>

<!-- Edit Attendance Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" id="editForm" class="modal-content">
      @csrf
      @method('PUT')
      <div class="modal-header">
        <h5 class="modal-title">Edit Attendance</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label fw-semibold">Employee</label>
          <input type="text" id="empName" class="form-control" readonly>
        </div>
        <div class="mb-3">
          <label class="form-label">Time In</label>
          <input type="datetime-local" name="time_in" id="timeIn" class="form-control">
        </div>
        <div class="mb-3">
          <label class="form-label">Time Out</label>
          <input type="datetime-local" name="time_out" id="timeOut" class="form-control">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')

<script>
  const selectAll = document.getElementById('selectAll');
  if (selectAll) {
    selectAll.addEventListener('change', function(){
      document.querySelectorAll('tbody input[type="checkbox"]').forEach(cb => {
        cb.checked = this.checked;
      });
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    const editModal = new bootstrap.Modal(document.getElementById('editModal'));
    const editForm = document.getElementById('editForm');

    document.querySelectorAll('.edit-btn').forEach(btn => {
      btn.addEventListener('click', async () => {
        const id = btn.dataset.id;
        const emp = btn.dataset.emp;
        document.getElementById('empName').value = emp;

        const res = await fetch(`/attendance/${id}/edit`);
        const data = await res.json();

        document.getElementById('timeIn').value = data.time_in || '';
        document.getElementById('timeOut').value = data.time_out || '';
        editForm.action = `/attendance/${id}`;
        editModal.show();
      });
    });
  });
</script>
@endpush
