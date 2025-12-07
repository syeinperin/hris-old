<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>@yield('page_title','Kiosk')</title>

  <meta name="csrf-token" content="{{ csrf_token() }}">

  {{-- Bootstrap CSS --}}
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link href="{{ asset('css/app.css') }}" rel="stylesheet">
</head>
<body class="bg-light">

  {{-- This is where each child view will inject its content --}}
  @yield('content')

  {{-- Bootstrap JS + app.js --}}
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="{{ asset('js/app.js') }}"></script>

  {{-- For any page‐specific scripts --}}
  @yield('scripts')
</body>
</html>
