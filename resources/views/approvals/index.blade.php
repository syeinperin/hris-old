@extends('layouts.app')

@section('page_title','Approvals')

@push('styles')
<style>
  :root {
    --asiatex-primary: #26264e;
    --asiatex-accent: #3a3a84;
  }

  .table th, .table td { vertical-align: middle; }

  .badge-status {
    padding: .35rem .65rem;
    border-radius: 999px;
    font-size: .85rem;
    font-weight: 600;
  }

  .badge-pending { background: #ececff; color: var(--asiatex-primary); border: 1px solid var(--asiatex-primary); }
  .badge-approved { background: var(--asiatex-primary); color: #fff; }
  .badge-rejected { background: #b02a37; color: #fff; }

  .text-theme { color: var(--asiatex-accent) !important; font-weight: 600; }
  .text-theme:hover { text-decoration: underline; }

  .card-header h4 {
    font-size: 1.15rem;
    font-weight: 600;
    color: var(--asiatex-primary);
  }

  .btn-approve { background-color: var(--asiatex-primary); color: #fff; }
  .btn-approve:hover { background-color: var(--asiatex-accent); color: #fff; }

  .btn-reject { border: 1px solid #b02a37; color: #b02a37; }
  .btn-reject:hover { background-color: #b02a37; color: #fff; }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0"><i class="bi bi-check2-square me-2"></i> Approvals Overview</h3>
    <a href="{{ route('approvals.history') }}" class="btn btn-sm btn-outline-primary">
      <i class="bi bi-clock-history me-1"></i> View Approval History
    </a>
  </div>

  {{-- 🟦 Pending User Approvals --}}
  <div class="card shadow-sm mb-4">
    <div class="card-header bg-white d-flex align-items-center">
      <h4 class="mb-0"><i class="bi bi-person-check me-2"></i> Pending User Approvals</h4>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Name</th>
              <th>Email</th>
              <th>Requested</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($pendingUsers as $approval)
              @php $user = $approval->approvable; @endphp
              <tr>
                <td>{{ $user?->name }}</td>
                <td>{{ $user?->email }}</td>
                <td>{{ $approval->created_at->format('Y-m-d') }}</td>
                <td class="text-end">

                  {{-- FIXED: USER REJECT BUTTON --}}
                  <button type="button"
                          class="btn btn-sm btn-reject"
                          data-action="{{ route('approvals.destroy',['t'=>'user','id'=>$approval->id]) }}">
                    Reject
                  </button>

                  <form class="d-inline" method="POST"
                        action="{{ route('approvals.approve', ['t'=>'user','id'=>$approval->id]) }}">
                    @csrf
                    <button class="btn btn-sm btn-approve">Approve</button>
                  </form>

                </td>
              </tr>
            @empty
              <tr><td colspan="4" class="text-center text-muted py-4">No pending user requests.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  {{-- 🟩 Pending Leave Requests --}}
  <div class="card shadow-sm">
    <div class="card-header bg-white d-flex align-items-center">
      <h4 class="mb-0"><i class="bi bi-calendar-check me-2"></i> Pending Leave Requests</h4>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Employee</th>
              <th>Type</th>
              <th>From</th>
              <th>To</th>
              <th>Reason</th>
              <th>Document</th>
              <th>Requested</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($pendingLeaves as $approval)
              @php $leave = $approval->approvable; @endphp
              <tr>
                <td>{{ optional($leave?->user)->name ?? '—' }}</td>
                <td>{{ optional($leave?->type)->name ?? '—' }}</td>
                <td>{{ $leave?->start_date ? \Carbon\Carbon::parse($leave->start_date)->format('Y-m-d') : '—' }}</td>
                <td>{{ $leave?->end_date ? \Carbon\Carbon::parse($leave->end_date)->format('Y-m-d') : '—' }}</td>
                <td>{{ \Illuminate\Support\Str::limit($leave?->reason, 35, '…') }}</td>
                <td>
                  @if($leave?->attachment_path)
                    <a href="{{ asset('storage/'.$leave->attachment_path) }}" target="_blank" class="text-theme">View File</a>
                  @else
                    <span class="text-muted">—</span>
                  @endif
                </td>
                <td>{{ $approval->created_at->format('Y-m-d') }}</td>
                <td class="text-end">

                  {{-- Leave Approve --}}
                  <form class="d-inline" method="POST"
                        action="{{ route('approvals.approve', ['t'=>'leave','id'=>$approval->id]) }}">
                    @csrf
                    <button class="btn btn-sm btn-approve"><i class="bi bi-check2 me-1"></i> Approve</button>
                  </form>

                  {{-- Leave Reject (already correct) --}}
                  <button type="button"
                          class="btn btn-sm btn-reject"
                          data-action="{{ route('approvals.destroy', ['t'=>'leave','id'=>$approval->id]) }}">
                    <i class="bi bi-x-lg me-1"></i> Reject
                  </button>

                </td>
              </tr>
            @empty
              <tr><td colspan="8" class="text-center text-muted py-4">No pending leave requests.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>

{{-- 🟦 Pending Profile Update Requests --}}
<div class="card shadow-sm mt-4">
  <div class="card-header bg-white d-flex align-items-center">
    <h4 class="mb-0"><i class="bi bi-person-lines-fill me-2"></i> Profile Update Requests</h4>
  </div>

  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Employee</th>
            <th>Requested Changes</th>
            <th>Date Requested</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($pendingProfiles as $approval)
            @php
              $employee = $approval->approvable;
              $changes = $approval->data ?? [];
            @endphp
            <tr>
              <td>
                <strong>{{ $employee?->full_name ?? 'N/A' }}</strong><br>
                <small class="text-muted">#{{ $employee?->employee_code }}</small>
              </td>
              <td style="max-width: 400px;">
                {{-- changes output --}}
                @if(!empty($changes))
                  <ul class="mb-0 ps-3">
                    @foreach($changes as $key => $value)
                      <li><strong>{{ ucwords(str_replace('_',' ',$key)) }}:</strong> {{ $value }}</li>
                    @endforeach
                  </ul>
                @else
                  <span class="text-muted">No data provided</span>
                @endif
              </td>
              <td>{{ $approval->created_at->format('M d, Y h:i A') }}</td>
              <td class="text-end">

                <form method="POST" action="{{ route('approvals.approve', ['t'=>'employee','id'=>$approval->id]) }}" class="d-inline">
                  @csrf
                  <button class="btn btn-sm btn-approve">
                    <i class="bi bi-check2 me-1"></i> Approve
                  </button>
                </form>

                {{-- FIXED: EMPLOYEE PROFILE REJECT BUTTON --}}
                <button type="button"
                        class="btn btn-sm btn-reject"
                        data-action="{{ route('approvals.destroy', ['t'=>'employee','id'=>$approval->id]) }}">
                  <i class="bi bi-x-lg me-1"></i> Reject
                </button>

              </td>
            </tr>
          @empty
            <tr>
              <td colspan="4" class="text-center text-muted py-4">No pending profile update requests.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- 🟥 Reject Request Modal -->
<div class="modal fade" id="rejectReasonModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form id="rejectForm" method="POST">
      @csrf
      @method('DELETE')
      <div class="modal-content">
        <div class="modal-header bg-danger text-white">
          <h5 class="modal-title"><i class="bi bi-x-circle me-2"></i>Reject Request</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p>Please provide a reason for rejection:</p>
          <textarea name="reason" class="form-control" rows="3" required placeholder="Enter rejection reason..."></textarea>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger">Submit Rejection</button>
        </div>
      </div>
    </form>
  </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {

  // Open modal
  document.querySelectorAll('.btn-reject').forEach(btn => {
    btn.addEventListener('click', e => {
      e.preventDefault();
      const form = document.getElementById('rejectForm');
      form.action = btn.dataset.action;
      const modal = new bootstrap.Modal(document.getElementById('rejectReasonModal'));
      modal.show();
    });
  });

  // Handle AJAX submission
  document.getElementById('rejectForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const url = this.action;
    const formData = new FormData(this);

    const response = await fetch(url, {
      method: "POST",
      headers: {
        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
        "Accept": "application/json"
      },
      body: formData
    });

    const data = await response.json();

    if (data.redirect) {
      window.location.href = data.redirect; 
    } else {
      window.location.reload();
    }
  });

});
</script>
@endpush

@endsection
