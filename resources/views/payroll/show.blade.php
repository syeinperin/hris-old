@extends('layouts.app')

@section('page_title', 'Payslip')

@push('styles')
<style>
  .nav.brand-pills .nav-link { border:1px solid #e9ecef; color:var(--brand); font-weight:600; }
  .nav.brand-pills .nav-link:not(.active):hover { background:rgba(44,44,84,.06); }
  .nav.brand-pills .nav-link.active { background:var(--brand); color:#fff; }
  .payslip-title { font-weight:700; letter-spacing:.2px; }
  .payslip-subtle { color:#6c757d; }
  .table-scroll .table { margin-bottom:0; }
  .manual-row { background:rgba(13,110,253,.08); }
</style>
@endpush

@section('content')
<div class="container-fluid">
  @php
    $month = request('month', now()->format('Y-m'));
    $empName = $employee->name ?? $employee->employee_name ?? null;
  @endphp

  <div class="card shadow-sm mb-3">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
      <div class="d-flex align-items-center gap-2">
        <i class="bi bi-wallet2"></i>
        <h4 class="payslip-title mb-0">Payslip for {{ $empName }}</h4>
      </div>
      <a href="{{ route('payroll.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to Payroll Summary
      </a>
    </div>

    <div class="card-body">
      {{-- 🔹 Date range filter --}}
      <form method="GET" action="{{ route('payroll.show', $employee->id) }}" class="row g-2 align-items-end mb-4">
        <div class="col-md-3">
          <label class="form-label fw-semibold">From</label>
          <input type="date" name="from" class="form-control"
                 value="{{ request('from', $from->toDateString()) }}">
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold">To</label>
          <input type="date" name="to" class="form-control"
                 value="{{ request('to', $to->toDateString()) }}">
        </div>
        <div class="col-md-2">
          <label class="form-label fw-semibold">Month</label>
          <input type="month" name="month" class="form-control" value="{{ $month }}">
        </div>
        <div class="col-md-2">
          <button class="btn btn-primary w-100">
            <i class="bi bi-search me-1"></i>Filter
          </button>
        </div>
      </form>

      {{-- Cutoff tabs --}}
      <ul class="nav nav-pills brand-pills mb-3" role="tablist">
        <li class="nav-item">
          <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#cut1" type="button">
            First Cut-off (1–15)
          </button>
        </li>
        <li class="nav-item ms-2">
          <button class="nav-link" data-bs-toggle="tab" data-bs-target="#cut2" type="button">
            Second Cut-off (16–31)
          </button>
        </li>
      </ul>

      <div class="tab-content">
        {{-- Cutoff 1 --}}
        <div class="tab-pane fade show active" id="cut1">
          @include('payroll.partials.cutoff-table', ['rows' => $firstRows, 'label' => '1–15'])
        </div>

        {{-- Cutoff 2 --}}
        <div class="tab-pane fade" id="cut2">
          @include('payroll.partials.cutoff-table', ['rows' => $secondRows, 'label' => '16–31'])
        </div>
      </div>
    </div>
  </div>
</div>

@endsection
