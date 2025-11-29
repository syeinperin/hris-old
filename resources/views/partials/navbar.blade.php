@php
    use Illuminate\Support\Facades\Auth;

    $user = Auth::user();
    $employee = $user->employee ?? null;
    $unread = $user->unreadNotifications ?? collect();

    $avatarUrl =
        $employee && $employee->profile_picture
            ? asset('storage/' . $employee->profile_picture)
            : asset('images/default-profile.png');
@endphp

<nav class="navbar navbar-expand bg-white shadow-sm border-bottom">
    <div class="container-fluid px-3">

        {{-- 🏷 Page Title --}}
        <span class="navbar-brand mb-0 h1">@yield('page_title')</span>

        <div class="ms-auto d-flex align-items-center">

            {{-- 🔔 Notifications --}}
            <div class="dropdown me-3">
                @role('employee')
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exampleModal">
                        Request OT
                    </button>
                @endrole
                <button class="btn position-relative p-0" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-bell fs-4"></i>
                    @if ($unread->count())
                        <span class="badge bg-danger position-absolute top-0 start-100 translate-middle">
                            {{ $unread->count() }}
                        </span>
                    @endif
                </button>

                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li class="dropdown-header fw-semibold">Notifications</li>
                    @forelse($unread as $note)
                        <li>
                            <a class="dropdown-item" href="{{ route('notifications.show', $note->id) }}">
                                <strong>{{ $note->data['title'] ?? 'Notification' }}</strong><br>
                                <small class="text-muted">{{ $note->created_at->diffForHumans() }}</small>
                            </a>
                        </li>
                    @empty
                        <li><span class="dropdown-item text-muted">No new notifications</span></li>
                    @endforelse
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li>
                        <a class="dropdown-item text-center" href="{{ route('notifications.index') }}">
                            View all notifications
                        </a>
                    </li>
                </ul>
            </div>

            {{-- 👤 User Dropdown --}}
            <div class="dropdown">
                <a class="d-flex align-items-center text-decoration-none dropdown-toggle" href="#"
                    id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">

                    <img src="{{ $avatarUrl }}" alt="Profile Picture" class="me-2" width="46" height="46">


                    {{-- 🧾 User Info --}}
                    <div class="text-end">
                        <div class="fw-bold text-brand">{{ $user->name }}</div>
                        <small class="text-muted">{{ ucfirst($user->role->name ?? 'Employee') }}</small>
                    </div>
                </a>

                {{-- 🔽 Dropdown Menu --}}
                <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="userDropdown">
                    <li>
                        <a class="dropdown-item" href="{{ route('profile.edit') }}">
                            <i class="bi bi-person me-2"></i> My Profile
                        </a>
                    </li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li>
                        <a class="dropdown-item text-danger" href="#"
                            onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            <i class="bi bi-box-arrow-right me-2"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>

            {{-- 🔒 Hidden Logout Form --}}
            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                @csrf
            </form>

        </div>
    </div>
</nav>

<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Request Overtime</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form action="{{ route('employee.overtime-request.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="">Start At</label>
                        <input type="datetime-local" name="starts_at" class="form-control">
                    </div>

                    <div class="form-group">
                        <label for="">Ends At</label>
                        <input type="datetime-local" name="ends_at" class="form-control">
                    </div>

                    <div class="form-group">
                        <label for="">Task Description</label>
                        <textarea class="form-control" name="task_description"></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save changes</button>
                </div>
            </form>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
