@extends('layouts.app')
@section('page_title','Payroll Calendar')

@section('content')
<div class="container-fluid">

  {{-- Header & Filter Form --}}
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">
      Payroll Calendar » {{ \Carbon\Carbon::parse("$month-01")->format('F Y') }}
    </h3>
    <form method="GET" action="{{ route('payroll.calendar') }}" class="d-flex">
      <input
        type="text"
        name="search"
        class="form-control form-control-sm me-2"
        placeholder="Search code or name…"
        value="{{ $search }}"
      >
      <input
        type="month"
        name="month"
        class="form-control form-control-sm me-2"
        value="{{ $month }}"
      >
      <button class="btn btn-sm btn-outline-primary">Go</button>
    </form>
  </div>

  {{-- Calendar Table --}}
  <div class="table-responsive">
    <table class="table table-bordered mb-1">
      <thead class="table-light">
        <tr>
          <th style="width:120px">Code</th>
          <th style="width:200px">Name</th>
          @for($d = $start->copy(); $d->lte($end); $d->addDay())
            <th class="text-center">{{ $d->day }}</th>
          @endfor
        </tr>
      </thead>
      <tbody>
        @foreach($employees as $emp)
          <tr>
            <td class="align-middle">{{ $emp->employee_code }}</td>
            <td class="align-middle">{{ $emp->last_name }}, {{ $emp->first_name }}</td>

            @for($d = $start->copy(); $d->lte($end); $d->addDay())
              @php
                $day         = $d->toDateString();
                $dow         = $d->format('l');
                $sched       = $emp->schedule;
                $isRest      = $sched && $sched->rest_day === $dow;
$empAttendance = $attendance->get($emp->id);
$att = $empAttendance ? $empAttendance->get($day) : null;
                $leaveOnDay  = $leaveIndex->get($emp->id, collect())->get($day, collect());
                $isHoliday   = array_key_exists($day, $holidays);

                // NEW: discipline overlays
                $suspOnDay   = $discipline['suspensions'][$emp->id][$day] ?? null;
                $violCount   = isset($discipline['violations'][$emp->id][$day])
                               ? count($discipline['violations'][$emp->id][$day]) : 0;
              @endphp

              @if($isHoliday)
                {{-- Official Holiday --}}
                <td class="p-0 bg-warning text-dark"
                    style="opacity:.5;width:32px;height:32px"
                    title="{{ $holidays[$day] }}">&nbsp;</td>

              @elseif($suspOnDay)
                {{-- NEW: Suspension (blocks the day) --}}
                <td class="p-0 bg-dark text-white"
                    style="opacity:.75;width:32px;height:32px;cursor:help"
                    title="Suspended: {{ $suspOnDay->category }} ({{ ucfirst($suspOnDay->severity) }})
Reason: {{ $suspOnDay->reason ?? '—' }}">
                  <div class="w-100 h-100"></div>
                </td>

              @elseif($isRest)
                {{-- Rest Day --}}
                <td class="p-0 bg-secondary text-white"
                    style="opacity:.5;width:32px;height:32px"
                    title="Rest Day">&nbsp;</td>

              @elseif($leaveOnDay->isNotEmpty())
                {{-- Leave --}}
                @php
                  $lv    = $leaveOnDay->first();
                  $cls   = $lv->status === 'approved'
                            ? 'bg-success'
                            : 'bg-light border border-success text-success';
                  $title = ucfirst($lv->status)
                           ." leave: {$lv->leave_type}\n"
                           ."from {$lv->start_date->toDateString()}"
                           ." to {$lv->end_date->toDateString()}";
                @endphp
                <td class="p-0" style="width:32px;height:32px;cursor:help"
                    title="{{ $title }}">
                  <div class="w-100 h-100 {{ $cls }}"></div>
                </td>

       @else
  {{-- Attendance toggle / empty --}}
  @php
      // $att is a Collection of attendance records for the day
      $firstAtt = $att?->first(); // safely get the first record if any

      if ($firstAtt) {
          $cls   = $firstAtt->is_manual
                    ? 'bg-primary'
                    : 'bg-danger text-white';
          $title = $firstAtt->is_manual
                    ? 'Manual attendance'
                    : 'Biometric attendance';
      } else {
          $cls   = 'bg-subtle border border-info';
          $title = 'No attendance record';
      }
  @endphp
  <td class="p-0 position-relative cell"
      data-emp="{{ $emp->id }}"
      data-day="{{ $day }}"
      style="cursor:pointer;width:32px;height:32px"
      title="{{ $title }}">
    <div class="w-100 h-100 {{ $cls }}"></div>

    {{-- NEW: violation dot overlay --}}
    @if($violCount > 0)
      <span class="violation-dot" title="{{ $violCount }} violation(s) on this day"></span>
    @endif
  </td>
