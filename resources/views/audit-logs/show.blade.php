@extends('layouts.app')

@section('page_title', 'User Audit Logs')

@section('content')
<div class="container-fluid">
  <div class="card shadow-sm mb-4">
    <div class="card-header d-flex justify-content-between align-items-center bg-white">
      <h4 class="mb-0">
        <i class="bi bi-person-lines-fill me-2"></i>
        {{ $user->name }} — Activity Logs
      </h4>
      <a href="{{ route('audit-logs.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Back to Audit Logs
      </a>
    </div>

    <div class="card-body">
      <div class="mb-3">
        <strong>Email:</strong> {{ $user->email }}<br>
        <strong>Role:</strong> {{ $user->roles?->pluck('name')->join(', ') ?: '—' }}<br>
        <strong>Last Login:</strong>
        {{ $user->last_login ? \Carbon\Carbon::parse($user->last_login)->format('Y-m-d H:i:s') : 'Never' }}
      </div>

      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>Action</th>
              <th>Model</th>
              <th>Old Values</th>
              <th>New Values</th>
              <th>Timestamp</th>
            </tr>
          </thead>
          <tbody>
            @forelse($logs as $i => $log)
              <tr>
                <td>{{ $logs->firstItem() + $i }}</td>
                <td>
                  <span class="badge bg-info text-dark">{{ ucfirst($log->action) }}</span>
                </td>
                <td>{{ class_basename($log->auditable_type) }} #{{ $log->auditable_id }}</td>
                <td>
                  <pre class="small text-muted mb-0">{{ json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                </td>
                <td>
                  <pre class="small text-success mb-0">{{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                </td>
                <td>
                  <i class="bi bi-clock me-1 text-secondary"></i>
                  {{ $log->created_at->format('Y-m-d H:i:s') }}
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="text-center text-muted py-4">
                  <i class="bi bi-info-circle me-1"></i> No audit activity recorded.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="d-flex justify-content-between align-items-center mt-3">
        <small class="text-muted">
          Showing {{ $logs->firstItem() ?? 0 }}–{{ $logs->lastItem() ?? 0 }} of {{ $logs->total() ?? 0 }}
        </small>
        {{ $logs->links('pagination::bootstrap-5') }}
      </div>
    </div>
  </div>
</div>
@endsection
