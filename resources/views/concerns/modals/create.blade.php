<div class="modal fade" id="addConcernModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <form method="POST" action="{{ route('concerns.store') }}" enctype="multipart/form-data" class="modal-content">
      @csrf

      <div class="modal-header">
        <h5 class="modal-title">
          <i class="bi bi-exclamation-circle me-2"></i> Submit Work-Related Concern
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">

        {{-- Employees auto-fill --}}
        @if(auth()->user()->employee)
            <input type="hidden" name="employee_id" value="{{ auth()->user()->employee->id }}">
        @else
        {{-- HR: Select an employee --}}
        <div class="col-md-12 mb-3">
          <label class="form-label fw-semibold">Employee</label>
          <select name="employee_id" class="form-select" required>
            <option value="">-- Select Employee --</option>
            @foreach($employees as $emp)
              <option value="{{ $emp->id }}">{{ $emp->full_name }} ({{ $emp->employee_code }})</option>
            @endforeach
          </select>
        </div>
        @endif


        <div class="row g-3">

          {{-- Category --}}
          <div class="col-md-6">
            <label class="form-label fw-semibold">Concern Category</label>
            <select name="concern_category_id" class="form-select" required>
              <option value="">-- Select Category --</option>
              @foreach($categories as $cat)
                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
              @endforeach
            </select>
          </div>

          {{-- Subject --}}
          <div class="col-md-6">
            <label class="form-label fw-semibold">Subject</label>
            <input type="text" name="subject" class="form-control" required>
          </div>

          {{-- Description --}}
          <div class="col-md-12">
            <label class="form-label fw-semibold">Description</label>
            <textarea name="description" class="form-control" rows="4" required></textarea>
          </div>

          {{-- Attachment --}}
          <div class="col-md-12">
            <label class="form-label fw-semibold">Attachment (optional)</label>
            <input type="file" name="attachment" class="form-control">
          </div>

          {{-- Confidential --}}
          <div class="col-md-12">
            <div class="form-check mt-2">
              <input class="form-check-input" type="checkbox" name="is_confidential" value="1">
              <label class="form-check-label">Mark as Confidential</label>
            </div>
          </div>

        </div>

      </div>

      <div class="modal-footer">
        <button class="btn btn-primary">
          <i class="bi bi-send me-1"></i> Submit Concern
        </button>
      </div>

    </form>
  </div>
</div>
