@extends('layouts.app')

@section('page_title','Contracts Ending')

@push('styles')
<style>
  .page-header {
    background: #fff;
    border: 1px solid #eee;
    border-radius: 10px;
    padding: 18px 22px;
    margin-bottom: 24px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.03);
  }

  /* Remove scrollbars completely */
  .table-container {
    width: 100%;
    overflow: visible !important;
  }

  /* Make table auto-fit inside container */
  .table-ending {
    width: 100%;
    table-layout: fixed;
    border-radius: 10px;
  }

  /* Shrink columns nicely */
  .table-ending th,
  .table-ending td {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  /* Force dropdown to appear above table borders */
.table-ending {
    position: relative;
    overflow: visible !important;
}

.table-ending td,
.table-ending th {
    overflow: visible !important;
}

/* Fix dropdown stacking */
.dropdown-menu {
    position: absolute !important;
    z-index: 9999 !important;
    transform: translate3d(0, 0, 0) !important;
}


  /* Column widths */
  .table-ending th:nth-child(1) { width: 4%; }   /* # */
  .table-ending th:nth-child(2) { width: 10%; }  /* Code */
  .table-ending th:nth-child(3) { width: 18%; }  /* Name */
  .table-ending th:nth-child(4) { width: 15%; }  /* Department */
  .table-ending th:nth-child(5) { width: 12%; }  /* Type */
  .table-ending th:nth-child(6) { width: 12%; }  /* Start Date */
  .table-ending th:nth-child(7) { width: 12%; }  /* End Date */
  .table-ending th:nth-child(8) { width: 12%; }  /* Actions */

  .dropdown-menu { z-index: 1060 !important; }

  .badge { 
    font-size: 0.75rem !important; 
    padding: 0.25em 0.55em !important;
  }

  body { overflow-x: hidden !important; }
</style>
@endpush


@section('content')
<div class="container-fluid pb-4">

  {{-- ===== HEADER ===== --}}
  <div class="page-header d-flex flex-wrap justify-content-between align-items-center">
    <div class="mb-2 mb-md-0">
      <h3 class="mb-0 fw-semibold">
        <i class="bi bi-clock-history me-2 text-primary"></i> Contracts Ending
      </h3>
      <div class="text-muted small">
        Employees with contracts ending soon or probationary staff requiring action.
      </div>
    </div>
    <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary">
      <i class="bi bi-arrow-left me-1"></i> All Employees
    </a>
  </div>

  {{-- ===== FILTERS ===== --}}
  <form method="GET" action="{{ route('employees.endings') }}" class="row g-2 align-items-center mb-4">
    <div class="col-md-4">
      <select name="department_id" class="form-select shadow-sm">
        <option value="">All Departments</option>
        @foreach($departments as $id => $name)
          <option value="{{ $id }}" @selected(request('department_id')==$id)>{{ $name }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-4">
      <select name="employment_type" class="form-select shadow-sm">
        @foreach($employmentTypes as $key => $label)
          <option value="{{ $key }}" @selected(request('employment_type')===$key)>{{ $label }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-4 d-flex justify-content-md-end gap-2">
      <button type="submit" class="btn btn-primary shadow-sm">
        <i class="bi bi-funnel me-1"></i> Filter
      </button>
      <a href="{{ route('employees.endings') }}" class="btn btn-outline-secondary shadow-sm">Reset</a>
    </div>
  </form>

  {{-- ===== TABLE ===== --}}
  <div class="table-container">
    <table class="table table-hover table-bordered align-middle table-ending">
      <thead class="table-light">
        <tr>
          <th>#</th>
          <th>Code</th>
          <th>Name</th>
          <th>Department</th>
          <th>Type</th>
          <th>Start Date</th>
          <th>End Date</th>
          <th class="text-center">Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($employees as $emp)
          <tr>
            <td>{{ $loop->iteration + ($employees->currentPage()-1)*$employees->perPage() }}</td>
            <td><strong>{{ $emp->employee_code }}</strong></td>
            <td>{{ $emp->name }}</td>
            <td>{{ optional($emp->department)->name }}</td>
            <td><span class="badge bg-info-subtle text-dark">{{ ucfirst($emp->employment_type) }}</span></td>
            <td>{{ optional($emp->employment_start_date)->toDateString() }}</td>
            <td>
              <span class="badge bg-light text-dark border">
                {{ optional($emp->employment_end_date)->toDateString() ?? '—' }}
              </span>
            </td>
            <td class="text-center">
              <div class="dropdown">
                <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                  Manage
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                  @if($emp->employment_type === 'probationary')
                    <li>
                  <form action="{{ route('employees.regularize', $emp->id) }}" method="POST">
    @csrf
    <button type="submit" class="dropdown-item text-success">
        <i class="bi bi-check-circle me-1"></i> Regularize
    </button>
</form>

                    </li>

                    {{-- Trigger the Extend Modal --}}
                    <li>
                      <button type="button" class="dropdown-item"
                              data-bs-toggle="modal"
                              data-bs-target="#extendModal"
                              data-route="{{ route('employees.extendProbation', $emp->id) }}"
                              data-current-end="{{ optional($emp->employment_end_date)->toDateString() }}">
                        <i class="bi bi-plus-circle me-1 text-primary"></i> Extend Probation
                      </button>
                    </li>

                    <li>
                      <form method="POST" action="{{ route('employees.rejectProbation', $emp) }}"
                            onsubmit="return confirm('Reject probation for {{ $emp->employee_code }}?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="dropdown-item text-danger">
                          <i class="bi bi-x-circle me-1"></i> Reject Probation
                        </button>
                      </form>
                    </li>
                  @else
                    {{-- Trigger the Adjust Dates Modal --}}
                    <li>
                      <button type="button" class="dropdown-item"
                              data-bs-toggle="modal"
                              data-bs-target="#adjustModal"
                              data-route="{{ route('employees.extendTerm', $emp->id) }}"
                              data-current-start="{{ optional($emp->employment_start_date)->toDateString() }}"
                              data-current-end="{{ optional($emp->employment_end_date)->toDateString() }}">
                        <i class="bi bi-pencil-square me-1 text-primary"></i> Adjust Dates
                      </button>
                    </li>
                  @endif
                </ul>
              </div>
            </td>
          </tr>
        @empty
          <tr><td colspan="10" class="text-center text-muted py-4">No employees ending soon.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="mt-3">{{ $employees->links('pagination::bootstrap-5') }}</div>
</div>

{{-- ===== EXTEND MODAL ===== --}}
<div class="modal fade" id="extendModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form id="extendForm" method="POST" class="modal-content">
      @csrf @method('PUT')
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-calendar-plus me-2 text-primary"></i> Extend Probation</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Current End Date</label>
          <div class="form-control bg-light text-muted" id="extendCurrentEndDisplay"></div>
        </div>
        <div class="mb-3">
          <label class="form-label">New End Date</label>
          <input type="date" name="new_end_date" id="extendNewEnd" class="form-control" required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-save me-1"></i> Save
        </button>
      </div>
    </form>
  </div>
</div>

{{-- ===== ADJUST MODAL ===== --}}
<div class="modal fade" id="adjustModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form id="adjustForm" method="POST" class="modal-content">
      @csrf @method('PUT')
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="bi bi-pencil-square me-2 text-primary"></i> Adjust Dates
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Current Start Date</label>
          <div class="form-control bg-light text-muted" id="modalCurrentStartDisplay"></div>
        </div>
        <div class="mb-3">
          <label class="form-label">New Start Date</label>
          <input type="date" name="employment_start_date" id="modalNewStart" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Current End Date</label>
          <div class="form-control bg-light text-muted" id="modalCurrentEndDisplay"></div>
        </div>
        <div class="mb-3">
          <label class="form-label">New End Date</label>
          <input type="date" name="employment_end_date" id="modalNewEnd" class="form-control" required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-save me-1"></i> Save Changes
        </button>
      </div>
    </form>
  </div>
</div>

{{-- Extend Probation Modal --}}
<div class="modal fade" id="extendModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form id="extendForm" method="POST">
      @csrf @method('PATCH')
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Extend Probation</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Current End Date</label>
            <input type="text" id="extendCurrentEnd" class="form-control" readonly>
          </div>
          <div class="mb-3">
            <label class="form-label">Months to Extend</label>
            <select name="months" class="form-select" required>
              @for($m=1;$m<=6;$m++)
                <option value="{{ $m }}">{{ $m }} {{ \Illuminate\Support\Str::plural('month',$m) }}</option>
              @endfor
            </select>
            <div class="form-text">Allowed: 1 to 6 months.</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Effective On (optional)</label>
            <input type="date" name="effective_on" class="form-control">
            <div class="form-text">Defaults to the later of today or the current end date.</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Reason (optional)</label>
            <textarea name="reason" class="form-control" rows="2" placeholder="e.g., more time to evaluate attendance and quality"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Extend</button>
        </div>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  const csrf = document.querySelector('meta[name="csrf-token"]').content;

  /* ───── EXTEND PROBATION ───── */
  const extendModal = document.getElementById('extendModal');
  const extendForm = document.getElementById('extendForm');
  const extendDisplay = document.getElementById('extendCurrentEndDisplay');
  const extendNewEnd = document.getElementById('extendNewEnd');

  extendModal?.addEventListener('show.bs.modal', ev => {
    const trigger = ev.relatedTarget;
    extendForm.action = trigger.dataset.route;
    const current = trigger.dataset.currentEnd || '';
    extendDisplay.textContent = current || '—';
    if (current) {
      const d = new Date(current);
      d.setMonth(d.getMonth() + 3);
      extendNewEnd.value = d.toISOString().split('T')[0];
    }
  });

  extendForm?.addEventListener('submit', e => {
    e.preventDefault();
    fetch(extendForm.action, {
      method: 'PUT',
      headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
      body: JSON.stringify({ new_end_date: extendNewEnd.value })
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        alert(data.message);
        bootstrap.Modal.getInstance(extendModal).hide();
        location.reload();
      } else alert('Failed: ' + (data.message || 'Unknown error'));
    })
    .catch(() => alert('Error extending probation.'));
  });

  /* ───── ADJUST DATES ───── */
  const adjustModal = document.getElementById('adjustModal');
  const adjustForm = document.getElementById('adjustForm');
  const modalCurStartDisplay = document.getElementById('modalCurrentStartDisplay');
  const modalCurEndDisplay = document.getElementById('modalCurrentEndDisplay');
  const modalNewStart = document.getElementById('modalNewStart');
  const modalNewEnd = document.getElementById('modalNewEnd');

  adjustModal?.addEventListener('show.bs.modal', ev => {
    const trigger = ev.relatedTarget;
    adjustForm.action = trigger.dataset.route;
    modalCurStartDisplay.textContent = trigger.dataset.currentStart || '—';
    modalCurEndDisplay.textContent   = trigger.dataset.currentEnd || '—';
    modalNewStart.value = trigger.dataset.currentStart || '';
    modalNewEnd.value   = trigger.dataset.currentEnd || '';
  });

  adjustForm?.addEventListener('submit', e => {
    e.preventDefault();
    fetch(adjustForm.action, {
      method: 'PUT',
      headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
      body: JSON.stringify({
        employment_start_date: modalNewStart.value,
        employment_end_date: modalNewEnd.value
      })
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        alert(data.message);
        bootstrap.Modal.getInstance(adjustModal).hide();
        location.reload();
      } else alert('Failed: ' + (data.message || 'Unknown error'));
    })
    .catch(() => alert('Error updating employment dates.'));
  });

  // Extend Probation modal hook
  const extendModal  = document.getElementById('extendModal');
  const extendForm   = document.getElementById('extendForm');
  const extendEnd    = document.getElementById('extendCurrentEnd');

  extendModal?.addEventListener('show.bs.modal', (ev)=>{
    const a = ev.relatedTarget;
    if (!a) return;
    extendForm.action = a.getAttribute('data-route');
    extendEnd.value   = a.getAttribute('data-current-end') || '';
  });
});
</script>
@endpush
