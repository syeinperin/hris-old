@extends('layouts.app')

@section('page_title', 'My Leave Requests')

@section('content')
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">My Leave Requests</h1>
    <a href="{{ route('leaves.create') }}" class="btn btn-primary">
      <i class="bi bi-plus-circle me-1"></i> New Request
    </a>
  </div>

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
  @endif

  <div class="card shadow-sm">
    <div class="card-body table-responsive">
      <table class="table align-middle">
        <thead class="table-light">
          <tr>
            <th>Leave Type</th>
            <th>From</th>
            <th>To</th>
            <th>Status</th>
            <th>Attachment</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($requests as $leave)
            <tr>
              <td>{{ ucfirst($leave->leave_type) }}</td>
              <td>{{ \Carbon\Carbon::parse($leave->start_date)->format('M d, Y') }}</td>
              <td>{{ \Carbon\Carbon::parse($leave->end_date)->format('M d, Y') }}</td>
              <td>
                <span class="badge bg-{{ $leave->status === 'approved' ? 'success' : ($leave->status === 'rejected' ? 'danger' : 'warning') }}">
                  {{ ucfirst($leave->status) }}
                </span>
              </td>
              <td>
                @if($leave->attachment_path)
                  <a href="{{ asset('storage/'.$leave->attachment_path) }}" target="_blank">View</a>
                @else
                  —
                @endif
              </td>
              <td>
                <form action="{{ route('leaves.destroy', $leave->id) }}" method="POST" onsubmit="return confirm('Delete this request?');">
                  @csrf @method('DELETE')
                  <button class="btn btn-sm btn-danger">
                    <i class="bi bi-trash"></i>
                  </button>
                </form>
              </td>
            </tr>
          @empty
            <tr><td colspan="6" class="text-center text-muted">No leave requests found.</td></tr>
          @endforelse
        </tbody>
      </table>
      {{ $requests->links() }}
    </div>
  </div>
</div>
@endsection
