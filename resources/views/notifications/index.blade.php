@extends('layouts.app')

@section('page_title','My Notifications')

@push('styles')
<style>
    /* Unread highlight */
    .notif-unread {
        background: #f4f6fa !important;
    }

    /* Title color (Asiatex Blue) */
    .notif-title {
        color: #0A2342;
        font-size: 1rem;
        font-weight: 700;
    }

    /* Message text */
    .notif-message {
        color: #34495e;
        font-size: 0.95rem;
        line-height: 1.4;
    }

    /* Date muted color */
    .notif-date {
        color: #6c757d;
        font-size: 0.82rem;
    }

    /* Button styling (Asiatex theme) */
    .notif-btn {
        border: 1px solid #0A2342 !important;
        color: #ffffffff !important;
        padding: 3px 12px;
        border-radius: 6px;
        background: transparent;
        transition: 0.2s ease-in-out;
    }

    .notif-btn:hover {
        background: #0A2342 !important;
        color: #fff !important;
    }

    /* List item spacing */
    .list-group-item {
        padding: 1rem 1.25rem;
    }
</style>
@endpush

@section('content')
<div class="container py-4">
  <div class="row justify-content-center">
    <div class="col-md-8">

      <div class="card shadow-sm">
        <div class="card-header bg-white">
          <h5 class="mb-0 fw-bold" style="color:#0A2342;">
            <i class="bi bi-bell me-2"></i> My Notifications
          </h5>
        </div>

        <div class="card-body p-0">

          <ul class="list-group list-group-flush">

            @forelse($notifications as $n)
              @php
                  $data = $n->data ?? [];
                  $isUnread = is_null($n->read_at);
                  $date = $n->created_at?->format('M j, Y • g:i A') ?? '';
              @endphp

              <li class="list-group-item d-flex justify-content-between align-items-start {{ $isUnread ? 'notif-unread' : '' }}">
                <div class="me-3 flex-grow-1">

                  {{-- Title --}}
                  <div class="notif-title">
                    {{ $data['title'] ?? 'Notification' }}
                  </div>

                  {{-- Message --}}
                  <div class="notif-message mt-1">
                    {!! nl2br(e($data['message'] ?? '')) !!}
                  </div>

                  {{-- Date --}}
                  <small class="notif-date d-block mt-2">
                    {{ $date }}
                  </small>
                </div>

                {{-- Mark Read Button --}}
                @if($isUnread)
                <form method="POST" action="{{ route('notifications.markRead', $n->id) }}">
                    @csrf
                    <button type="submit" class="btn btn-sm notif-btn">
                      Mark read
                    </button>
                </form>
                @endif
              </li>

            @empty
              <li class="list-group-item text-center text-muted py-4">
                <i class="bi bi-inbox"></i> No notifications yet.
              </li>
            @endforelse

          </ul>

        </div>
      </div>

      <div class="mt-3">
        {{ $notifications->links() }}
      </div>

    </div>
  </div>
</div>
@endsection
