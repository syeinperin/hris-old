@extends('layouts.app')

@section('page_title', 'Edit Payroll for ' . ($employee->name ?? 'Employee'))

@section('content')
<div class="container py-3">
  <div class="card shadow-sm">
    <div class="card-header bg-white">
      <h4 class="mb-0">Edit Payroll — {{ $employee->name ?? 'Employee' }}</h4>
      <small class="text-muted">Date: {{ $date ?? '—' }}</small>
    </div>

    <form method="POST" 
          action="{{ route('payroll.employee.updateByDate', ['employee' => $employee->id, 'date' => $date]) }}">
      @csrf
      @method('PUT')

      <div class="card-body row g-3">
        <div class="col-md-4">
          <label class="form-label">Worked Hours</label>
          <input type="number" step="0.01" name="worked_hours" class="form-control"
                 value="{{ old('worked_hours', $payroll->worked_hours ?? 0) }}">
        </div>

        <div class="col-md-4">
          <label class="form-label">Overtime Hours</label>
          <input type="number" step="0.01" name="ot_hours" class="form-control"
                 value="{{ old('ot_hours', $payroll->ot_hours ?? 0) }}">
        </div>

        <div class="col-md-4">
          <label class="form-label">Overtime Pay (₱)</label>
          <input type="number" step="0.01" name="ot_pay" class="form-control"
                 value="{{ old('ot_pay', $payroll->ot_pay ?? 0) }}">
        </div>

        <div class="col-md-4">
          <label class="form-label">Night Diff. Hours</label>
          <input type="number" step="0.01" name="nd_hours" class="form-control"
                 value="{{ old('nd_hours', $payroll->nd_hours ?? 0) }}">
        </div>

        <div class="col-md-4">
          <label class="form-label">Night Diff. Pay (₱)</label>
          <input type="number" step="0.01" name="nd_pay" class="form-control"
                 value="{{ old('nd_pay', $payroll->nd_pay ?? 0) }}">
        </div>

        <div class="col-md-4">
          <label class="form-label">Holiday Pay (₱)</label>
          <input type="number" step="0.01" name="holiday_pay" class="form-control"
                 value="{{ old('holiday_pay', $payroll->holiday_pay ?? 0) }}">
        </div>

        <div class="col-md-4">
          <label class="form-label">Late Deduction (₱)</label>
          <input type="number" step="0.01" name="late_deduction" class="form-control"
                 value="{{ old('late_deduction', $payroll->late_deduction ?? 0) }}">
        </div>

        <div class="col-md-4">
          <label class="form-label">Personal Loan (₱)</label>
          <input type="number" step="0.01" name="personal_loan" class="form-control"
                 value="{{ old('personal_loan', $payroll->personal_loan ?? 0) }}">
        </div>

        <div class="col-md-4">
          <label class="form-label">Govt Deduction (₱)</label>
          <input type="number" step="0.01" name="govt_deduction" class="form-control"
                 value="{{ old('govt_deduction', $payroll->govt_deduction ?? 0) }}">
        </div>

        <div class="col-md-6">
          <label class="form-label fw-bold">Gross Amount (₱)</label>
          <input type="number" step="0.01" name="gross_amount" class="form-control"
                 value="{{ old('gross_amount', $payroll->gross_amount ?? 0) }}">
        </div>

        <div class="col-md-6">
          <label class="form-label fw-bold">Net Amount (₱)</label>
          <input type="number" step="0.01" name="net_amount" class="form-control"
                 value="{{ old('net_amount', $payroll->net_amount ?? 0) }}">
        </div>
      </div>

      <div class="card-footer bg-light d-flex justify-content-between">
        <a href="{{ route('payroll.show', $employee->id) }}" class="btn btn-outline-secondary">
          ← Back
        </a>
        <button type="submit" class="btn btn-primary">💾 Save Changes</button>
      </div>
    </form>
  </div>
</div>
@endsection
