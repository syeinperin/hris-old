@extends('layouts.app')
@section('page_title','Analytics & Dashboards')

@push('styles')
<style>
  .hero {
    background:#111827;
    color:#fff;
    border-radius:16px;
    padding:20px 22px;
    margin-bottom:18px
  }
  .hero h2 { margin:0 0 6px; font-weight:800 }
  .muted { opacity:.85 }
  .grid {
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(280px,1fr));
    gap:16px
  }
  .card {
    background:#fff;
    border:1px solid #eef0f6;
    border-radius:14px;
    padding:14px
  }
  .card h5 { margin:0 0 10px; font-weight:700 }
  canvas { width:100% !important; height:280px !important }
</style>
@endpush

@push('scripts')
{{-- Load Chart.js --}}
<script src="{{ asset('js/chart.min.js') }}"></script>

<script>
document.addEventListener("DOMContentLoaded", () => {
  console.log("✅ Chart.js loaded?", typeof Chart);

  if (typeof Chart === "undefined") {
    console.error("❌ Chart.js not loaded");
    return;
  }

  // Helper: groupBy
  const groupBy = (arr, key) =>
    arr.reduce((m, x) => ((m[x[key]] ??= []).push(x), m), {});

  // Data from backend
  const headcount = @json($headcount);
  const turnover  = @json($turnover);
  const tth       = @json($timeToHire);
  const abs       = @json($absenteeism);
  const ot        = @json($otCost);
  const gender    = @json($genderMix);

  console.log({ headcount, turnover, tth, abs, ot, gender });

  // ─── Headcount ───
  (() => {
    if (!headcount.length) return;
    const g = groupBy(headcount, 'month');
    const months = [...new Set(headcount.map(x => x.month))].sort();
    const depts  = [...new Set(headcount.map(x => x.department))];
    const datasets = depts.map(dep => ({
      label: dep || 'Unassigned',
      data: months.map(m => (g[m]||[]).find(x => x.department === dep)?.headcount || 0),
      borderWidth: 1
    }));
    new Chart(document.getElementById('chartHeadcount'), {
      type: 'bar',
      data: { labels: months, datasets },
      options: { responsive:true, plugins:{legend:{position:'bottom'}}, 
        scales:{x:{stacked:true},y:{stacked:true,beginAtZero:true}} }
    });
  })();

  // ─── Turnover ───
  (() => {
    if (!turnover.length) return;
    const labels = turnover.map(x => x.month);
    const data   = turnover.map(x => Number(x.separations));
    new Chart(document.getElementById('chartTurnover'), {
      type: 'line',
      data: { labels, datasets: [{ label:'Separations', data, borderWidth:2, fill:false }] },
      options: { responsive:true, plugins:{legend:{display:true}}, scales:{y:{beginAtZero:true}} }
    });
  })();

  // ─── Absenteeism ───
  (() => {
    if (!abs.length) return;
    const labels = abs.map(x => x.month);
    const data   = abs.map(x => Number(x.rate_pct).toFixed(2));
    new Chart(document.getElementById('chartAbs'), {
      type: 'line',
      data: { labels, datasets: [{ label:'% Absent', data, borderWidth:2, fill:false }] },
      options: { responsive:true, scales:{y:{beginAtZero:true,ticks:{callback:v=>v+'%'}} } }
    });
  })();

});
</script>
@endpush

@section('content')
<div class="container-fluid">
  <div class="hero">
    <h2>Analytics & dashboards</h2>
    <div class="muted">
      <div><strong>HR KPIs</strong> — Headcount & turnover, time-to-hire, absenteeism, OT cost, gender mix.</div>
      <div><em>Tech:</em> cached SQL views; lightweight charts on Reports → Analytics.</div>
    </div>
  </div>

  <div class="grid">
    <div class="card"><h5>Headcount by Department</h5><canvas id="chartHeadcount"></canvas></div>
    <div class="card"><h5>Offboarding</h5><canvas id="chartTurnover"></canvas></div>
    <div class="card"><h5>Absenteeism Rate (%)</h5><canvas id="chartAbs"></canvas></div>
  </div>
</div>
@endsection
