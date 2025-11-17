@extends('layouts.app')

@section('page_title', 'Approval History')

@push('styles')
<style>
  .accordion-button:not(.collapsed) {
    background-color: #f8f9fa;
    font-weight: 600;
  }

  .accordion-button {
    padding: .6rem 1rem;
    font-size: .95rem;
  }

  .accordion-body {
    padding: 1rem 1.25rem;
  }

  .table-sm td, .table-sm th {
    padding: 0.35rem 0.5rem;
    font-size: .88rem;
  }

  h3 {
    font-size: 1.3rem;
    font-weight: 700;
  }

  .badge-status {
    border-radius: 30px;
    padding: .25rem .65rem;
    font-weight: 600;
    font-size: .75rem;
  }

  .badge-approved { background: #28a745; color: #fff; }
  .badge-rejected { background: #dc3545; color: #fff; }
  .badge-pending { background: #e2e3ff; color: #212529; border: 1px solid #aaa; }

  small.text-muted {
    font-size: .78rem;
  }
</style>
@endpush

@section('content')
<div class="container-fluid">

  {{-- Header --}}
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="mb-0">
      <i class="bi bi-clock-history me-2"></i> Approval History
    </h3>
    <form method="GET" action="{{ route('approvals.history') }}" class="d-flex gap-2">
      <input type="text" name="search" class="form-control form-control-sm"
             placeholder="Search employee or type..." value="{{ $search ?? '' }}" style="width:220px">
      <button class="btn btn-sm btn-outline-primary">Search</button>
    </form>
  </div>

  {{-- Accordion --}}
  <div class="accordion" id="approvalAccordion">
    @forelse($approvals->groupBy('user_id') as $empId => $records)
      @php
        $employee = $records->first()->user;
      @endphp
      <div class="accordion-item mb-2 shadow-sm">
        <h2 class="accordion-header" id="heading{{ $empId }}">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                  data-bs-target="#collapse{{ $empId }}" aria-expanded="false">
            <div class="d-flex flex-column">
              <span class="fw-semibold">{{ $employee?->name ?? 'Unknown Employee' }}</span>
              <small class="text-muted">
                {{ $employee?->employee?->department?->name ?? 'No Department' }}
                · {{ $employee?->employee?->employee_code ?? '—' }}
              </small>
            </div>
          </button>
        </h2>

        <div id="collapse{{ $empId }}" class="accordion-collapse collapse"
             data-bs-parent="#approvalAccordion">
          <div class="accordion-body bg-light">
            <div class="table-responsive">
              <table class="table table-sm table-bordered align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Type</th>
                    <th>From</th>
                    <th>To</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Rejection Reason</th>
                    <th>Approved/Rejected By</th>
                    <th>Date</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($records as $leave)
                    <tr>
                      <td>{{ $leave->type?->name ?? '—' }}</td>
                      <td>{{ $leave->start_date?->format('Y-m-d') ?? '—' }}</td>
                      <td>{{ $leave->end_date?->format('Y-m-d') ?? '—' }}</td>
                      <td>{{ Str::limit($leave->reason, 40, '…') }}</td>
                      <td>
                        <span class="badge-status badge-{{ $leave->status }}">
                          {{ ucfirst($leave->status) }}
                        </span>
                      </td>
<td>
  @if(!empty($leave->rejection_reason))
    {{ $leave->rejection_reason }}
  @elseif(!empty($leave->approval?->data['rejection_reason']))
    {{ $leave->approval->data['rejection_reason'] }}
  @else
    —
  @endif
</td>

                      <td>{{ $leave->supervisor?->name ?? 'N/A' }}</td>
                      <td>{{ $leave->updated_at?->format('M d, Y h:i A') }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    @empty
      <div class="alert alert-light text-center">No approval history found.</div>
    @endforelse
  </div>

  <div class="mt-3">
    {{ $approvals->links('pagination::bootstrap-5') }}
  </div>

</div>
@endsection
