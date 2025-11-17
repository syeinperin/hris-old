{{-- resources/views/offboarding/create.blade.php --}}
<div class="modal fade" id="offboardModal" tabindex="-1" aria-labelledby="offboardModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content border-0 shadow-lg">

      <form action="{{ route('offboarding.store') }}" method="POST">
        @csrf

        {{-- Header --}}
        <div class="modal-header bg-brand text-white">
          <h5 class="modal-title" id="offboardModalLabel">
            <i class="bi bi-person-dash me-2"></i>Offboard Employee
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>

        {{-- Body --}}
        <div class="modal-body" style="max-height: calc(100vh - 220px); overflow-y: auto;">
          {{-- Employee --}}
          <div class="mb-3">
            <label class="form-label fw-semibold">Employee <span class="text-danger">*</span></label>
            <select name="employee_id" class="form-select" required>
              <option value="">Select employee...</option>
              @foreach($employees as $emp)
                <option value="{{ $emp->id }}">{{ $emp->employee_code }} — {{ $emp->name }}</option>
              @endforeach
            </select>
          </div>

          {{-- Type --}}
          <div class="mb-3">
            <label class="form-label fw-semibold">Offboarding Type <span class="text-danger">*</span></label>
            <select name="type" class="form-select" required>
              @foreach(['resignation','termination','endo','retirement','other'] as $t)
                <option value="{{ $t }}">{{ ucfirst($t) }}</option>
              @endforeach
            </select>
          </div>

          {{-- Effective Date --}}
          <div class="mb-3">
            <label class="form-label fw-semibold">Effective Date <span class="text-danger">*</span></label>
            <input type="date" name="effective_date" class="form-control" required>
          </div>

          {{-- Reason --}}
          <div class="mb-3">
            <label class="form-label fw-semibold">Reason <span class="text-danger">*</span></label>
            <input type="text" name="reason" class="form-control" placeholder="Enter reason" required>
          </div>

          {{-- Portal Access --}}
          <div class="mb-3">
            <label class="form-label fw-semibold">Allow Portal Access Until</label>
            <input type="date" name="allow_portal_access_until" class="form-control">
          </div>

          {{-- Asset Return --}}
          <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="company_asset_returned" value="1" id="assetReturned">
            <label class="form-check-label" for="assetReturned">All company assets returned</label>
          </div>

          {{-- Notes --}}
          <div class="mb-3">
            <label class="form-label fw-semibold">Notes (optional)</label>
            <textarea name="separation_notes" class="form-control" rows="4" placeholder="Any remarks..."></textarea>
          </div>
        </div>

        {{-- Footer --}}
        <div class="modal-footer bg-light border-0">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-check2-circle me-1"></i> Complete Offboarding
          </button>
        </div>

      </form>
    </div>
  </div>
</div>
