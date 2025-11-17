@php
  $empId   = old('employee_id', isset($evaluation)? $evaluation->employee_id : null);
  $start   = old('period_start', isset($evaluation)? $evaluation->period_start?->toDateString() : '');
  $end     = old('period_end',   isset($evaluation)? $evaluation->period_end?->toDateString()   : '');
  $remarks = old('remarks', isset($evaluation)? $evaluation->remarks : '');
@endphp

<div class="row g-3">

  {{-- EVALUATION TYPE --}}
  <div class="col-12 col-md-6">
    <label class="form-label fw-semibold">Evaluation Type</label>
    <select name="type" id="evaluationType" class="form-select" required>
      <option value="">Select Evaluation Type</option>
      <option value="regular">Regular Evaluation</option>
      <option value="probationary">Probationary Evaluation</option>
      <option value="kpi">KPI-Based Evaluation</option>
      <option value="360">360° Leadership Evaluation</option>
    </select>
  </div>

  {{-- EMPLOYEE FIELD --}}
  <div class="col-12 col-md-6">
    <label class="form-label fw-semibold">Employee</label>
    <select name="employee_id" id="employeeSelect"
            class="form-select @error('employee_id') is-invalid @enderror"
            required disabled>
      <option value="">Select Employee</option>
    </select>
    @error('employee_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>

  {{-- PERIOD START / END --}}
  <div class="col-12 col-md-3">
    <label class="form-label fw-semibold">Period Start</label>
    <input type="date" name="period_start" value="{{ $start }}"
           class="form-control @error('period_start') is-invalid @enderror" required>
    @error('period_start') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>

  <div class="col-12 col-md-3">
    <label class="form-label fw-semibold">Period End</label>
    <input type="date" name="period_end" value="{{ $end }}"
           class="form-control @error('period_end') is-invalid @enderror" required>
    @error('period_end') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>

  {{-- INFO BAR --}}
  <div class="col-12">
    <div class="alert alert-info py-2 mb-2">
      Rate each criterion from <strong>1</strong> (Unsatisfactory) to <strong>5</strong> (Excellent).  
      Weighted total automatically computes to <strong>100%</strong>.
    </div>
    <p id="typeNote" class="text-muted small mb-0"></p>
  </div>

  {{-- EVALUATION TABLE --}}
  <div class="col-12">
    <div class="table-responsive">
      <table class="table table-sm table-bordered align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th style="width:32%">Review Area</th>
            <th class="text-center" style="width:8%">Weight</th>
            <th class="text-center" style="width:25%">Score (1–5)</th>
            <th>Notes</th>
          </tr>
        </thead>
        <tbody>
          @foreach($items as $it)
            @php
              $cur  = old("scores.$it->id", isset($evaluation) ? optional($evaluation->scores->firstWhere('item_id',$it->id))->score : null);
              $note = old("notes.$it->id",  isset($evaluation) ? optional($evaluation->scores->firstWhere('item_id',$it->id))->notes : null);
            @endphp
            <tr>
              <td>
                <div class="fw-semibold">{{ $it->name }}</div>
                @if($it->description)
                  <div class="small text-muted">{{ $it->description }}</div>
                @endif
              </td>
              <td class="text-center fw-semibold">{{ $it->weight }}%</td>
              <td class="text-center">
                <div class="d-flex justify-content-center gap-2 score-radio-group" data-item="{{ $it->id }}">
                  @for($i=1; $i<=5; $i++)
                    <label class="form-check form-check-inline m-0">
                      <input type="radio" 
                             class="form-check-input score-radio"
                             name="scores[{{ $it->id }}]"
                             value="{{ $i }}"
                             {{ (string)$cur===(string)$i ? 'checked' : '' }}
                             required>
                      <span class="small">{{ $i }}</span>
                    </label>
                  @endfor
                </div>
              </td>
              <td>
                <input name="notes[{{ $it->id }}]" value="{{ $note }}" 
                       class="form-control form-control-sm" placeholder="Optional notes">
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

  {{-- OVERALL SCORE --}}
  <div class="col-12 col-md-4 mt-3">
    <label class="form-label fw-semibold">Calculated Overall (%)</label>
    <input id="overallScore" class="form-control text-center fw-bold" value="0.00" readonly>
  </div>

  {{-- OVERALL REMARKS --}}
  <div class="col-12 mt-3">
    <label class="form-label fw-semibold">Overall Remarks</label>
    <textarea name="remarks" rows="3" class="form-control">{{ $remarks }}</textarea>
  </div>
</div>

{{-- ===================== JS ===================== --}}
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  const evalTypeSelect = document.getElementById('evaluationType');
  const employeeSelect = document.getElementById('employeeSelect');
  const overallField = document.getElementById('overallScore');
  const typeNote = document.getElementById('typeNote');
  const weights = @json($items->pluck('weight','id'));
  const radios = document.querySelectorAll('.score-radio');

  /** 🧮 Compute total normalized to 100% */
  function computeTotal() {
    const weightValues = Object.values(weights);
    const totalWeight = weightValues.reduce((a,b) => a + b, 0) || 100;
    let total = 0;

    document.querySelectorAll('.score-radio-group').forEach(group => {
      const itemId = group.dataset.item;
      const selected = group.querySelector('input[type="radio"]:checked');
      const val = selected ? parseInt(selected.value) : null;
      const weight = weights[itemId] || 0;
      if (!isNaN(val)) {
        const normalizedWeight = (weight / totalWeight) * 100;
        total += (val / 5) * normalizedWeight;
      }
    });

    overallField.value = total.toFixed(2) + '%';
  }

  /** 👤 Load employees by type */
  async function loadEmployeesByType(type) {
    employeeSelect.disabled = true;
    employeeSelect.innerHTML = `<option>Loading employees...</option>`;

    if (!type) {
      employeeSelect.innerHTML = `<option value="">Select Employee</option>`;
      employeeSelect.disabled = false;
      return;
    }

    try {
      const url = `{{ route('evaluations.filterEmployees') }}?type=${encodeURIComponent(type)}`;
      const response = await fetch(url, {
        method: 'GET',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin'
      });
      const text = await response.text();
      let data = [];
      try { data = JSON.parse(text); } catch (e) { console.error('⚠️ Non-JSON:', text); }

      employeeSelect.innerHTML = `<option value="">Select Employee</option>`;
      if (Array.isArray(data) && data.length) {
        data.forEach(emp => {
          const opt = document.createElement('option');
          opt.value = emp.id;
          opt.textContent = emp.name;
          employeeSelect.appendChild(opt);
        });
      } else {
        employeeSelect.innerHTML = `<option value="">No employees found</option>`;
      }
      console.log('✅ Employees loaded:', data);
    } catch (err) {
      console.error('❌ Error loading employees:', err);
      employeeSelect.innerHTML = `<option value="">Error loading employees</option>`;
    } finally {
      employeeSelect.disabled = false;
    }
  }

  /** 🧾 When evaluation type changes */
  evalTypeSelect.addEventListener('change', () => {
    const selectedText = evalTypeSelect.options[evalTypeSelect.selectedIndex].text;
    typeNote.textContent = selectedText
      ? `All evaluation criteria are shown for ${selectedText}.`
      : '';
    loadEmployeesByType(evalTypeSelect.value);
  });

  // 🧮 Recalculate whenever a score changes
  radios.forEach(r => r.addEventListener('change', computeTotal));

  // 🧾 Enable employee before submit
  document.querySelector('form').addEventListener('submit', () => {
    employeeSelect.disabled = false;
  });

  computeTotal();
});
</script>
@endpush
