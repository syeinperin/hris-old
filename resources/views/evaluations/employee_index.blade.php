@extends('layouts.app')
@section('page_title', 'My Evaluations')

@section('content')
<div class="container-fluid">
  <div class="card shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
      <h4 class="mb-0">
        <i class="bi bi-clipboard2-check me-2"></i> My Evaluations
      </h4>
    </div>

    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-hover table-bordered align-middle">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>Period</th>
              <th>Overall %</th>
              <th>Evaluator</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($evaluations as $e)
              <tr>
                <td>{{ $loop->iteration + ($evaluations->currentPage()-1)*$evaluations->perPage() }}</td>
                <td>{{ $e->period_start->toDateString() }} – {{ $e->period_end->toDateString() }}</td>
                <td class="fw-semibold">{{ number_format($e->overall_score, 2) }}%</td>
                <td>{{ $e->evaluator->name ?? '—' }}</td>
                <td>
                  <button type="button"
                          class="btn btn-sm btn-outline-primary js-view-eval"
                          data-id="{{ $e->id }}">
                    <i class="bi bi-eye"></i> View
                  </button>
                </td>
              </tr>
            @empty
              <tr><td colspan="5" class="text-center text-muted py-4">No evaluations yet.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>

      {{ $evaluations->links('pagination::bootstrap-5') }}
    </div>
  </div>
</div>

{{-- MODAL (empty, dynamically filled) --}}
<div class="modal fade" id="evaluationModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content shadow-lg border-0">
      <div class="modal-header bg-white border-bottom">
        <h5 class="modal-title">
          <i class="bi bi-eye me-2"></i> Evaluation Details
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center py-5">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  const modalEl = document.getElementById('evaluationModal');
  const modal = new bootstrap.Modal(modalEl);

  document.querySelectorAll('.js-view-eval').forEach(btn => {
    btn.addEventListener('click', async () => {
      const id = btn.dataset.id;
      const body = modalEl.querySelector('.modal-body');
      body.innerHTML = `
        <div class="text-center py-5">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
        </div>`;

      modal.show();

      try {
        const response = await fetch(`/evaluations/${id}?ajax=1`);
        const html = await response.text();
        body.innerHTML = html;
      } catch (err) {
        console.error(err);
        body.innerHTML = `<div class="alert alert-danger">Failed to load evaluation details.</div>`;
      }
    });
  });
});
</script>
@endpush
