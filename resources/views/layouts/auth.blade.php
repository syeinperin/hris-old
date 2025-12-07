<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title','ASIATEX HRTrack')</title>
  <meta name="csrf-token" content="{{ csrf_token() }}">

  {{-- Poppins --}}
  <link
    href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
    rel="stylesheet"
  >

  {{-- Bootstrap CSS --}}
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
  >

  {{-- Bootstrap Icons --}}
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css"
    rel="stylesheet"
  >

  {{-- Your App CSS (includes login‐page styles) --}}
  <link href="{{ asset('css/app.css') }}" rel="stylesheet">
</head>
<body class="bg-light">

  @yield('content')

  {{-- Bootstrap JS --}}
  <script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
    defer
  ></script>
</body>
</html>
