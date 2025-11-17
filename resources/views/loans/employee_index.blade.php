@extends('layouts.app')

@section('page_title','My Loans')

@section('content')
<div class="container-fluid">
  <div class="card shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
      <h4 class="mb-0">
        <i class="bi bi-wallet2 me-2"></i> My Loans
      </h4>
    </div>

    <div class="card-body">
      {{-- Filter by status --}}
      <form method="GET" class="row g-2 mb-3">
        <div class="col-md-4">
          <select name="status" class="form-select" onchange="this.form.submit()">
            <option value="">-- All statuses --</option>
            <option value="active" {{ request('status')=='active'?'selected':'' }}>Active</option>
            <option value="paid" {{ request('status')=='paid'?'selected':'' }}>Paid</option>
            <option value="defaulted" {{ request('status')=='defaulted'?'selected':'' }}>Defaulted</option>
          </select>
        </div>
      </form>

      {{-- Loans Table --}}
      <div class="table-responsive">
        <table class="table table-hover table-bordered align-middle">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>Reference</th>
              <th>Type</th>
              <th>Plan</th>
              <th>Principal</th>
              <th>Monthly</th>
              <th>Next Due</th>
              <th>Status</th>
              <th width="90">Action</th>
            </tr>
          </thead>
          <tbody>
            @forelse($loans as $loan)
              <tr>
                <td>{{ $loop->iteration + ($loans->currentPage()-1)*$loans->perPage() }}</td>
                <td>{{ $loan->reference_no }}</td>
                <td>{{ $loan->loanType->name ?? '-' }}</td>
                <td>{{ $loan->plan->name ?? '-' }}</td>
                <td>₱{{ number_format($loan->principal_amount,2) }}</td>
                <td>₱{{ number_format($loan->monthly_amount,2) }}</td>
                <td>{{ $loan->next_payment_date?->format('Y-m-d') ?? '-' }}</td>
                <td>
                  <span class="badge
                    {{ $loan->status=='active' ? 'bg-primary' : '' }}
                    {{ $loan->status=='paid' ? 'bg-success' : '' }}
                    {{ $loan->status=='defaulted' ? 'bg-danger' : '' }}">
                    {{ ucfirst($loan->status) }}
                  </span>
                </td>
                <td class="text-center">
                  <button class="btn btn-sm btn-outline-primary"
                          data-bs-toggle="modal"
                          data-bs-target="#loanPaymentsModal"
                          data-loan-id="{{ $loan->id }}"
                          data-loan-ref="{{ $loan->reference_no }}">
                    <i class="bi bi-eye"></i> View
                  </button>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="9" class="text-center text-muted py-4">
                  You have no loans.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      {{-- Pagination --}}
      <div class="d-flex justify-content-between align-items-center mt-3">
        @if($loans->total())
          <small class="text-muted">
            Showing {{ $loans->firstItem() }}–{{ $loans->lastItem() }} of {{ $loans->total() }}
          </small>
        @endif
        {{ $loans->withQueryString()->links('pagination::bootstrap-5') }}
      </div>
    </div>
  </div>
</div>

{{-- 🔹 Loan Payments Modal --}}
<div class="modal fade" id="loanPaymentsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Loan Payments — <span id="loanRef"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="paymentTableContainer" class="text-center py-4 text-muted">
          <div class="spinner-border text-primary" role="status"></div>
          <p class="mt-2">Loading payments...</p>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const modal = document.getElementById('loanPaymentsModal');
  const refSpan = document.getElementById('loanRef');
  const container = document.getElementById('paymentTableContainer');

  modal.addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    const loanId = button.getAttribute('data-loan-id');
    const ref = button.getAttribute('data-loan-ref');
    refSpan.textContent = ref;

    container.innerHTML = `
      <div class="text-center py-4 text-muted">
        <div class="spinner-border text-primary" role="status"></div>
        <p class="mt-2">Loading payments...</p>
      </div>
    `;

    fetch(`/employee/loans/${loanId}/payments`)
      .then(res => res.json())
      .then(data => {
        if (!data.length) {
          container.innerHTML = `<p class="text-center text-muted py-3">No payments found for this loan.</p>`;
          return;
        }

        let html = `
          <table class="table table-bordered align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Date</th>
                <th>Amount</th>
                <th>Penalty</th>
              </tr>
            </thead>
            <tbody>
              ${data.map(p => `
                <tr>
                  <td>${p.payment_date}</td>
                  <td>₱${parseFloat(p.amount).toLocaleString('en-PH', { minimumFractionDigits: 2 })}</td>
                  <td>₱${parseFloat(p.penalty).toLocaleString('en-PH', { minimumFractionDigits: 2 })}</td>
                </tr>
              `).join('')}
            </tbody>
          </table>
        `;
        container.innerHTML = html;
      })
      .catch(err => {
        console.error(err);
        container.innerHTML = `<p class="text-danger text-center py-3">Error loading payments.</p>`;
      });
  });
});
</script>
@endpush
