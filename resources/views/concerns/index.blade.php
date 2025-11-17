@extends('layouts.app')

@section('page_title','Formal Complaints')

@section('content')
<div class="container-fluid">

  <div class="d-flex justify-content-between mb-3">
    <h3 class="mb-0">Formal Complaints</h3>

    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addConcernModal">
      <i class="bi bi-plus-circle me-1"></i> New Concern
    </button>
  </div>

  <div class="card shadow-sm">
    <div class="card-body p-0">
      <table class="table table-striped mb-0">
        <thead class="table-light">
          <tr>
            <th>Date</th>
            <th>Employee</th>
            <th>Category</th>
            <th>Subject</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>

        <tbody>
          @forelse ($concerns as $c)
          <tr>
            <td>{{ $c->created_at->format('Y-m-d') }}</td>
            <td>{{ $c->employee->full_name ?? 'Unknown Employee' }}</td>
            <td>{{ $c->category->name }}</td>
            <td>{{ $c->subject }}</td>

            <td>
              <span class="badge bg-{{ 
                    $c->status=='open' ? 'danger' : 
                    ($c->status=='in_progress' ? 'warning' : 
                    ($c->status=='resolved' ? 'info' : 'success')) }}">
                {{ ucfirst($c->status) }}
              </span>
            </td>

            <td>
              <a href="{{ route('concerns.show',$c->id) }}" class="btn btn-sm btn-outline-primary">View</a>
              <form action="{{ route('concerns.destroy',$c->id) }}" method="POST" class="d-inline">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this concern?')">
                  Delete
                </button>
              </form>
            </td>
          </tr>

          @empty
          <tr>
            <td colspan="6" class="text-center py-4 text-muted">No concerns submitted yet.</td>
          </tr>
          @endforelse
        </tbody>
      </table>

      <div class="p-2">
        {{ $concerns->links() }}
      </div>

    </div>
  </div>

</div>

{{-- Modal --}}
@include('concerns.modals.create')

@endsection
