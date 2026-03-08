<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}"
    class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('admin.dashboard.title')) — {{ __('common.app_name') }}</title>
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

<body class="h-full bg-gray-100 text-gray-900 antialiased">

    {{-- ===== Top navigation bar ===== --}}
    <nav class="bg-gray-900 text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">

                {{-- Brand + primary nav --}}
                <div class="flex items-center gap-6">
                    <a href="{{ route('admin.dashboard') }}"
                        class="text-lg font-bold tracking-tight text-amber-400 hover:text-amber-300 transition">
                        {{ __('common.app_name') }}
                        <span class="text-xs text-gray-400 ms-1">admin</span>
                    </a>

                    <a href="{{ route('admin.dashboard') }}"
                        class="text-sm {{ request()->routeIs('admin.dashboard') ? 'text-white' : 'text-gray-400 hover:text-white' }} transition">
                        {{ __('admin.nav.dashboard') }}
                    </a>

                    <a href="{{ route('admin.competitions.index') }}"
                        class="text-sm {{ request()->routeIs('admin.competitions.*') ? 'text-white' : 'text-gray-400 hover:text-white' }} transition">
                        {{ __('admin.nav.competitions') }}
                    </a>
                </div>

                {{-- Right side: locale + logout --}}
                <div class="flex items-center gap-4">
                    {{-- Locale switcher --}}
                    @if (app()->getLocale() === 'ar')
                        <a href="{{ route('locale.switch', 'en') }}"
                            class="text-xs px-3 py-1 bg-gray-700 hover:bg-gray-600 text-gray-300 rounded-full transition">
                            {{ __('common.switch_to_en') }}
                        </a>
                    @else
                        <a href="{{ route('locale.switch', 'ar') }}"
                            class="text-xs px-3 py-1 bg-gray-700 hover:bg-gray-600 text-gray-300 rounded-full transition">
                            {{ __('common.switch_to_ar') }}
                        </a>
                    @endif

                    {{-- Logged-in user --}}
                    <span class="text-sm text-gray-400 hidden sm:inline">{{ auth()->user()->name }}</span>

                    {{-- Logout --}}
                    <form method="POST" action="{{ route('admin.logout') }}" class="inline">
                        @csrf
                        <button type="submit"
                            class="text-sm px-3 py-1.5 bg-red-700 hover:bg-red-600 rounded transition">
                            {{ __('admin.auth.logout') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    {{-- ===== Page content ===== --}}
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- Flash messages --}}
        @if (session('success'))
            <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-800 rounded-lg text-sm">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-6 p-4 bg-red-100 border border-red-400 text-red-800 rounded-lg text-sm">
                {{ session('error') }}
            </div>
        @endif

        @yield('content')
    </main>

    @stack('scripts')
</body>

</html>
