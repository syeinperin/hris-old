@extends('layouts.app')

@section('page_title', 'Loan Payments')

@section('content')
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="mb-0">
      <i class="bi bi-cash-stack me-2"></i>
      Loan Payments — {{ $loan->employee->name }}
    </h3>
    <a href="{{ route('loans.index') }}" class="btn btn-secondary">
      ← Back to Loans
    </a>
  </div>

  {{-- Loan Summary --}}
  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <div class="row mb-3">
        <div class="col-md-3"><strong>Reference:</strong> {{ $loan->reference_no }}</div>
        <div class="col-md-3"><strong>Plan:</strong> {{ $loan->plan->name ?? '—' }}</div>
        <div class="col-md-3"><strong>Loan Type:</strong> {{ $loan->loanType->name ?? '—' }}</div>
        <div class="col-md-3">
          <strong>Status:</strong>
          <span class="badge bg-{{ $loan->status === 'paid' ? 'success' : 'warning' }}">
            {{ ucfirst($loan->status) }}
          </span>
        </div>
      </div>
      <div class="row">
        <div class="col-md-3"><strong>Principal:</strong> ₱{{ number_format($loan->principal_amount, 2) }}</div>
        <div class="col-md-3"><strong>Total Payable:</strong> ₱{{ number_format($loan->total_payable, 2) }}</div>
        <div class="col-md-3"><strong>Paid So Far:</strong> ₱{{ number_format($totalPaid, 2) }}</div>
        <div class="col-md-3"><strong>Balance:</strong> ₱{{ number_format($balance, 2) }}</div>
      </div>
    </div>
  </div>

  {{-- Payment Form --}}
  @if($loan->status !== 'paid')
  <div class="card shadow-sm mb-4">
    <div class="card-header bg-white fw-semibold">
      <i class="bi bi-plus-circle me-2"></i> Record Payment
    </div>
    <div class="card-body">
      <form action="{{ route('payments.store', $loan->id) }}" method="POST" class="row g-3">
        @csrf

        <div class="col-md-4">
          <label class="form-label">Payment Date</label>
          <input type="date" name="payment_date" class="form-control" value="{{ now()->toDateString() }}" required>
        </div>

        <div class="col-md-4">
          <label class="form-label">Amount (₱)</label>
          <input type="number" step="0.01" name="amount" class="form-control" placeholder="0.00" required>
        </div>

        <div class="col-md-4">
          <label class="form-label">Penalty (₱)</label>
          <input type="number" step="0.01" name="penalty" class="form-control" placeholder="0.00">
        </div>

        <div class="col-12 text-end">
          <button class="btn btn-primary">
            <i class="bi bi-save me-1"></i> Save Payment
          </button>
        </div>
      </form>
    </div>
  </div>
  @endif

  {{-- Payment History --}}
  <div class="card shadow-sm">
    <div class="card-header bg-white fw-semibold">
      <i class="bi bi-journal-text me-2"></i> Payment History
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-striped mb-0 align-middle">
          <thead class="table-light">
            <tr>
              <th>Date</th>
              <th>Amount</th>
              <th>Penalty</th>
              <th>Total</th>
            </tr>
          </thead>
          <tbody>
            @forelse($loan->payments as $p)
              <tr>
                <td>{{ $p->payment_date->format('Y-m-d') }}</td>
                <td>₱{{ number_format($p->amount, 2) }}</td>
                <td>₱{{ number_format($p->penalty, 2) }}</td>
                <td>₱{{ number_format($p->amount + $p->penalty, 2) }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="4" class="text-center py-3 text-muted">
                  No payments recorded yet.
                </td>
              </tr>
            @endforelse
          </tbody>
          @if($loan->payments->count())
          <tfoot class="table-light">
            <tr>
              <th colspan="3" class="text-end">Total Paid:</th>
              <th>₱{{ number_format($totalPaid, 2) }}</th>
            </tr>
          </tfoot>
          @endif
        </table>
      </div>
    </div>
  </div>
</div>
@endsection
