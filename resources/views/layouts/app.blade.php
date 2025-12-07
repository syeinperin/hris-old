<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>@yield('page_title', 'ASIATEX HRTrack')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Choices.js -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />

    <!-- App CSS -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">

    <!-- GLOBAL LAYOUT FIX -->
    <style>
        html,
        body {
            height: 100%;
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            overflow-x: hidden !important;
            overflow-y: auto !important;
        }

        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .layout-wrapper {
            display: flex;
            flex: 1;
            overflow: hidden;
        }

        /* Sidebar */
        .sidebar {
            flex: 0 0 250px;
            background-color: #fff;
            border-right: 1px solid #dee2e6;
            overflow-y: auto;
            overflow-x: hidden;
            height: 100vh;
            position: sticky;
            top: 0;
            z-index: 1020;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
            overflow: visible;
            position: relative;
        }

        /* Navbar */
        .navbar {
            position: sticky;
            top: 0;
            z-index: 1040 !important;
            background: #fff;
            border-bottom: 1px solid #dee2e6;
        }

        .navbar * {
            overflow: visible !important;
        }

        /* Dropdown - always visible */
        .navbar .dropdown-menu {
            position: absolute !important;
            top: 100% !important;
            right: 0 !important;
            left: auto !important;
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 0.5rem;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
            z-index: 2000 !important;
            width: 260px;
            overflow: visible !important;
        }

        /* Content */
        .app-content {
            flex: 1;
            overflow-y: auto;
            padding: 1.5rem;
            background: #ffffff;
        }

        .app-footer {
            background: #fff;
            border-top: 1px solid #dee2e6;
            text-align: center;
            font-size: 0.9rem;
            color: #666;
            padding: 0.75rem 0;
            flex-shrink: 0;
        }

        .table-responsive {
            overflow-x: visible !important;
            width: 100%;
        }

        .pagination {
            margin-bottom: 0;
            justify-content: flex-end;
        }

        /* ==========================================================
       RED ASTERISK FIX — GLOBAL
       ========================================================== */
        .required-asterisk {
            color: #dc3545 !important;
            font-weight: 600;
        }
    </style>

    @stack('styles')
</head>

<body>
    <div class="layout-wrapper">

        {{-- Sidebar --}}
        <aside class="sidebar">
            @include('partials.sidebar')
        </aside>

        {{-- Main Content --}}
        <div class="main-content">

            @include('partials.navbar')

            <main class="app-content">
                @yield('content')
            </main>

            <footer class="app-footer">
                &copy; {{ date('Y') }} ASIATEX HRTrack. All rights reserved.
            </footer>

        </div>
    </div>

    <!-- JS LIBRARIES -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>

    <!-- Choices.js -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.choices-multi').forEach(select => {
                new Choices(select, {
                    removeItemButton: true,
                    searchEnabled: true,
                    placeholder: true,
                    placeholderValue: 'Select employees...',
                    position: 'bottom',
                    shouldSort: false,
                });
            });
        });
    </script>

    <!-- ==========================================================
       JS: AUTO-WRAP ALL ASTERISKS IN LABELS
       ========================================================== -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            document.querySelectorAll("label").forEach(label => {
                if (label.innerHTML.includes("*")) {
                    label.innerHTML = label.innerHTML.replace(
                        "*",
                        `<span class="required-asterisk">*</span>`
                    );
                }
            });
        });
    </script>



    @stack('modals')
    @stack('scripts')

</body>

</html>
