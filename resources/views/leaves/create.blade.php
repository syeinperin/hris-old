<div class="modal fade" id="leaveCreateModal" tabindex="-1" aria-labelledby="leaveCreateModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow-sm">
      <form action="{{ route('leaves.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="modal-header bg-white border-bottom">
          <h5 class="modal-title">
            <i class="bi bi-calendar-plus me-2"></i> New Leave Request
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="row g-3">

            {{-- Leave Type --}}
            <div class="col-md-6">
              <label for="leave_type_id" class="form-label">Leave Type <span class="text-danger">*</span></label>
              @php
                $employee = Auth::user()->employee;
                $gender = strtolower(optional($employee)->gender);
              @endphp

              <select name="leave_type_id" id="leave_type_id" class="form-select" required>
                <option value="">Choose...</option>
                @foreach($types as $type)
                  @php
                    $key = strtolower($type->key ?? $type->name);
                    $isMaternity = str_contains($key, 'maternity');
                    $isPaternity = str_contains($key, 'paternity');
                  @endphp

                  @if(($gender === 'female' && !$isPaternity) || ($gender === 'male' && !$isMaternity) || (!$isMaternity && !$isPaternity))
                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                  @endif
                @endforeach
              </select>
            </div>

            {{-- Dates --}}
            <div class="col-md-3">
              <label for="start_date" class="form-label">From <span class="text-danger">*</span></label>
              <input type="date" name="start_date" id="start_date" class="form-control" required>
            </div>

            <div class="col-md-3">
              <label for="end_date" class="form-label">To <span class="text-danger">*</span></label>
              <input type="date" name="end_date" id="end_date" class="form-control" required>
            </div>

            {{-- Reason --}}
            <div class="col-12">
              <label for="reason" class="form-label">Reason <span class="text-danger">*</span></label>
              <textarea name="reason" id="reason" rows="3" class="form-control" placeholder="Enter your reason..." required></textarea>
            </div>

            {{-- Supporting Document --}}
            <div class="col-12">
              <label for="attachment" class="form-label">Supporting Document <span class="text-danger">*</span></label>
              <input type="file" name="attachment" id="attachment" class="form-control"
                     accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required>
              <small class="text-muted d-block mt-2">
                📎 Please upload a valid document such as:
                <br>• Medical certificate (for sick leave)
                <br>• Birthday, wedding, or event invitation (for personal leave)
                <br>• Any proof of leave request
                <br><strong>Accepted formats:</strong> PDF, JPG, PNG, DOC, DOCX — Max 2MB
              </small>
            </div>

          </div>
        </div>

        <div class="modal-footer bg-light border-top">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-send me-1"></i> Submit Request
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const leaveSelect = document.getElementById('leave_type_id');
    const submitBtn = document.querySelector('#leaveCreateModal button[type="submit"]');

    // 🟡 Create the balance warning dynamically
    const warningDiv = document.createElement('div');
    warningDiv.classList.add('alert', 'mt-2', 'p-2');
    warningDiv.style.display = 'none';
    leaveSelect.parentNode.appendChild(warningDiv);

    leaveSelect.addEventListener('change', function() {
        const typeId = this.value;
        warningDiv.style.display = 'none';
        submitBtn.disabled = false;

        // Reset alert style each time
        warningDiv.classList.remove('alert-warning', 'alert-success');
        warningDiv.textContent = '';

        if (!typeId) return;

        fetch(`/schedule/leaves/check-balance/${typeId}`)
            .then(res => res.json())
            .then(data => {
                if (data.error) {
                    warningDiv.textContent = '⚠️ ' + data.error;
                    warningDiv.classList.add('alert-warning');
                    warningDiv.style.display = 'block';
                    submitBtn.disabled = true;
                } else {
                    const balance = parseFloat(data.balance);
                    if (balance <= 0) {
                        warningDiv.textContent = '⚠️ You have no remaining leave credits for this type.';
                        warningDiv.classList.add('alert-warning');
                        warningDiv.style.display = 'block';
                        submitBtn.disabled = true;
                    } else {
                        warningDiv.innerHTML = `✅ You have <strong>${balance}</strong> remaining day(s).`;
                        warningDiv.classList.add('alert-success');
                        warningDiv.style.display = 'block';
                        submitBtn.disabled = false;
                    }
                }
            })
            .catch(() => {
                warningDiv.textContent = '⚠️ Error checking balance.';
                warningDiv.classList.add('alert-warning');
                warningDiv.style.display = 'block';
                submitBtn.disabled = true;
            });
    });
});
</script>
@endpush
