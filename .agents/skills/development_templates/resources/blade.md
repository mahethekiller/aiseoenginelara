# Master Layout Blade Template

```html
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'HR Portal') - Antigravity</title>
    
    <!-- Local UI Assets -->
    <link href="{{ asset('assets/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/vendor/font-awesome/css/all.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/app.css') }}" rel="stylesheet">
    @stack('css')
</head>
<body class="bg-body-tertiary">
    <div class="d-flex">
        @include('layouts.sidebar')
        
        <main class="flex-grow-1 min-vh-100 p-4">
            @include('layouts.header')
            @yield('content')
        </main>
    </div>

    <!-- Local JS Scripts -->
    <script src="{{ asset('assets/vendor/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    @stack('js')
</body>
</html>
```