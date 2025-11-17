@extends('layouts.app')

@section('page_title', 'Evaluation Details')

@section('content')
<div class="container-fluid">
  <div class="card shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
      <h4 class="mb-0">
        <i class="bi bi-clipboard2-data me-2"></i>
        Evaluation Details — {{ $evaluation->employee->name }}
      </h4>
      <a href="{{ route('evaluations.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back
      </a>
    </div>

    <div class="card-body">
      {{-- Evaluation Info --}}
      <div class="row mb-3">
        <div class="col-md-6">
          <p><strong>Evaluator:</strong> {{ $evaluation->evaluator->name ?? '—' }}</p>
          <p><strong>Period:</strong> {{ $evaluation->period_start->toDateString() }} – {{ $evaluation->period_end->toDateString() }}</p>
          <p><strong>Type:</strong> {{ ucfirst($evaluation->type) }}</p>
        </div>
        <div class="col-md-6">
          <p><strong>Overall Score:</strong>
            <span class="fw-bold text-primary">{{ number_format($evaluation->overall_score, 2) }}%</span>
            <span class="badge bg-{{ $evaluation->result_color }} ms-1">
              {{ $evaluation->result_label }}
            </span>
          </p>

          @if($evaluation->regularization_recommended)
            <p><strong>Recommendation:</strong> <span class="badge bg-success">Regularization Recommended</span></p>
          @elseif($evaluation->promotion_recommended)
            <p><strong>Recommendation:</strong> <span class="badge bg-primary">Promotion Recommended</span></p>
          @endif

          <p><strong>Remarks:</strong> {{ $evaluation->remarks ?: '—' }}</p>
        </div>
      </div>

      {{-- Scores Table --}}
      <div class="table-responsive">
        <table class="table table-sm table-bordered align-middle">
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
                <td>{{ $s->item->name }}</td>
                <td class="text-center">{{ $s->weight_cache }}%</td>
                <td class="text-center">{{ $s->score }}</td>
                <td class="text-center">{{ number_format($s->weighted_score, 2) }}</td>
                <td>{{ $s->notes ?: '—' }}</td>
              </tr>
            @endforeach
          </tbody>
          <tfoot class="table-light">
            <tr class="fw-semibold">
              <td colspan="3" class="text-end">Total Weighted Score</td>
              <td class="text-center">{{ number_format($evaluation->scores->sum('weighted_score'), 2) }}</td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection
