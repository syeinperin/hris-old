@extends('layouts.app')
@section('page_title','Performance Evaluations')

@push('styles')
<style>
  .accordion-button:not(.collapsed) {
    background-color: #f8f9fa;
    font-weight: 600;
  }
  .table-sm td, .table-sm th { padding: 0.35rem 0.5rem; }
  .card-header h4 { font-weight: 700; }
  .btn-danger i, .btn-outline-danger i { vertical-align: -1px; }
</style>
@endpush

@section('content')
<div class="container-fluid">
  <div class="card shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
      <h4 class="mb-0 d-flex align-items-center">
        <i class="bi bi-clipboard2-check me-2"></i> Performance Evaluations
      </h4>

      {{-- Visible only to HR & Supervisor --}}
      @if(auth()->user()->hasRole('hr') || auth()->user()->hasRole('supervisor'))
      <div class="d-flex align-items-center gap-2 flex-wrap">
        {{-- Violation / Suspension --}}
        <a href="{{ route('discipline.index') }}" class="btn btn-outline-danger">
          <i class="bi bi-exclamation-octagon me-1"></i> Violation / Suspension
        </a>

        {{-- New Evaluation --}}
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#evalCreateModal">
          <i class="bi bi-plus-lg me-1"></i> New Evaluation
        </button>
      </div>
      @endif
    </div>

    <div class="card-body">
      {{-- Search --}}
      <form method="GET" class="row g-2 mb-3">
        <div class="col-md-6">
          <input name="search" value="{{ request('search') }}" class="form-control" placeholder="Search employee…">
        </div>
        <div class="col-md-3">
          <select name="type" class="form-select">
            <option value="">All Types</option>
            <option value="regular" {{ request('type')=='regular'?'selected':'' }}>Regular</option>
            <option value="probationary" {{ request('type')=='probationary'?'selected':'' }}>Probationary</option>
            <option value="kpi" {{ request('type')=='kpi'?'selected':'' }}>KPI-Based</option>
            <option value="360" {{ request('type')=='360'?'selected':'' }}>360° Leadership</option>
          </select>
        </div>
        <div class="col-md-2">
          <button class="btn btn-outline-secondary w-100">
            <i class="bi bi-search me-1"></i> Search
          </button>
        </div>
      </form>

      {{-- Grouped Evaluations --}}
      <div class="accordion" id="evalAccordion">
        @forelse($evaluations->groupBy('employee_id') as $empId => $records)
          @php $emp = $records->first()->employee; @endphp
          <div class="accordion-item mb-2 shadow-sm">
            <h2 class="accordion-header" id="heading{{ $empId }}">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                      data-bs-target="#collapse{{ $empId }}" aria-expanded="false">
                <div class="d-flex flex-column">
                  <span class="fw-semibold">{{ $emp->name }}</span>
                  <small class="text-muted">
                    {{ $emp->department->name ?? 'No Department' }} · {{ $emp->employee_code }}
                  </small>
                </div>
              </button>
            </h2>

            <div id="collapse{{ $empId }}" class="accordion-collapse collapse"
                 data-bs-parent="#evalAccordion">
              <div class="accordion-body bg-light">
                <div class="table-responsive">
                  <table class="table table-sm table-bordered align-middle mb-0">
                    <thead class="table-light">
                      <tr>
                        <th>Period</th>
                        <th>Type</th>
                        <th>Overall</th>
                        <th>Evaluator</th>
                        <th>Recommendation</th>
                        <th>Status</th>
                        <th class="text-center">Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      @foreach($records as $e)
                        <tr>
                          <td>{{ $e->period_start->toDateString() }} – {{ $e->period_end->toDateString() }}</td>
                          <td>{{ ucfirst($e->type) }}</td>
                          <td>
                            <div class="fw-semibold">{{ number_format($e->overall_score, 2) }}%</div>
                            <small class="badge bg-{{ $e->result_color }}">{{ $e->result_label }}</small>
                          </td>
                          <td>{{ $e->evaluator->name ?? '—' }}</td>
                          <td>
                            @if($e->regularization_recommended)
                              <span class="badge bg-success">Regularization Recommended</span>
                            @elseif($e->promotion_recommended)
                              <span class="badge bg-primary">Promotion Recommended</span>
                            @else
                              <span class="badge bg-secondary">—</span>
                            @endif
                          </td>
                          <td>
                            <span class="badge {{ $e->status==='submitted'?'bg-success':'bg-secondary' }}">
                              {{ ucfirst($e->status) }}
                            </span>
                          </td>
                          <td class="text-center">
                            <a href="{{ route('evaluations.partials.show', $e) }}" class="btn btn-sm btn-outline-dark" title="View">
                              <i class="bi bi-eye"></i>
                            </a>

                            {{-- Delete --}}
                            <form action="{{ route('evaluations.destroy',$e) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('Delete this evaluation?')">
                              @csrf @method('DELETE')
                              <button class="btn btn-sm btn-outline-danger" title="Delete">
                                <i class="bi bi-trash"></i>
                              </button>
                            </form>
                          </td>
                        </tr>
                      @endforeach
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        @empty
          <div class="alert alert-light text-center">No evaluations found.</div>
        @endforelse
      </div>

      {{-- Pagination --}}
      <div class="mt-3 d-flex justify-content-between align-items-center">
        <small class="text-muted">
          Showing {{ $evaluations->firstItem() ?? 0 }}–{{ $evaluations->lastItem() ?? 0 }} of {{ $evaluations->total() ?? 0 }}
        </small>
        {{ $evaluations->links('pagination::bootstrap-5') }}
      </div>
    </div>
  </div>
</div>

{{-- Create Evaluation Modal --}}
@if(auth()->user()->hasRole('hr') || auth()->user()->hasRole('supervisor'))
<div class="modal fade" id="evalCreateModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <form action="{{ route('evaluations.store') }}" method="POST">
        @csrf
        <div class="modal-header bg-white border-bottom">
          <h5 class="modal-title">
            <i class="bi bi-plus-circle me-1"></i> New Evaluation
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body" style="max-height:70vh;overflow:auto;">
          @include('evaluations.partials.form', ['mode'=>'create'])
        </div>

        <div class="modal-footer bg-light">
          <button class="btn btn-success" type="submit">
            <i class="bi bi-check2-circle me-1"></i> Save
          </button>
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endif
@endsection
