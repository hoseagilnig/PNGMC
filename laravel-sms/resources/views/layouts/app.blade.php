<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'PNG Maritime College SMS')</title>

    <!-- Styles -->
    <link rel="stylesheet" href="{{ asset('css/sms_styles.css') }}">
    <link rel="stylesheet" href="{{ asset('css/d_styles.css') }}">
    @stack('styles')
</head>
<body>
    @auth
        @include('partials.navigation')
    @endauth

    <main>
        @yield('content')
    </main>

    @include('partials.footer')

    <!-- Scripts -->
    <script src="{{ asset('js/app.js') }}"></script>
    @stack('scripts')
</body>
</html>

