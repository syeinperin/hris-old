@extends('layouts.app')

@section('page_title', 'Overtime Requests')

@section('content')
<div class="container-fluid py-3">

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Overtime Requests</h3>
    @role('employee')
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#overtimeCreateModal">
        + New Request
      </button>
    @endrole
  </div>

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif
  @if(session('warning'))
    <div class="alert alert-warning">{{ session('warning') }}</div>
  @endif

  <div class="card shadow-sm">
    <div class="card-body table-responsive">
      <table class="table table-bordered align-middle text-center">
        <thead class="table-light">
          <tr>
            <th>Date</th>
            <th>Employee</th>
            <th>Requested (hrs)</th>
            <th>Approved (hrs)</th>
            <th>Status</th>
            <th>Reason</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($requests as $req)
            <tr>
              <td>{{ $req->ot_date->format('Y-m-d') }}</td>
              <td>{{ $req->employee->full_name ?? '—' }}</td>
              <td>{{ $req->requested_hours }}</td>
              <td>{{ $req->approved_hours ?? '—' }}</td>
              <td>
                @if($req->status === 'approved')
                  <span class="badge bg-success">Approved</span>
                @elseif($req->status === 'rejected')
                  <span class="badge bg-danger">Rejected</span>
                @else
                  <span class="badge bg-warning text-dark">Pending</span>
                @endif
              </td>
              <td>{{ $req->reason ?? '—' }}</td>
              <td class="d-flex justify-content-center gap-1">
                @role('supervisor|hr')
                  @if($req->status === 'pending')
                    <form action="{{ route('overtime.approve', $req->id) }}" method="POST" onsubmit="return confirm('Approve this request?')">
                      @csrf
                      <button class="btn btn-sm btn-success"><i class="bi bi-check-lg"></i></button>
                    </form>
                    <form action="{{ route('overtime.reject', $req->id) }}" method="POST" onsubmit="return confirm('Reject this request?')">
                      @csrf
                      <button class="btn btn-sm btn-danger"><i class="bi bi-x-lg"></i></button>
                    </form>
                  @endif
                @endrole
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-muted py-4">No overtime requests found.</td>
            </tr>
          @endforelse
        </tbody>
      </table>

      {{ $requests->links() }}
    </div>
  </div>

</div>

{{-- Modal for employee overtime request --}}
@role('employee')
<div class="modal fade" id="overtimeCreateModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="{{ route('overtime.store') }}">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title">Request Overtime</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Date</label>
            <input type="date" name="ot_date" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Requested Hours</label>
            <input type="number" name="requested_hours" step="0.5" min="0.5" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Reason</label>
            <textarea name="reason" class="form-control" rows="2" required></textarea>
          </div>
          <input type="hidden" name="employee_id" value="{{ Auth::user()->employee->id ?? '' }}">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Submit</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endrole
@endsection
