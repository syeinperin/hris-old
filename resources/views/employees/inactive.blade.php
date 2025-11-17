{{-- resources/views/employees/inactive.blade.php --}}
@extends('layouts.app')

@section('page_title', 'Inactive Employees')

@push('styles')
<style>
  .table-responsive {
    overflow-x: auto;
  }

  td.text-nowrap form {
    display: inline-block;
    margin-right: 4px;
  }

  td.text-nowrap button {
    padding: 2px 8px;
    font-size: 0.85rem;
  }

  .table th,
  .table td {
    vertical-align: middle;
  }

  .alert {
    margin-bottom: 1rem;
  }
</style>
@endpush

@section('content')
<div class="container-fluid">

  {{-- ✅ Alerts --}}
  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  {{-- Header --}}
  <div class="row mb-4">
    <div class="col">
      <h3><i class="bi bi-person-x me-1"></i> Inactive Employees</h3>
    </div>
    <div class="col text-end">
      <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary btn-sm">
        ← Active Employees
      </a>
    </div>
  </div>

  {{-- Filters --}}
  <form method="GET" action="{{ route('employees.inactive') }}" class="row g-2 mb-4">
    <div class="col-md-4">
      <select name="department_id" class="form-select">
        <option value="">All Departments</option>
        @foreach($departments as $id => $name)
          <option value="{{ $id }}" @selected(request('department_id') == $id)>{{ $name }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-4">
      <select name="employment_type" class="form-select">
        @foreach($employmentTypes as $key => $label)
          <option value="{{ $key }}" @selected(request('employment_type') === $key)>{{ $label }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-4 d-flex">
      <button class="btn btn-primary me-2" type="submit">Filter</button>
      <a href="{{ route('employees.inactive') }}" class="btn btn-outline-secondary">Reset</a>
    </div>
  </form>

  {{-- Table --}}
  <div class="table-responsive">
    <table class="table table-striped align-middle">
      <thead class="table-light">
        <tr>
          <th>ID</th>
          <th>Code</th>
          <th>Status</th>
          <th>Name</th>
          <th>Email</th>
          <th>Department</th>
          <th>Type</th>
          <th>Start Date</th>
          <th>End Date</th>
          <th>Schedule</th>
          <th class="text-center">Actions</th>
        </tr>
      </thead>

      <tbody>
        @forelse($employees as $emp)
          <tr>
            <td>{{ $emp->id }}</td>
            <td>{{ $emp->employee_code }}</td>
            <td><span class="badge bg-secondary">{{ ucfirst($emp->status) }}</span></td>
            <td>{{ $emp->name }}</td>
            <td>{{ $emp->email }}</td>
            <td>{{ optional($emp->department)->name ?? '—' }}</td>
            <td>{{ ucfirst($emp->employment_type) }}</td>
            <td>{{ optional($emp->employment_start_date)->toDateString() ?? '—' }}</td>
            <td>{{ optional($emp->employment_end_date)->toDateString() ?? '—' }}</td>
            <td>{{ optional($emp->schedule)->name ?? '—' }}</td>

            {{-- ✅ Only Activate + Delete --}}
            <td class="text-nowrap text-center">

              {{-- 🟢 Activate --}}
              <form method="POST" action="{{ route('employees.restore', $emp->id) }}" class="d-inline">
                @csrf @method('PATCH')
                <button class="btn btn-sm btn-success"
                        onclick="return confirm('Activate {{ $emp->employee_code }} ({{ $emp->name }})?')">
                  <i class="bi bi-person-check"></i> Activate
                </button>
              </form>

            
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="11" class="text-center text-muted py-3">
              No inactive employees found.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{-- Pagination --}}
  <div class="mt-3 d-flex justify-content-between align-items-center">
    <small class="text-muted">
      Showing {{ $employees->firstItem() ?? 0 }}–{{ $employees->lastItem() ?? 0 }} of {{ $employees->total() ?? 0 }}
    </small>
    {{ $employees->links('pagination::bootstrap-5') }}
  </div>

</div>
@endsection
