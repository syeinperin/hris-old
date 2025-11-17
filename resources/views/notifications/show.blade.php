@extends('layouts.app')

@section('page_title', $note->data['title'])

@section('content')
<div class="container py-4">
  <div class="card">
    <div class="card-header">{{ $note->data['title'] }}</div>
    <div class="card-body">
      <p>{{ $note->data['message'] ?? '' }}</p>
      <hr>
      <h5>Infraction Report #{{ $infraction->id }}</h5>

      {{-- Employee name --}}
      <p>
        <strong>Employee:</strong>
        {{ $infraction->employee->user->name }}
      </p>

      {{-- Incident date (cast as Carbon in your model) --}}
      <p>
        <strong>Date:</strong>
        {{ $infraction->incident_date
            ? $infraction->incident_date->format('M j, Y')
            : 'N/A' }}
      </p>

      <p>
        <strong>Description:</strong><br>
        {{ $infraction->description }}
      </p>

      @if($infraction->actions->isNotEmpty())
        <hr>
        <h6>Disciplinary Actions</h6>
        <ul>
          @foreach($infraction->actions as $action)
            <li>
              [{{ strtoupper($action->type->code) }}]
              {{ $action->remarks }} –
              <small>{{ $action->created_at->diffForHumans() }}</small>
            </li>
          @endforeach
        </ul>
      @endif
    </div>
  </div>

  <a href="{{ route('notifications.show') }}" class="btn btn-secondary mt-3">
    ← Back to Notifications
  </a>
</div>
@endsection
