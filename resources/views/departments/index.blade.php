@extends('layouts.app')

@section('page_title', 'Department Assignment')

@section('content')
<div class="container">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h2 class="mb-0">Department Assignment</h2>

    {{-- ➕ Assign Supervisors Button (Moved to Top) --}}
    <button class="btn btn-primary shadow-sm"
            data-bs-toggle="modal"
            data-bs-target="#assignDepartmentModal">
      <i class="bi bi-people-fill me-1"></i> Assign Supervisors
    </button>
  </div>

  {{-- 🔍 Search Bar --}}
  <x-search-bar
    :action="route('departments.search')"
    placeholder="Search department name…"
    :filters="[]"
    class="mb-3"
  />

  {{-- Department List --}}
  <div id="departmentContainer">
    @include('departments.partials.list')
  </div>
</div>

{{-- 🧩 Assign Department Modal --}}
<div class="modal fade" id="assignDepartmentModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content shadow-lg border-0 rounded-3">
      <form action="{{ route('departments.store') }}" method="POST">
        @csrf

        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title">
            <i class="bi bi-people-fill me-1"></i>
            Assign Supervisors to Department
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          {{-- Department Selector --}}
          <div class="mb-3">
            <label class="form-label fw-semibold">Select Department *</label>
            <select name="department_id" class="form-select" required>
              <option value="" disabled selected>Choose a department…</option>
              @foreach(\App\Models\Department::orderBy('name')->get() as $dep)
                <option value="{{ $dep->id }}">{{ $dep->name }}</option>
              @endforeach
            </select>
          </div>

          {{-- Supervisors --}}
          <div class="mb-3">
            <label class="form-label fw-semibold">Assign Supervisors</label>
            <div class="border rounded p-2" style="max-height:180px; overflow-y:auto;">
              <div class="row row-cols-1 g-2">
                @foreach($supervisors as $sup)
                  <div class="col">
                    <div class="form-check form-switch d-flex align-items-center justify-content-between px-2">
                      <label class="form-check-label me-2" for="sup-{{ $sup->id }}">
                        <span class="badge bg-light text-dark border">
                          <i class="bi bi-person-badge me-1 text-primary"></i> {{ $sup->name }}
                        </span>
                      </label>
                      <input type="checkbox"
                             class="form-check-input"
                             id="sup-{{ $sup->id }}"
                             name="supervisor_ids[]"
                             value="{{ $sup->id }}">
                    </div>
                  </div>
                @endforeach
              </div>
            </div>
            <small class="text-muted d-block mt-1">
              Toggle switches to assign one or more supervisors to the selected department.
            </small>
          </div>
        </div>

        <div class="modal-footer bg-light">
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-save me-1"></i> Assign
          </button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="bi bi-x-circle me-1"></i> Cancel
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
