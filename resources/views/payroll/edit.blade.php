@extends('layouts.app')

@section('page_title', 'Edit Payroll Entry')

@section('content')
<div class="container py-3">
  <div class="card shadow-sm">
    <div class="card-header bg-white d-flex align-items-center justify-content-between">
      <h4 class="mb-0">
        <i class="bi bi-pencil-square me-2"></i>
        Edit Payroll — {{ $employee->name ?? 'Employee' }}
      </h4>
    </div>

<form method="POST"
      action="{{ $payslip->exists
                    ? route('payroll.update', $payslip->id)
                    : route('payroll.store') }}">
  @csrf
  @if($payslip->exists)
      @method('PUT')
  @endif


      {{-- Hidden Fields --}}
      <input type="hidden" name="employee_id" value="{{ $employee->id }}">
      <input type="hidden" id="rate_per_hour" value="{{ optional($employee->designation)->rate_per_hour ?? 0 }}">

      <div class="card-body row g-3">

        {{-- 🗓 Date --}}
        @php
          use Carbon\Carbon;
          $rawDate = old('date') ?? ($payslip->date ?? $date ?? now());
          if (is_string($rawDate)) {
              $dateValue = $rawDate;
          } elseif ($rawDate instanceof Carbon) {
              $dateValue = $rawDate->format('Y-m-d');
          } else {
              try {
                  $dateValue = Carbon::parse($rawDate)->format('Y-m-d');
              } catch (\Exception $e) {
                  $dateValue = now()->format('Y-m-d');
              }
          }
        @endphp

        <div class="col-md-4">
          <label class="form-label fw-semibold">Date</label>
          <input type="date" name="date" class="form-control" value="{{ $dateValue }}" required>
        </div>

        {{-- 🕒 Worked Hours --}}
        <div class="col-md-4">
          <label class="form-label fw-semibold">Worked Hours</label>
          <input type="number" step="0.01" name="worked_hours" class="form-control calc"
                 value="{{ old('worked_hours', $payslip->worked_hours ?? '') }}">
        </div>

        {{-- ⏰ Overtime Hours --}}
        <div class="col-md-4">
          <label class="form-label fw-semibold">Overtime Hours</label>
          <input type="number" step="0.01" name="ot_hours" class="form-control calc"
                 value="{{ old('ot_hours', $payslip->ot_hours ?? '') }}">
        </div>

        {{-- 💰 Overtime Pay (auto) --}}
        <div class="col-md-4">
          <label class="form-label fw-semibold">Overtime Pay</label>
          <input type="number" step="0.01" name="ot_pay" id="ot_pay" 
                 class="form-control readonly" readonly
                 value="{{ old('ot_pay', $payslip->ot_pay ?? '') }}">
        </div>

        {{-- 🌙 Night Diff Hours --}}
        <div class="col-md-4">
          <label class="form-label fw-semibold">Night Diff. Hours</label>
          <input type="number" step="0.01" name="nd_hours" class="form-control calc"
                 value="{{ old('nd_hours', $payslip->nd_hours ?? '') }}">
        </div>

        {{-- 🌙 Night Diff Pay (auto) --}}
        <div class="col-md-4">
          <label class="form-label fw-semibold">Night Diff. Pay</label>
          <input type="number" step="0.01" name="nd_pay" id="nd_pay" 
                 class="form-control readonly" readonly
                 value="{{ old('nd_pay', $payslip->nd_pay ?? '') }}">
        </div>

        {{-- 🎉 Holiday Pay --}}
        <div class="col-md-4">
          <label class="form-label fw-semibold">Holiday Pay</label>
          <input type="number" step="0.01" name="holiday_pay" class="form-control calc"
                 value="{{ old('holiday_pay', $payslip->holiday_pay ?? '') }}">
        </div>

        {{-- ⏳ Late Deduction --}}
        <div class="col-md-4">
          <label class="form-label fw-semibold">Late Deduction</label>
          <input type="number" step="0.01" name="late_deduction" class="form-control calc"
                 value="{{ old('late_deduction', $payslip->late_deduction ?? 0) }}">
        </div>

        {{-- 💸 Loan Deduction --}}
        <div class="col-md-4">
          <label class="form-label fw-semibold">Loan Deduction</label>
          <input type="number" step="0.01" name="personal_loan" class="form-control calc"
                 value="{{ old('personal_loan', $payslip->personal_loan ?? 0) }}">
        </div>

        {{-- 🏛 Govt Deduction --}}
        <div class="col-md-4">
          <label class="form-label fw-semibold">Government Deduction</label>
          <input type="number" step="0.01" name="govt_deduction" class="form-control calc"
                 value="{{ old('govt_deduction', $payslip->govt_deduction ?? 0) }}">
        </div>

        {{-- 💵 Gross Amount (auto) --}}
        <div class="col-md-4">
          <label class="form-label fw-semibold">Gross Amount</label>
          <input type="number" step="0.01" name="gross_amount" id="gross_amount"
                 class="form-control readonly" readonly
                 value="{{ old('gross_amount', $payslip->gross_amount ?? '') }}">
        </div>

        {{-- 🧾 Net Amount (auto) --}}
        <div class="col-md-4">
          <label class="form-label fw-semibold">Net Amount</label>
          <input type="number" step="0.01" name="net_amount" id="net_amount"
                 class="form-control readonly" readonly
                 value="{{ old('net_amount', $payslip->net_amount ?? '') }}">
        </div>

        {{-- 🗒 Remarks --}}
        <div class="col-md-12">
          <label class="form-label fw-semibold">Remarks (Optional)</label>
          <textarea name="remarks" class="form-control" rows="2"
                    placeholder="Add any notes or remarks...">{{ old('remarks', $payslip->remarks ?? '') }}</textarea>
        </div>
      </div>

      {{-- Footer --}}
      <div class="card-footer bg-light d-flex justify-content-end gap-2">
        <a href="{{ route('payroll.show', $employee->id) }}" class="btn btn-secondary">← Back</a>
        <button type="submit" class="btn btn-primary">
          💾 {{ isset($payslip) ? 'Update' : 'Save' }}
        </button>
      </div>
    </form>
  </div>
