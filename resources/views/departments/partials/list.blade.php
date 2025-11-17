<div class="card shadow-sm border-0">
  <div class="card-body p-0">
    <table class="table table-striped align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>Department</th>
          <th>Assigned Supervisors</th>
          <th class="text-center" style="width:160px;">Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($departments as $department)
          <tr>
            <td class="fw-semibold">{{ $department->name }}</td>
            <td>
              @forelse($department->supervisors as $sup)
                <span class="badge bg-light text-dark border">
                  <i class="bi bi-person-badge me-1 text-primary"></i>
                  {{ $sup->name }}
                </span>
              @empty
                <span class="text-muted fst-italic">None</span>
              @endforelse
            </td>
            <td class="text-center">
              <button class="btn btn-warning btn-sm"
                      data-bs-toggle="modal"
                      data-bs-target="#editDepartmentModal{{ $department->id }}">
                <i class="bi bi-pencil-square"></i>
              </button>
            </td>
          </tr>

          {{-- Edit Modal --}}
          <div class="modal fade"
               id="editDepartmentModal{{ $department->id }}"
               tabindex="-1"
               aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
              <div class="modal-content shadow-lg border-0 rounded-3">
                <form action="{{ route('departments.update', $department) }}" method="POST">
                  @csrf
                  @method('PUT')

                  <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                      <i class="bi bi-pencil-square me-1"></i>
                      Edit Supervisor Assignment — {{ $department->name }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                  </div>

                  <div class="modal-body">
                    <div class="mb-3">
                      <label class="form-label fw-semibold">Assign Supervisors</label>
                      <div class="border rounded p-2" style="max-height:180px; overflow-y:auto;">
                        <div class="row row-cols-1 g-2">
                          @foreach($supervisors as $sup)
                            <div class="col">
                              <div class="form-check form-switch d-flex align-items-center justify-content-between px-2">
                                <label class="form-check-label me-2" for="edit-sup-{{ $department->id }}-{{ $sup->id }}">
                                  <span class="badge bg-light text-dark border">
                                    <i class="bi bi-person-badge me-1 text-primary"></i> {{ $sup->name }}
                                  </span>
                                </label>
                                <input type="checkbox"
                                       class="form-check-input"
                                       id="edit-sup-{{ $department->id }}-{{ $sup->id }}"
                                       name="supervisor_ids[]"
                                       value="{{ $sup->id }}"
                                       {{ $department->supervisors->pluck('id')->contains($sup->id) ? 'checked' : '' }}>
                              </div>
                            </div>
                          @endforeach
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="modal-footer bg-light">
                    <button type="submit" class="btn btn-success">
                      <i class="bi bi-check-circle me-1"></i> Update
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                      <i class="bi bi-x-circle me-1"></i> Cancel
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        @empty
          <tr>
            <td colspan="3" class="text-center text-muted py-4">No departments found.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<div class="mt-3">
  {{ $departments->links('pagination::bootstrap-5') }}
</div>