@endif

            @endfor

          </tr>
        @endforeach
      </tbody>
    </table>
    {{-- Pagination --}}
    <div class="mt-3">
      {{ $employees->links() }}
    </div>
  </div>

  {{-- Legend --}}
  <div class="mt-4 d-flex flex-wrap align-items-center">
    <span class="badge bg-danger text-white me-2">Biometric</span>
    <small class="me-4">Biometric attendance</small>

    <span class="badge bg-primary me-2">Manual</span>
    <small class="me-4">Manual attendance</small>

    <span class="badge bg-secondary me-2">Rest Day</span>
    <small class="me-4">Weekend / Rest</small>

    <span class="badge bg-warning text-dark me-2">Holiday</span>
    <small class="me-4">Official holiday</small>

    <span class="badge bg-success me-2">Leave</span>
    <small class="me-4">Approved leave</small>

    <span class="badge bg-light border border-success text-success me-2">Leave</span>
    <small class="me-4">Pending leave</small>

    <span class="badge bg-dark me-2">Suspension</span>
    <small class="me-4">Unpaid suspension</small>

    <span class="badge bg-warning text-dark me-2">●</span>
    <small>Day has violation</small>

    <span class="badge bg-subtle border border-info me-2">Empty</span>
    <small>Empty cell</small>
  </div>
</div>

{{-- Action Modal --}}
<div class="modal fade" id="actionModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Choose Attendance Action</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="modalEmp">
        <input type="hidden" id="modalDay">
        <div class="form-check">
          <input class="form-check-input" type="radio" name="action" id="actBio" value="biometric" checked>
          <label class="form-check-label" for="actBio">Biometric attendance</label>
        </div>
        <div class="form-check">
          <input class="form-check-input" type="radio" name="action" id="actMan" value="manual">
          <label class="form-check-label" for="actMan">Manual attendance</label>
        </div>
        <div class="form-check mt-2">
          <input class="form-check-input" type="radio" name="action" id="actRem" value="remove">
          <label class="form-check-label" for="actRem">Remove entry</label>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="modalSave">Save</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('styles')
<style>
  .table td, .table th { padding:0; font-size:.75rem; }
  .position-relative { position:relative; }
  .bg-subtle { background-color:#f0f8ff !important; }

  /* NEW: tiny violation indicator */
  .violation-dot{
    position:absolute; top:2px; right:2px;
    width:7px; height:7px; border-radius:50%;
    background:#ffc107; box-shadow:0 0 0 1px #fff inset;
  }
</style>
@endpush

@push('scripts')
<script>
  const token = document.head.querySelector('meta[name="csrf-token"]').content;
  let clickedCell;

  // Show modal on cell click
  document.querySelectorAll('td.cell').forEach(td => {
    td.addEventListener('click', () => {
      clickedCell = td;
      document.getElementById('modalEmp').value = td.dataset.emp;
      document.getElementById('modalDay').value = td.dataset.day;
      new bootstrap.Modal(document.getElementById('actionModal')).show();
    });
  });

  // Handle “Save”
  document.getElementById('modalSave').addEventListener('click', async () => {
    const emp   = document.getElementById('modalEmp').value;
    const day   = document.getElementById('modalDay').value;
    const act   = document.querySelector('input[name="action"]:checked').value;
    const div   = clickedCell.querySelector('div');

    // choose endpoint
    let url, method = 'POST', body = JSON.stringify({ employee_id: emp, date: day });
    if (act === 'biometric') {
      url = "{{ route('payroll.calendar.biometric') }}";
    } else if (act === 'manual') {
      url = "{{ route('payroll.calendar.toggleManual') }}";
    } else {
      url    = "{{ route('payroll.calendar.remove') }}";
      method = 'DELETE';
    }

    // send
    const res = await fetch(url, {
      method,
      headers: {
        'Content-Type':'application/json',
        'X-CSRF-TOKEN': token
      },
      body
    });
    bootstrap.Modal.getInstance(document.getElementById('actionModal')).hide();

    if (!res.ok) {
      return alert(`Failed to apply "${act}"`);
    }

    // refresh cell color
    if (act === 'biometric') {
      div.className = 'w-100 h-100 bg-danger text-white';
    } else if (act === 'manual') {
      div.className = 'w-100 h-100 bg-primary';
    } else {
      div.className = 'w-100 h-100 bg-subtle border border-info';
    }
  });
</script>
@endpush
