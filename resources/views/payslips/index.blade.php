@extends('layouts.app')

@section('page_title','My Payslips')

@push('styles')
<style>
  .page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
  }

  .generate-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    margin-bottom: 2rem;
  }

  .highlight-row {
    background-color: #eaf3ff !important;
    border-left: 4px solid #0d6efd;
    font-weight: 600;
  }

  .highlight-row:hover {
    background-color: #dceaff !important;
  }

  .table-container {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 1rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
  }

  .table th {
    background-color: #f8f9fb;
    white-space: nowrap;
  }

  .btn-generate {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: .5rem;
  }

  .empty-row {
    text-align: center;
    color: #6c757d;
    padding: 2rem 0;
  }

  .latest-badge {
    font-size: 0.75rem;
    background: #0d6efd;
    color: #fff;
    padding: 0.15rem 0.5rem;
    border-radius: 6px;
    margin-left: .4rem;
  }
</style>
@endpush

@section('content')
<div class="container-fluid">

  <div class="page-header">
    <h2 class="mb-0"><i class="bi bi-receipt-cutoff me-2"></i>My Payslips</h2>
  </div>

  {{-- SUCCESS MESSAGE --}}
  @if(session('success'))
    <div class="alert alert-success shadow-sm">{{ session('success') }}</div>
  @endif

  {{-- GENERATE FORM --}}
  <div class="generate-card">
    <h5><i class="bi bi-file-earmark-plus"></i> Generate New Payslip</h5>
    <p class="text-muted mb-3 small">Choose a month and cutoff period below to generate your payslip.</p>

    <form action="{{ route('payslips.store') }}" method="POST" class="row gy-3 align-items-end">
      @csrf
      <div class="col-md-3">
        <label class="form-label">Select Month</label>
        <input type="month" name="month" class="form-control"
               value="{{ old('month', now()->format('Y-m')) }}" required>
      </div>

      <div class="col-md-6">
        <label class="form-label d-block">Cutoff Period</label>
        <div class="d-flex flex-wrap gap-3">
          <div class="form-check">
            <input class="form-check-input" type="radio" name="cutoff" id="cutoff1" value="first" checked>
            <label class="form-check-label" for="cutoff1">1st Cutoff (1–15)</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="radio" name="cutoff" id="cutoff2" value="second">
            <label class="form-check-label" for="cutoff2">2nd Cutoff (16–end)</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="radio" name="cutoff" id="cutoff3" value="whole">
            <label class="form-check-label" for="cutoff3">Whole Month</label>
          </div>
        </div>
      </div>

      <div class="col-md-3 text-end">
        <button class="btn btn-primary w-100 btn-generate">
          <i class="bi bi-magic"></i> Generate Payslip
        </button>
      </div>
    </form>
  </div>

  {{-- PAYSLIP HISTORY --}}
  <div class="table-container">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="mb-0"><i class="bi bi-list-ul me-1"></i> Payslip History</h5>
      <span class="text-muted small">Showing {{ $payslips->count() }} of {{ $payslips->total() }} records</span>
    </div>

    @php
      $sortedPayslips = $payslips->sortByDesc('period_end');
      $latest = $sortedPayslips->first();
    @endphp

    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead class="table-light">
          <tr>
            <th>Period</th>
            <th>Worked Hrs</th>
            <th>OT Hrs</th>
            <th>OT Pay</th>
            <th>Deductions</th>
            <th>Gross</th>
            <th>Net</th>
            <th class="text-center">PDF</th>
          </tr>
        </thead>
        <tbody>
          @forelse($sortedPayslips as $slip)
            <tr @class(['highlight-row' => $slip->id === $latest->id])>
              <td>
                <strong>
                  {{ optional($slip->period_end)->format('M d, Y') ?? '—' }}
                  @if($slip->id === $latest->id)
                    <span class="latest-badge">Latest</span>
                  @endif
                </strong><br>
                <small class="text-muted">{{ optional($slip->period_start)->format('M d, Y') ?? '—' }}</small>
              </td>
              <td>{{ number_format($slip->worked_hours, 2) }}</td>
              <td>{{ number_format($slip->ot_hours, 2) }}</td>
              <td>₱{{ number_format($slip->ot_pay, 2) }}</td>
              <td>₱{{ number_format($slip->deductions, 2) }}</td>
              <td>₱{{ number_format($slip->gross_amount, 2) }}</td>
              <td><strong>₱{{ number_format($slip->net_amount, 2) }}</strong></td>
              <td class="text-center">
                <a href="{{ route('payslips.download',$slip) }}" class="btn btn-sm btn-outline-primary">
                  <i class="bi bi-file-earmark-pdf"></i>
                </a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="empty-row">
                <i class="bi bi-inbox"></i><br>No payslips found for this period.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="mt-3">
      {{ $payslips->links('pagination::bootstrap-5') }}
    </div>
  </div>
</div>
@endsection
