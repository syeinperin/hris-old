@extends('layouts.app')

@section('page_title', 'Employee Documents')

@push('styles')
<style>
  .accordion-button:not(.collapsed) {
    background-color: #f8f9fa;
    font-weight: 600;
  }
  .table-sm td, .table-sm th { padding: 0.35rem 0.5rem; }
</style>
@endpush

@section('content')
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="mb-0"><i class="bi bi-folder2-open me-2"></i> Employee Documents</h3>
    <form method="GET" class="d-flex align-items-center gap-2">
      <select name="department_id" class="form-select" style="width:auto">
        <option value="">All Departments</option>
        @foreach($departments as $dept)
          <option value="{{ $dept->id }}" {{ $deptId == $dept->id ? 'selected' : '' }}>
            {{ $dept->name }}
          </option>
        @endforeach
      </select>
      <input type="text" name="q" value="{{ $search }}" class="form-control" placeholder="Search employee..." style="width:220px">
      <button class="btn btn-primary">Filter</button>
    </form>
  </div>

  <div class="accordion" id="docAccordion">
    @forelse($documents->groupBy('employee_id') as $empId => $docs)
      @php $emp = $docs->first()->employee; @endphp
      <div class="accordion-item mb-2 shadow-sm">
        <h2 class="accordion-header" id="heading{{ $empId }}">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                  data-bs-target="#collapse{{ $empId }}" aria-expanded="false">
            <div class="d-flex flex-column">
              <span class="fw-semibold">{{ $emp->name }}</span>
              <small class="text-muted">
                {{ $emp->department->name ?? 'No Department' }} · {{ $emp->employee_code }}
              </small>
            </div>
          </button>
        </h2>
        <div id="collapse{{ $empId }}" class="accordion-collapse collapse"
             data-bs-parent="#docAccordion">
          <div class="accordion-body bg-light">
            <div class="table-responsive">
              <table class="table table-sm table-bordered align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Title</th>
                    <th>Type</th>
                    <th>Version</th>
                    <th>Status</th>
                    <th>Uploaded</th>
                    <th>Expires</th>
                    <th class="text-center">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($docs as $doc)
                    <tr>
                      <td>{{ $doc->title }}</td>
                      <td>{{ ucfirst($doc->doc_type) }}</td>
                      <td>v{{ $doc->version }}</td>
                      <td>
                        <span class="badge text-bg-{{ $doc->status === 'approved' ? 'success' : ($doc->status === 'rejected' ? 'danger' : 'secondary') }}">
                          {{ ucfirst($doc->status ?? 'Submitted') }}
                        </span>
                      </td>
                      <td>{{ $doc->created_at->format('M d, Y') }}</td>
                      <td>{{ $doc->expires_at ? $doc->expires_at->format('M d, Y') : '—' }}</td>
                      <td class="text-center">
                        <a href="{{ route('documents.view', $doc) }}" target="_blank" class="btn btn-sm btn-outline-info" title="View">
                          <i class="bi bi-eye"></i>
                        </a>
                        <a href="{{ route('documents.download', $doc) }}" class="btn btn-sm btn-outline-secondary" title="Download">
                          <i class="bi bi-download"></i>
                        </a>
                        <form action="{{ route('documents.destroy', $doc) }}" method="POST" class="d-inline"
                              onsubmit="return confirm('Delete this document?')">
                          @csrf @method('DELETE')
                          <button class="btn btn-sm btn-outline-danger" title="Delete">
                            <i class="bi bi-trash"></i>
                          </button>
                        </form>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    @empty
      <div class="alert alert-light text-center">No documents found.</div>
    @endforelse
  </div>

  <div class="mt-3">
    {{ $documents->links('pagination::bootstrap-5') }}
  </div>
</div>
@endsection
