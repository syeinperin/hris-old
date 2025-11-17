@extends('layouts.app')

@section('page_title', 'User Management')

@section('content')
<div class="container-fluid">
  <div class="card shadow-sm mb-4">
    <div class="card-header d-flex justify-content-between align-items-center bg-white">
      <h4 class="mb-0 d-flex align-items-center gap-2">
        <i class="bi bi-people me-2"></i> User Management
      </h4>

      {{-- ✅ Audit Logs button --}}
      <div class="d-flex align-items-center gap-2">
        <a href="{{ route('audit-logs.index') }}" class="btn btn-outline-dark btn-sm">
          <i class="bi bi-clipboard-check me-1"></i> View Audit Logs
        </a>
      </div>
    </div>

    <div class="card-body">
      {{-- Filters/Search Bar --}}
      <x-search-bar
        :action="route('users.index')"
        placeholder="Search users…"
        :filters="[
          'role'   => [''=>'All Roles','admin'=>'Admin','hr'=>'HR','employee'=>'Employee','supervisor'=>'Supervisor','timekeeper'=>'Timekeeper'],
          'status' => [''=>'All','active'=>'Active','inactive'=>'Inactive'],
        ]"
        :sortFields="[
          'ID'         => 'id',
          'Name'       => 'name',
          'Email'      => 'email',
          'Created At' => 'created_at',
          'Last Login' => 'last_login',
        ]"
        showDateRange
        class="mb-4"
      />

      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>ID</th>
              <th>Name</th>
              <th>Email</th>
              <th>Role</th>
              <th>Status</th>
              <th>Last Login</th>
              <th class="text-center">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($users as $u)
              <tr>
                <td>{{ $u->id }}</td>
                <td>{{ $u->name }}</td>
                <td>{{ $u->email }}</td>
                <td>
                  <select
                    class="form-select form-select-sm role-select"
                    data-update-url="{{ route('users.updateRole', $u) }}"
                    data-current-role="{{ $u->role?->name }}"
                  >
                    @foreach(['admin','hr','employee','supervisor','timekeeper'] as $r)
                      <option value="{{ $r }}" {{ ($u->role?->name === $r) ? 'selected' : '' }}>
                        {{ ucfirst($r) }}
                      </option>
                    @endforeach
                  </select>
                </td>
                <td>
                  @if($u->status === 'active' && $u->last_login)
                    <span class="badge bg-success">Active</span>
                  @else
                    <span class="badge bg-secondary">Inactive</span>
                  @endif
                </td>
                <td>
                  @if($u->last_login)
                    {{ \Carbon\Carbon::parse($u->last_login)->format('Y-m-d H:i') }}
                  @else
                    Never
                  @endif
                </td>
                <td class="text-center">
                  <div class="d-flex gap-1 justify-content-center">
                    <a href="{{ route('users.editPassword', $u) }}" class="btn btn-outline-primary btn-sm">
                      Change Password
                    </a>
                    <form action="{{ route('users.destroy', $u) }}" method="POST" onsubmit="return confirm('Delete this user?')">
                      @csrf
                      @method('DELETE')
                      <button class="btn btn-outline-danger btn-sm">Delete</button>
                    </form>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center text-muted py-4">No users found.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      {{-- Pagination --}}
      <div class="d-flex justify-content-between align-items-center mt-4">
        <small class="text-muted">
          Showing {{ $users->firstItem() }}–{{ $users->lastItem() }} of {{ $users->total() }}
        </small>
        {{ $users->withQueryString()->links('pagination::bootstrap-5') }}
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.role-select').forEach(select => {
  let original = select.dataset.currentRole;
  select.addEventListener('change', async function() {
    const res = await fetch(this.dataset.updateUrl, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}'
      },
      body: JSON.stringify({ role: this.value })
    });
    if (res.ok) {
      const json = await res.json();
      alert(json.message);
      original = this.value;
      select.dataset.currentRole = this.value;
    } else {
      alert('Failed to update role');
      this.value = original;
    }
  });
});
</script>
@endpush
