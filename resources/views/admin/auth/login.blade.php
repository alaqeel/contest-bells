<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}"
    class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('admin.auth.login_title') }} — {{ __('common.app_name') }}</title>
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
</head>

<body class="h-full bg-gray-950 text-white antialiased flex items-center justify-center min-h-screen">

    <div class="w-full max-w-md px-4">

        {{-- Brand --}}
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-amber-400">{{ __('common.app_name') }}</h1>
            <p class="mt-1 text-gray-400 text-sm">{{ __('admin.auth.login_title') }}</p>
        </div>

        {{-- Card --}}
        <div class="bg-gray-900 rounded-2xl shadow-xl p-8">

            {{-- Validation errors --}}
            @if ($errors->any())
                <div class="mb-5 p-4 bg-red-900/50 border border-red-700 rounded-lg text-sm text-red-300">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.login.submit') }}" class="space-y-5">
                @csrf

                {{-- Email --}}
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-300 mb-1.5">
                        {{ __('admin.auth.email') }}
                    </label>
                    <input id="email" name="email" type="email" autocomplete="email"
                        value="{{ old('email') }}" required autofocus
                        class="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition text-sm">
                </div>

                {{-- Password --}}
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-300 mb-1.5">
                        {{ __('admin.auth.password') }}
                    </label>
                    <input id="password" name="password" type="password" autocomplete="current-password" required
                        class="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition text-sm">
                </div>

                {{-- Remember --}}
                <div class="flex items-center gap-2">
                    <input id="remember" name="remember" type="checkbox"
                        class="rounded border-gray-600 bg-gray-800 text-amber-500 focus:ring-amber-500">
                    <label for="remember" class="text-sm text-gray-400">
                        {{ __('admin.auth.remember') }}
                    </label>
                </div>

                {{-- Submit --}}
                <button type="submit"
                    class="w-full py-3 bg-amber-500 hover:bg-amber-400 text-gray-900 font-bold rounded-lg transition text-sm">
                    {{ __('admin.auth.login_button') }}
                </button>
            </form>
        </div>

        {{-- Locale switcher --}}
        <div class="mt-6 text-center">
            @if (app()->getLocale() === 'ar')
                <a href="{{ route('locale.switch', 'en') }}"
                    class="text-xs text-gray-500 hover:text-gray-300 transition">
                    {{ __('common.switch_to_en') }}
                </a>
            @else
                <a href="{{ route('locale.switch', 'ar') }}"
                    class="text-xs text-gray-500 hover:text-gray-300 transition">
                    {{ __('common.switch_to_ar') }}
                </a>
            @endif
        </div>
    </div>

</body>

</html>
