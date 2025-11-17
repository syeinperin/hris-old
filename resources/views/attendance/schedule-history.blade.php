@extends('layouts.app')

@section('page_title', 'Schedule History')

@section('content')
<div class="container-fluid py-4">

  {{-- ── Page Header ───────────────────────────── --}}
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2><i class="bi bi-clock-history me-2"></i> Schedule History</h2>

    <div class="d-flex gap-2">
      <a href="{{ route('schedule.index') }}" class="btn btn-outline-dark">
        <i class="bi bi-arrow-left me-1"></i> Back to Schedule
      </a>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  {{-- ── Schedule History Table ─────────────────── --}}
  <div class="card shadow-sm">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-striped align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>Employee</th>
              <th>Shift</th>
              <th>Effective From</th>
              <th>Effective To</th>
              <th>Department</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($assignments as $i => $a)
              <tr>
                <td>{{ $assignments->firstItem() + $i }}</td>
                <td>{{ $a->employee->name ?? '—' }}</td>
                <td>{{ $a->schedule->name ?? '—' }}</td>
                <td>{{ \Carbon\Carbon::parse($a->effective_from)->format('M d, Y') }}</td>
                <td>{{ $a->effective_to ? \Carbon\Carbon::parse($a->effective_to)->format('M d, Y') : '—' }}</td>
               <td>{{ $a->employee->department->name ?? '—' }}</td>
                <td class="text-end">
                  <button class="btn btn-sm btn-warning editBtn"
                          data-id="{{ $a->id }}"
                          data-employee="{{ $a->employee->name }}"
                          data-schedule="{{ $a->schedule_id }}"
                          data-from="{{ $a->effective_from }}"
                          data-to="{{ $a->effective_to }}"
                          data-notes="{{ $a->notes ?? '' }}">
                    <i class="bi bi-pencil"></i>
                  </button>
                  <form action="{{ route('schedule.history.destroy', $a->id) }}"
                        method="POST" class="d-inline"
                        onsubmit="return confirm('Delete this schedule record?');">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-danger">
                      <i class="bi bi-trash"></i>
                    </button>
                  </form>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center text-muted py-4">
                  No schedule assignments recorded yet.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    <div class="card-footer">
      {{ $assignments->links() }}
    </div>
  </div>
</div>

{{-- ─────────────────────────────── CREATE MODAL ─────────────────────────────── --}}
<div class="modal fade" id="createModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <form action="{{ route('schedule.history.store') }}" method="POST" class="modal-content">
      @csrf
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-plus-circle me-1"></i> Add Schedule Record</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        {{-- Department Selection --}}
        <div class="row mb-3">
          <div class="col-md-6">
            <label class="form-label">Select Department</label>
            <select id="deptSelect" class="form-select">
              <option value="">All Departments</option>
              @foreach(\App\Models\Department::orderBy('name')->get() as $d)
                <option value="{{ $d->id }}">{{ $d->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6 d-flex align-items-end">
            <button type="button" id="selectAllBtn" class="btn btn-sm btn-outline-primary me-2">
              <i class="bi bi-check2-all"></i> Select All Employees
            </button>
            <button type="button" id="clearAllBtn" class="btn btn-sm btn-outline-danger">
              <i class="bi bi-x-circle"></i> Clear All
            </button>
          </div>
        </div>

        {{-- Employee Multi-Select --}}
        <div class="mb-3">
          <label class="form-label">Select Employees</label>
          <select name="employee_id[]" id="employeeSelect" class="form-select choices-multi" multiple required>
            @foreach(\App\Models\Employee::orderBy('name')->get() as $emp)
              <option value="{{ $emp->id }}" data-dept="{{ $emp->department_id }}">
                {{ $emp->name }} ({{ $emp->employee_code }})
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

        {{-- Effectivity Options --}}
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

{{-- ─────────────────────────────── EDIT MODAL ─────────────────────────────── --}}
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" class="modal-content" id="editForm">
      @csrf @method('PUT')
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-pencil me-1"></i> Edit Schedule Record</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Employee</label>
          <input type="text" id="edit-employee" class="form-control" disabled>
        </div>
        <div class="mb-3">
          <label class="form-label">Shift</label>
          <select name="schedule_id" id="edit-schedule" class="form-select" required>
            @foreach(\App\Models\Schedule::orderBy('time_in')->get() as $s)
              <option value="{{ $s->id }}">{{ $s->name }} ({{ \Carbon\Carbon::parse($s->time_in)->format('H:i') }}–{{ \Carbon\Carbon::parse($s->time_out)->format('H:i') }})</option>
            @endforeach
          </select>
        </div>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Effective From</label>
            <input type="date" name="effective_from" id="edit-from" class="form-control" required>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Effective To</label>
            <input type="date" name="effective_to" id="edit-to" class="form-control">
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label">Notes</label>
          <textarea name="notes" id="edit-notes" class="form-control" rows="2"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary">Update Record</button>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  const editModal = new bootstrap.Modal(document.getElementById('editModal'));
  const form = document.getElementById('editForm');

  // Open edit modal
  document.querySelectorAll('.editBtn').forEach(btn => {
    btn.addEventListener('click', () => {
      const id = btn.dataset.id;
      form.action = `/schedule/history/${id}`;
      document.getElementById('edit-employee').value = btn.dataset.employee;
      document.getElementById('edit-schedule').value = btn.dataset.schedule;
      document.getElementById('edit-from').value = btn.dataset.from;
      document.getElementById('edit-to').value = btn.dataset.to || '';
      document.getElementById('edit-notes').value = btn.dataset.notes || '';
      editModal.show();
    });
  });

  // Effectivity logic
  const todayOption = document.getElementById('todayOption');
  const weekOption = document.getElementById('weekOption');
  const effFrom = document.getElementById('effective_from');
  const effTo = document.getElementById('effective_to');

  todayOption.addEventListener('change', () => effTo.value = '');
  weekOption.addEventListener('change', () => {
    if (effFrom.value) {
      const date = new Date(effFrom.value);
      date.setDate(date.getDate() + 6);
      effTo.value = date.toISOString().split('T')[0];
    }
  });

  effFrom.addEventListener('change', () => {
    if (weekOption.checked) {
      const date = new Date(effFrom.value);
      date.setDate(date.getDate() + 6);
      effTo.value = date.toISOString().split('T')[0];
    }
  });

  // Select by Department
  const deptSelect = document.getElementById('deptSelect');
  const employeeSelect = document.getElementById('employeeSelect');
  const choices = new Choices(employeeSelect, { removeItemButton: true, shouldSort: true });

  deptSelect.addEventListener('change', () => {
    const selectedDept = deptSelect.value;
    const allOptions = Array.from(employeeSelect.options);
    choices.clearStore();

    allOptions.forEach(opt => {
      if (!selectedDept || opt.dataset.dept === selectedDept) {
        choices.setChoices([{ value: opt.value, label: opt.textContent, selected: false }], 'value', 'label', false);
      }
    });
  });

  document.getElementById('selectAllBtn').addEventListener('click', () => {
    choices.setChoiceByValue([...employeeSelect.options].map(o => o.value));
  });

  document.getElementById('clearAllBtn').addEventListener('click', () => {
    choices.removeActiveItems();
  });
});
</script>
@endpush
