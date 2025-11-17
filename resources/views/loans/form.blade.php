@php
  $fv = fn($field, $fallback = '') => old($field, $fallback);
@endphp

{{-- 🔹 Global validation alert --}}
@if ($errors->any())
  <div class="alert alert-danger">
    <strong>⚠️ There were some problems with your submission:</strong>
    <ul class="mb-0 mt-1 small">
      @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif

<div class="mb-3">
  <label class="form-label">Employee *</label>
  <select name="employee_id" class="form-select" required>
    <option disabled {{ empty($loan) ? 'selected' : '' }}>— choose employee —</option>
    @foreach($employees as $id => $name)
      <option value="{{ $id }}" {{ $fv('employee_id', $loan->employee_id ?? '') == $id ? 'selected':'' }}>
        {{ $name }}
      </option>
    @endforeach
  </select>
  @error('employee_id')
    <small class="text-danger">{{ $message }}</small>
  @enderror
</div>

<div class="mb-3">
  <label class="form-label">Loan Type *</label>
  <select name="loan_type_id" class="form-select" required>
    <option disabled {{ empty($loan) ? 'selected' : '' }}>— choose type —</option>
    @foreach($types as $id => $name)
      <option value="{{ $id }}" {{ $fv('loan_type_id', $loan->loan_type_id ?? '') == $id ? 'selected':'' }}>
        {{ $name }}
      </option>
    @endforeach
  </select>
  @error('loan_type_id')
    <small class="text-danger">{{ $message }}</small>
  @enderror
</div>

<div class="mb-3">
  <label class="form-label">Loan Plan *</label>
  <select name="plan_id" id="planSelect" class="form-select" required>
    <option disabled selected>— choose plan —</option>
    @foreach($plans as $plan)
      <option value="{{ $plan->id }}"
              data-type="{{ $plan->deduction_type }}"
              data-rate="{{ $plan->interest_rate }}"
              {{ $fv('plan_id', $loan->plan_id ?? '') == $plan->id ? 'selected':'' }}>
        {{ $plan->name }} ({{ ucfirst($plan->deduction_type) }} @ {{ number_format($plan->interest_rate, 2) }}%)
      </option>
    @endforeach
  </select>
  <small class="text-muted">Choose how the loan will be deducted (semi-monthly, monthly, or one-time).</small>
  @error('plan_id')
    <small class="text-danger d-block">{{ $message }}</small>
  @enderror
</div>

<div class="mb-3">
  <label class="form-label">Principal Amount *</label>
  <input type="number" step="0.01" name="principal_amount" id="principalInput"
         class="form-control"
         value="{{ $fv('principal_amount', $loan->principal_amount ?? '') }}" required>
  @error('principal_amount')
    <small class="text-danger">{{ $message }}</small>
  @enderror
</div>

<div class="mb-3">
  <label class="form-label">Interest Rate (%) *</label>
  <input type="number" step="0.01" name="interest_rate" id="rateInput"
         class="form-control"
         value="{{ $fv('interest_rate', $loan->interest_rate ?? '') }}">
  <small class="text-muted">Auto-fills from selected plan.</small>
  @error('interest_rate')
    <small class="text-danger d-block">{{ $message }}</small>
  @enderror
</div>

<div class="mb-3">
  <label class="form-label">Number of Deductions *</label>
  <input type="number" name="term_months" id="termInput"
         class="form-control"
         value="{{ $fv('term_months', $loan->term_months ?? '') }}">
  <small class="text-muted">Auto-fills from plan but can be adjusted manually.</small>
  @error('term_months')
    <small class="text-danger d-block">{{ $message }}</small>
  @enderror
</div>

<div class="mb-3">
  <label class="form-label">Estimated Deduction per Cutoff</label>
  <input type="text" id="monthlyDisplay" class="form-control bg-light" readonly
         value="{{ isset($loan) ? number_format($loan->monthly_amount, 2) : '' }}">
  <small class="text-muted">Automatically computed as Total ÷ Number of Deductions.</small>
</div>

<div class="mb-3">
  <label class="form-label">Next Payment Date *</label>
  <input type="date" name="next_payment_date" id="nextPaymentInput" class="form-control"
         value="{{ $fv('next_payment_date', optional($loan)->next_payment_date?->toDateString()) }}" required>
  <small class="text-muted d-block">All loan deductions occur every 15th of the month.</small>
  @error('next_payment_date')
    <small class="text-danger">{{ $message }}</small>
  @enderror
</div>

<div class="mb-3">
  <label class="form-label">Release Date *</label>
  <input type="date" name="released_at" class="form-control"
         value="{{ $fv('released_at', optional($loan)->released_at?->toDateString()) }}" required>
  @error('released_at')
    <small class="text-danger">{{ $message }}</small>
  @enderror
</div>

<div class="mb-3">
  <label class="form-label">Status</label>
  <select name="status" class="form-select">
    @foreach(['active'=>'Active','paid'=>'Paid','defaulted'=>'Defaulted'] as $k=>$label)
      <option value="{{ $k }}" {{ $fv('status', $loan->status ?? 'active')===$k ? 'selected':'' }}>
        {{ $label }}
      </option>
    @endforeach
  </select>
  @error('status')
    <small class="text-danger">{{ $message }}</small>
  @enderror
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  const planSelect = document.getElementById('planSelect');
  const rateInput = document.getElementById('rateInput');
  const termInput = document.getElementById('termInput');
  const principalInput = document.getElementById('principalInput');
  const monthlyDisplay = document.getElementById('monthlyDisplay');
  const nextDateInput = document.getElementById('nextPaymentInput');

  // 🔹 Auto-calc monthly deduction
  function calculateMonthly() {
    const principal = parseFloat(principalInput.value) || 0;
    const rate = parseFloat(rateInput.value) || 0;
    const months = parseInt(termInput.value) || 1;

    if (principal > 0 && months > 0) {
      const total = principal * (1 + (rate / 100));
      const monthly = total / months;
      monthlyDisplay.value = monthly.toFixed(2);
    } else {
      monthlyDisplay.value = '';
    }
  }

  planSelect?.addEventListener('change', e => {
    const selected = e.target.selectedOptions[0];
    if (selected) {
      rateInput.value = selected.dataset.rate || '';
      termInput.value = selected.dataset.type === 'semi-monthly' ? 2 :
                        selected.dataset.type === 'monthly' ? 1 :
                        selected.dataset.type === 'quarterly' ? 3 : 1;
      calculateMonthly();
    }
  });

  [principalInput, rateInput, termInput].forEach(el => {
    el?.addEventListener('input', calculateMonthly);
  });

  // 🔹 Lock Next Payment Date to every 15th
  nextDateInput?.addEventListener('change', function () {
    const date = new Date(this.value);
    if (isNaN(date)) return;

    date.setDate(15); // force to 15th
    const yyyy = date.getFullYear();
    const mm = String(date.getMonth() + 1).padStart(2, '0');
    const dd = '15';
    this.value = `${yyyy}-${mm}-${dd}`;
  });

  calculateMonthly();
});
</script>
@endpush
