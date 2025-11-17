@extends('layouts.app')

@section('page_title', 'Leave Requests')

@push('styles')
<style>
  :root {
    --asiatex-primary: #3a3a84;
    --asiatex-light: #ececff;
  }

  a.text-theme {
    color: var(--asiatex-primary) !important;
    font-weight: 600;
  }
  a.text-theme:hover { text-decoration: underline; }

  .badge-theme-pending {
    background-color: var(--asiatex-light);
    color: var(--asiatex-primary);
    border: 1px solid var(--asiatex-primary);
    font-weight: 600;
  }
  .badge-theme-approved {
    background-color: var(--asiatex-primary);
    color: #fff;
  }
  .badge-theme-rejected {
    background-color: #b02a37;
    color: #fff;
  }

  .table td, .table th { vertical-align: middle; }

  .small-reason {
    font-size: 0.83rem;
    color: #6c757d;
  }

  .btn-icon {
    border: 1px solid #dee2e6;
    background-color: #f8f9fa;
    color: #3a3a84;
  }
  .btn-icon:hover {
    background-color: var(--asiatex-primary);
    color: #fff;
  }

  .modal-body p {
    margin-bottom: .5rem;
  }
</style>
@endpush

@section('content')
<div class="container-fluid">
  {{-- Header --}}
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Leave Requests</h1>

    @role('employee')
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#leaveCreateModal">
        + New Request
      </button>
    @endrole
  </div>

  {{-- Flash Messages --}}
  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
  @endif

  {{-- Table --}}
  <div class="card shadow-sm">
    <div class="card-body table-responsive">
      <table class="table table-striped align-middle">
        <thead class="table-light">
          <tr>
            <th>Type</th>
            <th>From</th>
            <th>To</th>
            <th>Reason</th>
            <th>Document</th>
            <th>Status</th>
            <th>Supervisor</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($requests as $req)
            @php
              $rejectReason = $req->rejection_reason
                  ?? $req->approval?->data['rejection_reason']
                  ?? null;
            @endphp

            <tr>
              <td>{{ $req->type->name ?? '—' }}</td>
              <td>{{ $req->start_date ? \Carbon\Carbon::parse($req->start_date)->format('Y-m-d') : '—' }}</td>
              <td>{{ $req->end_date ? \Carbon\Carbon::parse($req->end_date)->format('Y-m-d') : '—' }}</td>
              <td>{{ \Illuminate\Support\Str::limit($req->reason, 40, '…') }}</td>

              <td>
                @if($req->attachment_path)
                  <a href="{{ asset('storage/'.$req->attachment_path) }}" target="_blank" class="text-theme">View File</a>
                @else
                  <span class="text-muted">—</span>
                @endif
              </td>

              {{-- STATUS --}}
              <td>
                @if($req->status === 'pending')
                  <span class="badge badge-theme-pending">Pending</span>
                @elseif($req->status === 'approved')
                  <span class="badge badge-theme-approved">Approved</span>
                @elseif($req->status === 'rejected')
                  <span class="badge badge-theme-rejected">Rejected</span>
                  @if($rejectReason)
                    <div class="small-reason mt-1">
                      Reason: {{ $rejectReason }}
                    </div>
                  @endif
                @endif
              </td>

              <td>{{ optional($req->supervisor)->name ?? '—' }}</td>

              {{-- ACTIONS --}}
              <td class="d-flex gap-1">
                @role('supervisor')
                  @if($req->status === 'pending')
                    {{-- Approve --}}
                    <form action="{{ route('leaves.approve', $req->id) }}" method="POST"
                          onsubmit="return confirm('Approve this leave?')">
                      @csrf
                      <button class="btn btn-sm btn-primary">Approve</button>
                    </form>

                    {{-- Reject --}}
                    <button type="button"
                            class="btn btn-sm btn-outline-danger btn-reject-leave"
                            data-action="{{ route('leaves.reject', $req->id) }}">
                      Reject
                    </button>
                  @endif
                @elserole('employee')
                  @if($req->status === 'pending')
                    {{-- Delete Pending --}}
                    <form action="{{ route('leaves.destroy', $req->id) }}" method="POST"
                          onsubmit="return confirm('Delete this request?')">
                      @csrf @method('DELETE')
                      <button class="btn btn-sm btn-outline-danger">Delete</button>
                    </form>
                  @else
                    {{-- 👁 Eye Icon for Details --}}
                    <button type="button"
                            class="btn btn-sm btn-icon btn-view-details"
                            title="View Details"
                            data-type="{{ $req->type->name ?? '—' }}"
                            data-dates="{{ $req->start_date }} to {{ $req->end_date }}"
                            data-status="{{ ucfirst($req->status) }}"
                            data-reason="{{ $req->reason }}"
                            data-rejection="{{ $rejectReason ?? '—' }}"
                            data-supervisor="{{ optional($req->supervisor)->name ?? '—' }}">
                      <i class="bi bi-eye"></i>
                    </button>
                  @endif
                @endrole
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="text-center text-muted py-4">No leave requests found.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
      {{ $requests->links() }}
    </div>
  </div>
</div>

{{-- ✅ Create Leave Modal --}}
@include('leaves.create')

{{-- 🟥 Reject Modal (Supervisor) --}}
<div class="modal fade" id="rejectLeaveModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form id="rejectLeaveForm" method="POST">
      @csrf
      <div class="modal-content">
        <div class="modal-header bg-danger text-white">
          <h5 class="modal-title"><i class="bi bi-x-circle me-2"></i>Reject Leave Request</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p>Please provide a reason for rejecting this leave request:</p>
          <textarea name="reason" class="form-control" rows="3" placeholder="Enter reason..." required></textarea>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger">Submit Rejection</button>
        </div>
      </div>
    </form>
  </div>
</div>

{{-- 🟦 Show Details Modal (Employee) --}}
<div class="modal fade" id="leaveDetailsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="bi bi-info-circle me-2"></i>Leave Details</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p><strong>Type:</strong> <span id="detail-type"></span></p>
        <p><strong>Dates:</strong> <span id="detail-dates"></span></p>
        <p><strong>Status:</strong> <span id="detail-status"></span></p>
        <p><strong>Reason:</strong> <span id="detail-reason"></span></p>
        <p><strong>Rejection Reason:</strong> <span id="detail-rejection"></span></p>
        <p><strong>Supervisor:</strong> <span id="detail-supervisor"></span></p>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Reject Modal
  document.querySelectorAll('.btn-reject-leave').forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      const form = document.getElementById('rejectLeaveForm');
      form.action = this.dataset.action;
      new bootstrap.Modal(document.getElementById('rejectLeaveModal')).show();
    });
  });

  // Show Details Modal
  document.querySelectorAll('.btn-view-details').forEach(btn => {
    btn.addEventListener('click', function() {
      document.getElementById('detail-type').textContent = this.dataset.type;
      document.getElementById('detail-dates').textContent = this.dataset.dates;
      document.getElementById('detail-status').textContent = this.dataset.status;
      document.getElementById('detail-reason').textContent = this.dataset.reason;
      document.getElementById('detail-rejection').textContent = this.dataset.rejection;
      document.getElementById('detail-supervisor').textContent = this.dataset.supervisor;
      new bootstrap.Modal(document.getElementById('leaveDetailsModal')).show();
    });
  });
});
</script>
@endpush
@endsection
