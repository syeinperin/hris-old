@extends('layouts.app')

@section('page_title', 'View Concern')

@section('content')

<div class="container-fluid">

    <h3 class="mb-4">Concern Details</h3>

    <div class="card shadow-sm mb-4">
        <div class="card-body">

            <h5 class="fw-bold">{{ $concern->subject }}</h5>
            <p class="text-muted mb-2">
                Submitted by: <strong>{{ $concern->employee->full_name }}</strong><br>
                Category: <strong>{{ $concern->category->name }}</strong><br>
                Status: <span class="badge bg-primary">{{ ucfirst($concern->status) }}</span><br>
                Date: {{ $concern->created_at->format('Y-m-d') }}
            </p>

            <hr>

            <h6>Description</h6>
            <p>{{ $concern->description }}</p>

            <hr>

            {{-- Attachments --}}
            @if($concern->attachments->count())
                <h6>Attachments</h6>
                <ul>
                    @foreach($concern->attachments as $file)
                        <li>
                            <a href="{{ asset('storage/'.$file->file_path) }}" target="_blank">
                                {{ $file->file_name }}
                            </a>
                        </li>
                    @endforeach
                </ul>
                <hr>
            @endif

            {{-- Reply Section --}}
            <h6>Replies</h6>

            @forelse($concern->replies as $r)
                <div class="border rounded p-2 mb-2">
                    <strong>{{ $r->user->name }}</strong>
                    <span class="text-muted" style="font-size: .85rem;">
                        {{ $r->created_at->diffForHumans() }}
                    </span>
                    <p class="mb-0">{{ $r->message }}</p>
                </div>
            @empty
                <p class="text-muted">No replies yet.</p>
            @endforelse

            <hr>

            {{-- Reply Form --}}
            <form method="POST" action="{{ route('concerns.reply', $concern->id) }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Add Reply</label>
                    <textarea name="message" rows="3" class="form-control" required></textarea>
                </div>
                <button class="btn btn-primary">Submit Reply</button>
            </form>

        </div>
    </div>

</div>

@endsection
