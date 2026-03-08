<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}"
    class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('common.app_name'))</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;600;700;800;900&display=swap"
        rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            font-family: 'IBM Plex Sans Arabic', system-ui, sans-serif;
        }
    </style>
    @stack('head')
</head>

<body class="h-full bg-gray-950 text-white antialiased">
    @yield('content')

    {{-- Locale switcher (fixed corner) --}}
    <div class="fixed bottom-4 {{ app()->getLocale() === 'ar' ? 'left-4' : 'right-4' }} z-50">
        @if (app()->getLocale() === 'ar')
            <a href="{{ route('locale.switch', 'en') }}"
                class="text-xs px-3 py-1 bg-gray-800 hover:bg-gray-700 text-gray-400 rounded-full transition">
                {{ __('common.switch_to_en') }}
            </a>
        @else
            <a href="{{ route('locale.switch', 'ar') }}"
                class="text-xs px-3 py-1 bg-gray-800 hover:bg-gray-700 text-gray-400 rounded-full transition">
                {{ __('common.switch_to_ar') }}
            </a>
        @endif
    </div>

    @stack('scripts')
</body>

</html>
