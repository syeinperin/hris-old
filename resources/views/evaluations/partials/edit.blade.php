{{-- evaluations/partials/edit.blade.php --}}
<div class="modal fade show" style="display:block;background:rgba(0,0,0,0.5);" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content shadow-lg border-0">
      <form action="{{ route('evaluations.update', $editEval) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="modal-header bg-white border-bottom">
          <h5 class="modal-title">
            <i class="bi bi-pencil-square me-2"></i>
            Edit Evaluation — {{ $editEval->employee->name }}
          </h5>
          <a href="{{ route('evaluations.index') }}" class="btn-close"></a>
        </div>

        {{-- Set a fixed max-height for modal-body for scrolling --}}
        <div class="modal-body" style="max-height:70vh;overflow-y:auto;">
          @include('evaluations.partials.form', [
            'evaluation' => $editEval,
            'mode' => 'edit',
            'items' => $items
          ])
        </div>

        <div class="modal-footer bg-light border-top">
          <button type="submit" class="btn btn-success">
            <i class="bi bi-check2-circle me-1"></i> Update
          </button>
          <a href="{{ route('evaluations.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>
