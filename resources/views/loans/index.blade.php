@extends('layouts.app')

@section('page_title','Loan Settings')

@section('content')
<div class="container-fluid">
  <div class="card shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
      <h4 class="mb-0"><i class="bi bi-journal-medical me-1"></i> Loan Settings</h4>
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#loanCreateModal">
        <i class="bi bi-plus-lg me-1"></i> New Loan
      </button>
    </div>

    <div class="card-body">
      {{-- SEARCH FORM --}}
      <form method="GET" action="{{ route('loans.index') }}" class="row g-2 mb-4">
        <div class="col-md-8">
          <input name="search"
                 value="{{ request('search') }}"
                 class="form-control"
                 placeholder="Search reference or employee…">
        </div>
        <div class="col-md-2">
          <button class="btn btn-outline-secondary w-100">
            <i class="bi bi-search me-1"></i> Search
          </button>
        </div>
      </form>

      {{-- LOANS TABLE --}}
      <div class="table-responsive">
        <table class="table table-hover table-bordered align-middle">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>Employee</th>
              <th>Reference</th>
              <th>Type</th>
              <th>Plan</th>
              <th>Next Due</th>
              <th>Status</th>
              <th class="text-center">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($loans as $loan)
              <tr>
                <td>{{ $loop->iteration + ($loans->currentPage()-1)*$loans->perPage() }}</td>
                <td>{{ $loan->employee->name }}</td>
                <td>{{ $loan->reference_no }}</td>
                <td>{{ $loan->loanType->name ?? '—' }}</td>
                <td>{{ $loan->plan->name ?? '—' }}</td>
                <td>{{ optional($loan->next_payment_date)->toDateString() ?? '—' }}</td>
                <td>
                  <span class="badge
                    {{ $loan->status=='active'    ? 'bg-primary':'' }}
                    {{ $loan->status=='paid'      ? 'bg-success':'' }}
                    {{ $loan->status=='defaulted' ? 'bg-warning':'' }}">
                    {{ ucfirst($loan->status) }}
                  </span>
                </td>
                <td class="text-center">
                  {{-- View payments --}}
                  <a href="{{ route('loans.payments', $loan->id) }}"
                     class="btn btn-sm btn-outline-primary"
                     title="View Payments">
                    <i class="bi bi-list-ul"></i>
                  </a>

                  {{-- Edit loan --}}
                  <a href="{{ route('loans.edit', $loan) }}"
                     class="btn btn-sm btn-outline-success"
                     title="Edit Loan">
                    <i class="bi bi-pencil"></i>
                  </a>

                  {{-- Delete loan --}}
                  <form action="{{ route('loans.destroy', $loan) }}"
                        method="POST"
                        class="d-inline"
                        onsubmit="return confirm('Remove this loan?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger" title="Delete Loan">
                      <i class="bi bi-trash"></i>
                    </button>
                  </form>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="8" class="text-center text-muted py-4">
                  No loans found.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      {{-- PAGINATION --}}
      <div class="d-flex justify-content-between align-items-center mt-4">
        <small class="text-muted">
          Showing {{ $loans->firstItem() }}–{{ $loans->lastItem() }} of {{ $loans->total() }}
        </small>
        {{ $loans->withQueryString()->links('pagination::bootstrap-5') }}
      </div>
    </div>
  </div>
</div>
@endsection

{{-- PUSH ALL MODALS INTO LAYOUT --}}
@push('modals')
  {{-- CREATE MODAL --}}
  <div class="modal fade" id="loanCreateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
      <div class="modal-content">
        <form action="{{ route('loans.store') }}" method="POST">
          @csrf
          <div class="modal-header">
            <h5 class="modal-title"><i class="bi bi-plus-circle-fill me-1"></i> New Loan</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body" style="max-height:70vh; overflow-y:auto;">
            @include('loans.form', [
              'loan'      => null,
              'employees' => $employees,
              'types'     => $types,
              'plans'     => $plans,
            ])
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-success">
              <i class="bi bi-check-circle-fill me-1"></i> Save
            </button>
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
              Cancel
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  {{-- EDIT MODAL --}}
  @isset($editLoan)
    <div class="modal fade" id="loanEditModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
          <form action="{{ route('loans.update', $editLoan) }}" method="POST">
            @csrf @method('PUT')
            <div class="modal-header">
              <h5 class="modal-title"><i class="bi bi-pencil-square me-1"></i> Edit Loan</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="max-height:70vh; overflow-y:auto;">
              @include('loans.form', [
                'loan'      => $editLoan,
                'employees' => $employees,
                'types'     => $types,
                'plans'     => $plans,
              ])
            </div>
            <div class="modal-footer">
              <button type="submit" class="btn btn-primary">
                <i class="bi bi-save me-1"></i> Update
              </button>
              <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                Cancel
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  @endisset
@endpush

{{-- AUTO-OPEN EDIT MODAL IF NEEDED --}}
@isset($editLoan)
  @push('scripts')
    <script>
      document.addEventListener('DOMContentLoaded', () => {
        new bootstrap.Modal(document.getElementById('loanEditModal')).show();
      });
    </script>
  @endpush
@endisset
