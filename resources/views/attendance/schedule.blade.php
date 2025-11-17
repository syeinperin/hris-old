{{-- resources/views/attendance/schedule.blade.php --}}
@extends('layouts.app')

@section('page_title','Schedules')

@section('content')
<div class="container-fluid py-4">

  {{-- Flash Alerts --}}
  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
  @endif

  {{-- Page Header --}}
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">
      <i class="bi bi-calendar-week me-2"></i> Schedules
    </h2>
    <div class="d-flex gap-2">
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#assignModal">
        <i class="bi bi-plus-circle me-1"></i> Assign Schedule
      </button>
      <a href="{{ route('schedule.history') }}" class="btn btn-outline-primary">
        <i class="bi bi-clock-history me-1"></i> View Schedule History
      </a>
    </div>
  </div>

  {{-- Add New Shift --}}
  <div class="card shadow-sm mb-4">
    <div class="card-header bg-white d-flex align-items-center justify-content-between">
      <h4 class="mb-0"><i class="bi bi-clock-history me-2"></i> Add New Shift</h4>

      {{-- Bulk rest day --}}
      <form action="{{ route('schedule.restday.all') }}" method="POST" class="d-flex align-items-center gap-2">
        @csrf
        <label class="me-1 mb-0">Set rest day for all:</label>
        <select name="day" class="form-select form-select-sm" style="width:auto">
          @foreach(['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'] as $day)
            <option value="{{ $day }}" {{ $day==='Sunday' ? 'selected' : '' }}>{{ $day }}</option>
          @endforeach
        </select>
        <button class="btn btn-sm btn-outline-primary" onclick="return confirm('Apply this rest day to ALL schedules?')">
          Apply
        </button>
      </form>
    </div>

    <div class="card-body">
      @if($errors->any())
        <div class="alert alert-danger">
          <ul class="mb-0">
            @foreach($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <form action="{{ route('schedule.store') }}" method="POST" class="row g-3">
        @csrf
        <div class="col-md-3">
          <label class="form-label">Name <small>(no spaces)</small></label>
          <input type="text" name="name" class="form-control" placeholder="e.g., Shift-One" required>
        </div>
        <div class="col-md-2">
          <label class="form-label">Time In</label>
          <input type="time" name="time_in" class="form-control" required>
        </div>
        <div class="col-md-2">
          <label class="form-label">Time Out</label>
          <input type="time" name="time_out" class="form-control" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Rest Day</label>
          <select name="rest_day" class="form-select">
            <option value="">— none —</option>
            @foreach(['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'] as $day)
              <option value="{{ $day }}">{{ $day }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2 d-grid">
          <button type="submit" class="btn btn-primary">Save Shift</button>
        </div>
      </form>
    </div>
  </div>

  {{-- Existing Shifts --}}
  <div class="card shadow-sm">
    <div class="card-header bg-white">
      <h4 class="mb-0"><i class="bi bi-list-check me-2"></i> Existing Shifts</h4>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>Shift</th>
              <th>In</th>
              <th>Out</th>
              <th>Rest Day</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($schedules as $i => $sched)
              <tr>
                <td>{{ $schedules->firstItem() + $i }}</td>
                <td>{{ $sched->name }}</td>
                <td>{{ \Carbon\Carbon::parse($sched->time_in)->format('H:i') }}</td>
                <td>{{ \Carbon\Carbon::parse($sched->time_out)->format('H:i') }}</td>
                <td>{{ $sched->rest_day ?? '—' }}</td>
                <td class="text-end">
                  <button type="button" class="btn btn-sm btn-warning edit-button"
                    data-id="{{ $sched->id }}"
                    data-name="{{ $sched->name }}"
                    data-time_in="{{ \Carbon\Carbon::parse($sched->time_in)->format('H:i') }}"
                    data-time_out="{{ \Carbon\Carbon::parse($sched->time_out)->format('H:i') }}"
                    data-rest_day="{{ $sched->rest_day }}">
                    Edit
                  </button>
                  <form action="{{ route('schedule.destroy', $sched) }}" method="POST" class="d-inline" 
                        onsubmit="return confirm('Delete this shift?');">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-danger">Delete</button>
                  </form>
                </td>
              </tr>
            @empty
              <tr><td colspan="6" class="text-center text-muted py-4">No shifts defined yet.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    <div class="card-footer">
      {{ $schedules->links() }}
    </div>
  </div>
</div>

{{-- EDIT MODAL --}}
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form id="editForm" method="POST" class="modal-content">
      @csrf @method('PUT')
      <div class="modal-header">
        <h5 class="modal-title">Edit Schedule</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Shift Name</label>
          <input type="text" name="name" id="edit_name" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Time In</label>
          <input type="time" name="time_in" id="edit_time_in" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Time Out</label>
          <input type="time" name="time_out" id="edit_time_out" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Rest Day</label>
          <select name="rest_day" id="edit_rest_day" class="form-select">
            <option value="">— none —</option>
            @foreach(['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'] as $day)
              <option value="{{ $day }}">{{ $day }}</option>
            @endforeach
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>
{{-- ─────────────────────────────── ASSIGN MODAL ─────────────────────────────── --}}
<div class="modal fade" id="assignModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <form action="{{ route('attendance.schedule-history') }}" method="POST" class="modal-content">
      @csrf
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-plus-circle me-1"></i> Assign Schedule</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        {{-- Department Selection --}}
        @php
          $user = auth()->user();
          $departments = $user->hasRole('supervisor')
              ? $user->supervisedDepartments()->orderBy('name')->get()
              : \App\Models\Department::orderBy('name')->get();
        @endphp

        @if($user->hasRole('supervisor') && $departments->count() === 1)
          <input type="hidden" id="deptSelect" value="{{ $departments->first()->id }}">
          <div class="mb-3">
            <label class="form-label">Department</label>
            <input type="text" class="form-control" value="{{ $departments->first()->name }}" readonly>
          </div>
        @else
          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label">Select Department</label>
              <select id="deptSelect" class="form-select">
                <option value="">All Departments</option>
                @foreach($departments as $d)
                  <option value="{{ $d->id }}">{{ $d->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-6 d-flex align-items-end">
              <button type="button" id="selectAllBtn" class="btn btn-sm btn-outline-primary me-2">
                <i class="bi bi-check2-all"></i> Select All
              </button>
              <button type="button" id="clearAllBtn" class="btn btn-sm btn-outline-danger">
                <i class="bi bi-x-circle"></i> Clear All
              </button>
            </div>
          </div>
        @endif

        {{-- Employee Multi-Select --}}
        <div class="mb-3">
          <label class="form-label">Select Employees</label>
          <select name="employee_ids[]" id="employeeSelect" class="form-select" multiple required>
@php
  $employees = \App\Models\Employee::nonAdmin()
      ->with('department')
      ->when($user->hasRole('supervisor'), function ($q) use ($user) {
          $deptIds = $user->supervisedDepartments()->pluck('departments.id');
          $q->whereIn('department_id', $deptIds);
      })
      ->whereHas('user', fn($q) => $q->where('id', '!=', auth()->id())) 
      ->orderBy('name')
      ->get();
@endphp

            @foreach($employees as $emp)
              <option value="{{ $emp->id }}" data-dept="{{ $emp->department_id }}">
                {{ $emp->name }} ({{ $emp->employee_code }}) — {{ $emp->department->name ?? 'No Dept' }}
              </option>
            @endforeach
          </select>
        </div>

        {{-- Shift Selection --}}
        <div class="mb-3">
          <label class="form-label">Select Shift</label>
          <select name="schedule_id" class="form-select" required>
            <option value="">Select Shift</option>
            @foreach(\App\Models\Schedule::orderBy('time_in')->get() as $s)
              <option value="{{ $s->id }}">{{ $s->name }} ({{ \Carbon\Carbon::parse($s->time_in)->format('H:i') }}–{{ \Carbon\Carbon::parse($s->time_out)->format('H:i') }})</option>
            @endforeach
          </select>
        </div>

        {{-- Effectivity --}}
        <div class="mb-3">
          <label class="form-label d-block">Effectivity</label>
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="effect_type" id="todayOption" value="day" checked>
            <label class="form-check-label" for="todayOption">This Day Only</label>
          </div>
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="effect_type" id="weekOption" value="week">
            <label class="form-check-label" for="weekOption">Whole Week</label>
          </div>
        </div>

        {{-- Date Range --}}
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Effective From</label>
            <input type="date" name="effective_from" id="effective_from" class="form-control" required>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Effective To</label>
            <input type="date" name="effective_to" id="effective_to" class="form-control">
          </div>
        </div>

        {{-- Notes --}}
        <div class="mb-3">
          <label class="form-label">Notes</label>
          <textarea name="notes" class="form-control" rows="2" placeholder="Optional remarks..."></textarea>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary">Save Record</button>
      </div>
    </form>
  </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
  // ==== EDIT MODAL ====
  const editButtons = document.querySelectorAll('.edit-button');
  const editModal = new bootstrap.Modal(document.getElementById('editModal'));
  const editForm = document.getElementById('editForm');

  editButtons.forEach(button => {
    button.addEventListener('click', function() {
      const id = this.dataset.id;
      document.getElementById('edit_name').value = this.dataset.name;
      document.getElementById('edit_time_in').value = this.dataset.time_in;
      document.getElementById('edit_time_out').value = this.dataset.time_out;
      document.getElementById('edit_rest_day').value = this.dataset.rest_day || '';
      editForm.action = `/schedule/${id}`;
      editModal.show();
    });
  });

  // ==== ASSIGN MODAL ====
  const effFrom = document.getElementById('effective_from');
  const effTo = document.getElementById('effective_to');
  const todayOption = document.getElementById('todayOption');
  const weekOption = document.getElementById('weekOption');
  const deptSelect = document.getElementById('deptSelect');
  const employeeSelect = document.getElementById('employeeSelect');

  if (employeeSelect) {
    const choices = new Choices(employeeSelect, { removeItemButton: true, searchEnabled: true, shouldSort: true });

    todayOption?.addEventListener('change', () => effTo.value = '');
    weekOption?.addEventListener('change', () => {
      if (effFrom.value) {
        const date = new Date(effFrom.value);
        date.setDate(date.getDate() + 6);
        effTo.value = date.toISOString().split('T')[0];
      }
    });

    effFrom?.addEventListener('change', () => {
      if (weekOption.checked) {
        const date = new Date(effFrom.value);
        date.setDate(date.getDate() + 6);
        effTo.value = date.toISOString().split('T')[0];
      }
    });

    deptSelect?.addEventListener('change', () => {
      const selectedDept = deptSelect.value;
      const allOptions = Array.from(employeeSelect.options);
      choices.clearStore();
      allOptions.forEach(opt => {
        if (!selectedDept || opt.dataset.dept === selectedDept) {
          choices.setChoices([{ value: opt.value, label: opt.textContent, selected: false }], 'value', 'label', false);
        }
      });
    });

    document.getElementById('selectAllBtn')?.addEventListener('click', () => {
      const all = [...employeeSelect.options].map(o => o.value);
      choices.setChoiceByValue(all);
    });

    document.getElementById('clearAllBtn')?.addEventListener('click', () => {
      choices.removeActiveItems();
    });
  }
});
</script>
@endpush
