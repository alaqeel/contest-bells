<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Contest Bells') — Quiz Buzzer</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>

<body class="h-full bg-gray-950 text-white antialiased">
    @yield('content')
    @stack('scripts')
</body>

</html>
