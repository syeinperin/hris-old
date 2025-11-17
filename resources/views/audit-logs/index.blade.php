@extends('layouts.app')

@section('page_title', 'Audit Logs')

@section('content')
<div class="container-fluid">
  <div class="card shadow-sm mb-4">
    <div class="card-header d-flex justify-content-between align-items-center bg-white">
      <h4 class="mb-0">
        <i class="bi bi-clock-history me-2"></i> Audit Logs
      </h4>
      <a href="{{ route('users.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Back to Users
      </a>
    </div>

    <div class="card-body">
      {{-- Search bar --}}
      <form method="GET" action="{{ route('audit-logs.index') }}" class="row g-2 mb-3">
        <div class="col-md-4">
          <input type="text" name="search" class="form-control" placeholder="Search user or email..."
                 value="{{ request('search') }}">
        </div>
        <div class="col-md-2">
          <button class="btn btn-primary w-100">
            <i class="bi bi-search"></i> Search
          </button>
        </div>
        <div class="col-md-2">
          <a href="{{ route('audit-logs.index') }}" class="btn btn-outline-secondary w-100">
            <i class="bi bi-x-lg"></i> Reset
          </a>
        </div>
      </form>

      {{-- Table --}}
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>User</th>
              <th>Email</th>
              <th>Role</th>
              <th>Last Login</th>
              <th class="text-center">Action</th>
            </tr>
          </thead>
          <tbody>
            @forelse($logs as $i => $u)
              <tr>
                <td>{{ $logs->firstItem() + $i }}</td>
                <td>{{ $u->name }}</td>
                <td>{{ $u->email }}</td>
                <td>
                  {{ $u->roles?->pluck('name')->join(', ') ?: '—' }}
                </td>
                <td>
                  <i class="bi bi-clock me-1 text-secondary"></i>
                  {{ \Carbon\Carbon::parse($u->last_login)->format('Y-m-d H:i:s') }}
                </td>
                <td class="text-center">
                  <a href="{{ route('audit-logs.show', $u->id) }}" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-eye"></i> View Logs
                  </a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="text-center text-muted py-4">
                  <i class="bi bi-info-circle me-1"></i> No login records found.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      {{-- Pagination --}}
      <div class="d-flex justify-content-between align-items-center mt-3">
        <small class="text-muted">
          Showing {{ $logs->firstItem() ?? 0 }}–{{ $logs->lastItem() ?? 0 }} of {{ $logs->total() ?? 0 }}
        </small>
        {{ $logs->withQueryString()->links('pagination::bootstrap-5') }}
      </div>
    </div>
  </div>
</div>
@endsection
