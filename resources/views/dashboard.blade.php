{{-- resources/views/dashboard.blade.php --}}
@extends('layouts.app')

@section('page_title', 'Dashboard')

@section('content')
    <div class="container-fluid">

        {{-- ===== HR Top Summary Cards ===== --}}
        @role('hr')
            <div class="row g-3 mb-4">
                <div class="col-md-3 col-sm-6">
                    <div class="card h-100 border-0 shadow-sm brand-card">
                        <div class="card-body d-flex align-items-center text-white">
                            <i class="bi bi-check2-circle fs-2 me-3"></i>
                            <div>
                                <h5 class="mb-0 fw-bold">{{ $pendingUserCount }}</h5>
                                <small>User Approvals</small><br>
                                <a href="{{ route('approvals.index') }}" class="btn btn-sm btn-light mt-2 fw-semibold">Review</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-sm-6">
                    <div class="card h-100 border-0 shadow-sm brand-card">
                        <div class="card-body d-flex align-items-center text-white">
                            <i class="bi bi-person-dash fs-2 me-3"></i>
                            <div>
                                <h5 class="mb-0 fw-bold">{{ $absentCount }}</h5>
                                <small>Today's Absentees</small><br>
                                <a href="{{ route('attendance.index') }}" class="btn btn-sm btn-light mt-2 fw-semibold">View</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-sm-6">
                    <div class="card h-100 border-0 shadow-sm brand-card">
                        <div class="card-body d-flex align-items-center text-white">
                            <i class="bi bi-exclamation-triangle fs-2 me-3"></i>
                            <div>
                                <h5 class="mb-0 fw-bold">{{ $endingCount }}</h5>
                                <small>Contracts Ending</small><br>
                                <a href="{{ route('employees.endings') }}"
                                    class="btn btn-sm btn-light mt-2 fw-semibold">View</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-sm-6">
                    <div class="card h-100 border-0 shadow-sm brand-card">
                        <div class="card-body d-flex align-items-center text-white">
                            <i class="bi bi-file-earmark-text fs-2 me-3"></i>
                            <div>
                                <h5 class="mb-0 fw-bold">{{ $loanEndingCount }}</h5>
                                <small>Loans Ending Soon</small><br>
                                <a href="{{ route('loans.index') }}" class="btn btn-sm btn-light mt-2 fw-semibold">View</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endrole

        {{-- ===== Analytics Section ===== --}}
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <h5 class="fw-bold text-brand mb-0">Analytics</h5>
                    <a href="{{ route('reports.analytics') }}" class="btn btn-sm btn-outline-primary">Open full
                        Analytics</a>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-sm-6 col-md-3">
                        <div class="kpi-card">
                            <div class="kpi">
                                <div class="num" id="kpiHeadcount">--</div>
                                <div>
                                    <div>Headcount</div>
                                    <small class="text-muted">current</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-6 col-md-3">
                        <div class="kpi-card">
                            <div class="kpi">
                                <div class="num" id="kpiAbs">--%</div>
                                <div>
                                    <div>Absenteeism</div>
                                    <small class="text-muted">latest month</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Charts --}}
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="card border-0 p-3 shadow-sm h-100">
                            <div class="fw-semibold text-brand mb-2">Headcount Trend</div>
                            <canvas id="miniHeadcount" class="mini-chart"></canvas>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card border-0 p-3 shadow-sm h-100">
                            <div class="fw-semibold text-brand mb-2">Offboarding</div>
                            <canvas id="miniTurnover" class="mini-chart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== Announcements & Reminders ===== --}}
        <div class="row g-4 mb-5">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-brand text-white fw-semibold">Latest Announcements</div>
                    <ul class="list-group list-group-flush">
                        @forelse($announcements as $a)
                            <li class="list-group-item">
                                <a href="{{ route('announcements.show', $a) }}" data-view-announcement>
                                    <strong>{{ $a->title }}</strong>
                                </a><br>
                                <small
                                    class="text-muted">{{ ($a->published_at ?? $a->created_at)->format('M d, Y') }}</small>
                            </li>
                        @empty
                            <li class="list-group-item text-center text-muted py-4">No announcements yet.</li>
                        @endforelse
                    </ul>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-brand text-white fw-semibold">Reminders</div>
                    <div class="card-body">
                        @if ($birthdays->isEmpty() && $anniversaries->isEmpty())
                            <p class="text-muted mb-0">No upcoming birthdays or anniversaries.</p>
                        @else
                            @if ($birthdays->isNotEmpty())
                                <p class="fw-semibold text-brand mb-1">{{ $birthdays->count() }} Upcoming
                                    Birthday{{ $birthdays->count() > 1 ? 's' : '' }}</p>
                                <ul class="mb-3">
                                    @foreach ($birthdays as $b)
                                        <li>{{ $b->name }} — {{ $b->dob->format('M d') }}</li>
                                    @endforeach
                                </ul>
                            @endif

                            @if ($anniversaries->isNotEmpty())
                                <p class="fw-semibold text-brand mb-1">{{ $anniversaries->count() }} Work
                                    Anniversar{{ $anniversaries->count() > 1 ? 'ies' : 'y' }}</p>
                                <ul class="mb-0">
                                    @foreach ($anniversaries as $a)
                                        <li>{{ $a->name }} — {{ $a->employment_start_date->format('M d') }}
                                            ({{ $a->service_years }} yrs)
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        @endif
                    </div>
                </div>
            </div>

            {{-- ========================================= --}}
            {{-- =============== OT REQUEST =============== --}}
            {{-- ========================================= --}}

            <div class="col-md-12 mb-5">
                <div class="card shadow-sm">
                    <div class="card-header bg-brand text-white fw-semibold">OT Request</div>

                    <div class="card-body">

                        @if ($overtimeRequests->isEmpty())
                            <p class="text-muted">No overtime requests found.</p>
                        @else
                            <p class="fw-semibold text-brand mb-3">
                                {{ $overtimeRequests->count() }} Overtime
                                Request{{ $overtimeRequests->count() > 1 ? 's' : '' }}
                            </p>

                            <ul class="list-unstyled mb-0">
                                @foreach ($overtimeRequests as $ot)
                                    <li class="mb-2 d-flex justify-content-between align-items-center">

                                        <div>
                                            {{ $ot->starts_at->format('M d, Y h:i A') }} →
                                            {{ $ot->ends_at->format('M d, Y h:i A') }}
                                            —
                                            <span class="fw-semibold text-capitalize">{{ $ot->status }}</span>
                                        </div>

                                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal"
                                            data-bs-target="#approveOTModal" data-id="{{ $ot->id }}"
                                            data-start="{{ $ot->starts_at->format('M d, Y h:i A') }}"
                                            data-end="{{ $ot->ends_at->format('M d, Y h:i A') }}">
                                            Review
                                        </button>

                                    </li>
                                @endforeach
                            </ul>

                        @endif

                    </div>
                </div>
            </div>
        </div>

        {{-- APPROVE/REJECT OT MODAL --}}
        <div class="modal fade" id="approveOTModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <form method="POST" id="otForm">
                    @csrf
                    @method('PUT')

                    <input type="hidden" name="overtime_request_id" id="ot_id">

                    <div class="modal-content">
                        <div class="modal-header bg-brand text-white">
                            <h5 class="modal-title">Review Overtime Request</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body">
                            <p class="fw-semibold text-brand">Requested Time:</p>
                            <p id="ot_range" class="mb-3"></p>

                            <label class="fw-semibold">Action</label>
                            <select name="status" class="form-select" required>
                                <option value="">Select</option>
                                <option value="1">Approve</option>
                                <option value="2">Reject</option>
                            </select>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-brand text-white">Submit</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>


        @include('components.announcement-viewer')
    @endsection

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

        <script>
            /* ============================
           Mini Analytics Loader
        ============================ */
            (async () => {
                try {
                    const res = await fetch('{{ route('dashboard.analytics.json') }}', {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    const {
                        cards,
                        series
                    } = await res.json();

                    document.getElementById('kpiHeadcount').textContent =
                        (cards.headcount_now ?? 0).toLocaleString();

                    document.getElementById('kpiAbs').textContent =
                        ((cards.absenteeism_pct ?? 0).toFixed(2)) + '%';

                    const brand = getComputedStyle(document.documentElement).getPropertyValue('--brand').trim();
                    const brandDark = getComputedStyle(document.documentElement).getPropertyValue('--brand-darker')
                        .trim();

                    // Headcount chart
                    new Chart(document.getElementById('miniHeadcount'), {
                        type: 'line',
                        data: {
                            labels: (series.headcount || []).map(x => x.month),
                            datasets: [{
                                label: 'Headcount',
                                data: (series.headcount || []).map(x => x.total),
                                borderColor: brandDark,
                                backgroundColor: brand + '20',
                                borderWidth: 2,
                                tension: .3,
                                fill: true
                            }]
                        },
                        options: {
                            plugins: {
                                legend: {
                                    display: false
                                }
                            }
                        }
                    });

                    // Turnover chart
                    new Chart(document.getElementById('miniTurnover'), {
                        type: 'bar',
                        data: {
                            labels: (series.turnover || []).map(x => x.month),
                            datasets: [{
                                label: 'Offboarding',
                                data: (series.turnover || []).map(x => x.separations),
                                backgroundColor: brand,
                                borderColor: brandDark,
                                borderWidth: 1
                            }]
                        },
                        options: {
                            plugins: {
                                legend: {
                                    display: false
                                }
                            }
                        }
                    });

                } catch (e) {
                    console.error('Mini analytics failed:', e);
                }
            })();
        </script>


        <script>
            /* ============================
           OT Modal Dynamic Loader
           Sets form action based on clicked request
        ============================ */
            document.getElementById('approveOTModal')
                .addEventListener('show.bs.modal', function(event) {

                    let button = event.relatedTarget;

                    let id = button.getAttribute('data-id');
                    let start = button.getAttribute('data-start');
                    let end = button.getAttribute('data-end');

                    // Fill hidden ID
                    document.getElementById('ot_id').value = id;

                    // Display the requested range
                    document.getElementById('ot_range').textContent = `${start} → ${end}`;

                    // Dynamically set form action
                    let form = document.getElementById('otForm');
                    form.action = `payroll/overtime-request/change-status/${id}`;
                });
        </script>
    @endpush
