@extends('layouts.auth')

@section('title','Log In – ASIATEX HRTrack')

@section('content')
<div class="login-wrapper">
  <div class="login-panel">
    <div class="login-left">
      <img src="{{ asset('images/logo.png') }}" alt="ASIATEX Logo">
      <h1>HRTrack</h1>
      <p>
        Human Resource Information System<br>
        for Asia Textile Mills, Inc.
      </p>
    </div>

    <div class="login-right">
      <h2>Log In</h2>
      <p class="text-muted mb-4">Enter your credentials</p>

      @if ($errors->any())
        <div class="alert alert-danger">
          <ul class="mb-0">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <form method="POST" action="{{ route('login') }}">
        @csrf

        {{-- Email OR Contact Number --}}
        <div class="mb-3">
          <input
            id="login"
            type="text"
            name="login"
            class="form-control @error('login') is-invalid @enderror"
            placeholder="Email or Contact Number"
            value="{{ old('login') }}"
            autocomplete="username"
            required
          >
          @error('login')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>

        <div class="mb-3">
          <input
            id="password"
            type="password"
            name="password"
            class="form-control @error('password') is-invalid @enderror"
            placeholder="Password"
            autocomplete="current-password"
            required
          >
          @error('password')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>

        <button type="submit" class="btn btn-primary w-100">
          Log In
        </button>
      </form>
    </div>
  </div>
</div>
@endsection

