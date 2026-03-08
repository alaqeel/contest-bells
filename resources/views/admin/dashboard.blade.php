@extends('layouts.admin')

@section('title', __('admin.dashboard.title'))

@section('content')

    {{-- Page heading --}}
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-800">{{ __('admin.dashboard.title') }}</h2>
        <p class="mt-1 text-gray-500 text-sm">
            {{ __('admin.dashboard.welcome', ['name' => auth()->user()->name]) }}
        </p>
    </div>

    {{-- Summary cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-10">

        {{-- Total --}}
        <div class="col-span-2 lg:col-span-1 bg-white rounded-2xl shadow p-6 flex flex-col gap-1">
            <span class="text-4xl font-extrabold text-gray-800">{{ $summary['total'] }}</span>
            <span class="text-sm text-gray-500 font-medium">{{ __('admin.dashboard.total') }}</span>
        </div>

        {{-- Setup --}}
        <div class="bg-white rounded-2xl shadow p-6 flex flex-col gap-1">
            <span class="text-4xl font-extrabold text-blue-600">{{ $summary['setup'] }}</span>
            <span class="text-sm text-gray-500 font-medium">{{ __('admin.dashboard.setup') }}</span>
        </div>

        {{-- Active --}}
        <div class="bg-white rounded-2xl shadow p-6 flex flex-col gap-1">
            <span class="text-4xl font-extrabold text-green-600">{{ $summary['active'] }}</span>
            <span class="text-sm text-gray-500 font-medium">{{ __('admin.dashboard.active') }}</span>
        </div>

        {{-- Ended --}}
        <div class="bg-white rounded-2xl shadow p-6 flex flex-col gap-1">
            <span class="text-4xl font-extrabold text-gray-500">{{ $summary['ended'] }}</span>
            <span class="text-sm text-gray-500 font-medium">{{ __('admin.dashboard.ended') }}</span>
        </div>

        {{-- Total contestants --}}
        <div class="bg-white rounded-2xl shadow p-6 flex flex-col gap-1">
            <span class="text-4xl font-extrabold text-amber-600">{{ $summary['total_contestants'] }}</span>
            <span class="text-sm text-gray-500 font-medium">{{ __('admin.dashboard.total_contestants') }}</span>
        </div>

    </div>

    {{-- CTA --}}
    <div>
        <a href="{{ route('admin.competitions.index') }}"
            class="inline-flex items-center gap-2 px-6 py-3 bg-gray-900 hover:bg-gray-800 text-white font-semibold rounded-xl transition text-sm">
            {{ __('admin.dashboard.view_all') }}
            <span class="{{ app()->getLocale() === 'ar' ? 'rotate-180' : '' }} inline-block">→</span>
        </a>
    </div>

@endsection
