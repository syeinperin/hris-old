{{-- resources/views/evaluations/partials/show-content.blade.php --}}
@php
  $totalWeighted = $evaluation->scores->sum('weighted_score');
@endphp

<div class="container-fluid px-2 py-2">
  {{-- Summary Card --}}
  <div class="card shadow-sm mb-3 border-0">
    <div class="card-body">
      <div class="row gy-2">
        <div class="col-md-6">
          <p class="mb-1"><strong>Evaluator:</strong> {{ $evaluation->evaluator->name ?? '—' }}</p>
          <p class="mb-1"><strong>Period:</strong>
            {{ $evaluation->period_start->toFormattedDateString() }}
            – {{ $evaluation->period_end->toFormattedDateString() }}
          </p>
        </div>
        <div class="col-md-6">
          <p class="mb-1">
            <strong>Overall Score:</strong>
            <span class="fw-bold text-primary">{{ number_format($evaluation->overall_score, 2) }}%</span>
          </p>
          <p class="mb-1"><strong>Remarks:</strong> {{ $evaluation->remarks ?: '—' }}</p>
        </div>
      </div>
    </div>
  </div>

  {{-- Breakdown Table --}}
  <div class="card shadow-sm border-0">
    <div class="card-header bg-light fw-semibold">
      <i class="bi bi-clipboard-data me-1"></i> Evaluation Breakdown
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-striped table-bordered align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Review Area</th>
              <th class="text-center" style="width:8%">Weight</th>
              <th class="text-center" style="width:10%">Score</th>
              <th class="text-center" style="width:12%">Weighted</th>
              <th>Notes</th>
            </tr>
          </thead>
          <tbody>
            @foreach($evaluation->scores as $s)
              <tr>
                <td class="fw-semibold">{{ $s->item->name }}</td>
                <td class="text-center">{{ $s->weight_cache }}%</td>
                <td class="text-center">{{ $s->score }}</td>
                <td class="text-center">{{ number_format($s->weighted_score, 2) }}</td>
                <td>{{ $s->notes ?: '—' }}</td>
              </tr>
            @endforeach
          </tbody>
          <tfoot class="table-light">
            <tr class="fw-bold">
              <td colspan="3" class="text-end">Total Weighted Score</td>
              <td class="text-center text-primary">{{ number_format($totalWeighted, 2) }}</td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>
</div>
