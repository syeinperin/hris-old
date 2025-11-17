@extends('layouts.app')
@section('page_title','Supervisor Scorecards')

@push('styles')
<style>
  .hero{background:#111827;color:#fff;border-radius:16px;padding:20px 22px;margin-bottom:18px}
  .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:16px}
  .card{background:#fff;border:1px solid #eef0f6;border-radius:14px;padding:14px}
  canvas{width:100% !important;height:280px !important}
  .table-sm td,.table-sm th{padding:.4rem .5rem}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endpush

@section('content')
<div class="container-fluid">
  <div class="hero">
    <h2 class="mb-1">Supervisor Scorecards</h2>
    <div>Team attendance, performance trend, pending actions.</div>
  </div>

  <div class="grid">
    <div class="card">
      <h5 class="mb-2">Team Attendance (Present %)</h5>
      <canvas id="chartTeam"></canvas>
    </div>
    <div class="card">
      <h5 class="mb-2">Performance Trend</h5>
      <canvas id="chartPerf"></canvas>
    </div>
    <div class="card">
      <h5 class="mb-2">Pending Actions</h5>
      <table class="table table-sm">
        <thead><tr><th>Type</th><th>Status</th><th>Due</th></tr></thead>
        <tbody>
          @forelse($pendingActions as $p)
            <tr>
              <td>{{ ucfirst($p->type) }}</td>
              <td><span class="badge bg-warning text-dark">{{ $p->status }}</span></td>
              <td>{{ $p->due_date }}</td>
            </tr>
          @empty
            <tr><td colspan="3" class="text-muted">No pending actions 🎉</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
const team = @json($teamAttendance);
const perf = @json($performanceTrend);

(() => {
  const labels = team.map(x => x.day);
  const data   = team.map(x => Number(x.present_pct).toFixed(2));
  new Chart(document.getElementById('chartTeam'), {
    type: 'line',
    data: { labels, datasets:[{ label:'Present %', data, borderWidth:2, fill:false }] },
    options: { responsive:true, scales:{ y:{beginAtZero:true, ticks:{callback:v=>v+'%'} } } }
  });
})();

(() => {
  const labels = perf.map(x => x.month);
  const data   = perf.map(x => Number(x.perf_score).toFixed(1));
  new Chart(document.getElementById('chartPerf'), {
    type: 'bar',
    data: { labels, datasets:[{ label:'Score', data, borderWidth:1 }] },
    options: { responsive:true, scales:{ y:{beginAtZero:true, max:100} } }
  });
})();
</script>
@endsection