</div>
@endsection

{{-- 💡 Inline Auto-Calculation Script --}}
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
  const rate = parseFloat(document.getElementById('rate_per_hour').value || 0);

  function recalc() {
    const worked = parseFloat(document.querySelector('[name="worked_hours"]').value || 0);
    const ot = parseFloat(document.querySelector('[name="ot_hours"]').value || 0);
    const nd = parseFloat(document.querySelector('[name="nd_hours"]').value || 0);
    const holiday = parseFloat(document.querySelector('[name="holiday_pay"]').value || 0);
    const late = parseFloat(document.querySelector('[name="late_deduction"]').value || 0);
    const loan = parseFloat(document.querySelector('[name="personal_loan"]').value || 0);
    const govt = parseFloat(document.querySelector('[name="govt_deduction"]').value || 0);

    // ✅ Accurate calculations
    const basePay = worked * rate;
    const otPay = ot * rate * 1.25;
    const ndPay = nd * rate * 0.10;
    const gross = basePay + otPay + ndPay + holiday;
    const net = gross - (late + loan + govt);

    document.getElementById('ot_pay').value = otPay.toFixed(2);
    document.getElementById('nd_pay').value = ndPay.toFixed(2);
    document.getElementById('gross_amount').value = gross.toFixed(2);
    document.getElementById('net_amount').value = net.toFixed(2);
  }

  document.querySelectorAll('.calc').forEach(el => el.addEventListener('input', recalc));
  recalc(); // initial compute
});
</script>
@endpush

@push('styles')
<style>
.readonly {
  background-color: #f8f9fa !important;
  cursor: not-allowed;
}
</style>
@endpush
