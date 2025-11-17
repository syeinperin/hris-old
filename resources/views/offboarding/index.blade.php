{{-- resources/views/offboarding/index.blade.php --}}
@extends('layouts.app')

@section('page_title','Offboarding List')

@section('content')
<div class="container-fluid">

  {{-- Header --}}
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4><i class="bi bi-person-dash me-2"></i>Offboarding Records</h4>

    {{-- Only supervisors can create offboarding --}}
    @if(auth()->user()->hasRole('supervisor'))
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#offboardModal">
        <i class="bi bi-plus-lg me-1"></i> Offboard Employee
      </button>
    @endif
  </div>

  {{-- Alerts --}}
  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @elseif(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <i class="bi bi-x-circle-fill me-1"></i> {{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @elseif(session('warning'))
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
      <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ session('warning') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  {{-- Table --}}
  <div class="card shadow-sm">
    <div class="card-body table-responsive">
      <table class="table table-bordered align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>#</th>
            <th>Employee</th>
            <th>Type</th>
            <th>Reason</th>
            <th>Effective Date</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($offboardings as $ofb)
            <tr>
              <td>{{ $ofb->id }}</td>
              <td>{{ $ofb->employee->employee_code }} — {{ $ofb->employee->name }}</td>
              <td>{{ ucfirst($ofb->type) }}</td>
              <td>{{ $ofb->reason }}</td>
              <td>{{ \Carbon\Carbon::parse($ofb->effective_date)->format('M d, Y') }}</td>
              <td>
                @if($ofb->status === 'pending')
                  <span class="badge bg-warning text-dark">Pending HR Approval</span>
                @elseif($ofb->status === 'approved')
                  <span class="badge bg-success">Approved</span>
                @elseif($ofb->status === 'rejected')
                  <span class="badge bg-danger">Rejected</span>
                @else
                  <span class="badge bg-secondary">{{ ucfirst($ofb->status) }}</span>
                @endif
              </td>
              <td>
                {{-- HR Action Buttons --}}
                @if(auth()->user()->hasRole('hr') && $ofb->status === 'pending')
                  <form action="{{ route('offboarding.approve', $ofb->id) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-success">
                      <i class="bi bi-check2-circle me-1"></i> Approve
                    </button>
                  </form>

                  <form action="{{ route('offboarding.reject', $ofb->id) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-danger">
                      <i class="bi bi-x-circle me-1"></i> Reject
                    </button>
                  </form>
                @endif

                {{-- Supervisor delete option --}}
                @if(auth()->user()->hasRole('supervisor') && $ofb->status !== 'approved')
                  <form action="{{ route('offboarding.destroy', $ofb) }}" method="POST" class="d-inline"
                        onsubmit="return confirm('Delete this record?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger">
                      <i class="bi bi-trash"></i>
                    </button>
                  </form>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center text-muted py-4">
                No offboarding records found.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>

      <div class="mt-3">
        {{ $offboardings->links('pagination::bootstrap-5') }}
      </div>
    </div>
  </div>
</div>

{{-- ─────────────── MODAL (Supervisor Only) ─────────────── --}}
@if(auth()->user()->hasRole('supervisor'))
<div class="modal fade" id="offboardModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form action="{{ route('offboarding.store') }}" method="POST" class="modal-content">
      @csrf
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-person-dash me-1"></i> Offboard Employee</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Select Employee</label>
            <select name="employee_id" class="form-select" required>
              <option value="">Select Employee</option>
              @foreach($employees as $emp)
                <option value="{{ $emp->id }}">{{ $emp->employee_code }} — {{ $emp->name }}</option>
              @endforeach
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label">Offboarding Type</label>
            <select name="type" class="form-select" required>
              <option value="">Select Type</option>
              <option value="resignation">Resignation</option>
              <option value="termination">Termination</option>
              <option value="endo">End of Contract</option>
              <option value="retirement">Retirement</option>
              <option value="other">Other</option>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label">Effective Date</label>
            <input type="date" name="effective_date" class="form-control" required>
          </div>

          <div class="col-md-6">
            <label class="form-label">Reason</label>
            <input type="text" name="reason" class="form-control" placeholder="Reason for offboarding" required>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Submit Request</button>
      </div>
    </form>
  </div>
</div>
@endif

@endsection
