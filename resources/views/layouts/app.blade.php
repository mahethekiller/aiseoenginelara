<!DOCTYPE html>
<html lang="en" data-theme="night">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'AI SEO Engine') - Content Intelligence</title>
    
    <!-- Instant Theme Loader (Zero Flicker) -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'night';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="bg-base-100 text-base-content min-h-screen font-sans selection:bg-primary/25 selection:text-primary">
    <div class="drawer lg:drawer-open">
        <input id="app-drawer" type="checkbox" class="drawer-toggle" />
        
        <!-- Page Content Wrapper -->
        <div class="drawer-content flex flex-col min-h-screen bg-base-100">
            <!-- Top Navbar -->
            @include('components.navbar')

            <!-- Main Page View Content -->
            <main class="flex-1 p-4 lg:p-6 bg-base-200/50 overflow-y-auto">
                @yield('content')
            </main>

            <!-- Bottom Statusbar -->
            @include('components.statusbar')
        </div>

        <!-- Sidebar Drawer Side -->
        <div class="drawer-side z-40">
            <label for="app-drawer" aria-label="close sidebar" class="drawer-overlay"></label>
            @include('components.sidebar')
        </div>
    </div>

    <!-- Global Toast Container -->
    <div id="toast-container" class="toast toast-top toast-end z-[999999] pointer-events-none *:pointer-events-auto"></div>

    <!-- Global Scripts -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (window.$) {
                $.ajaxSetup({
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
                });
            }
            if (typeof window.createIcons === 'function') {
                window.createIcons();
            } else if (window.lucide && window.lucide.createIcons) {
                window.lucide.createIcons();
            }
        });
    </script>
    @stack('scripts')
</body>
</html>
