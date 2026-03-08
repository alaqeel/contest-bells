@extends('layouts.admin')

@section('title', __('admin.competitions.title'))

@section('content')

    {{-- Page heading --}}
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-800">{{ __('admin.competitions.title') }}</h2>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-2xl shadow p-5 mb-6">
        <form method="GET" action="{{ route('admin.competitions.index') }}"
            class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">

            {{-- Search --}}
            <div class="lg:col-span-2">
                <label class="block text-xs font-medium text-gray-500 mb-1">
                    {{ __('admin.competitions.search') }}
                </label>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                    placeholder="{{ __('admin.competitions.search') }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-400">
            </div>

            {{-- Status --}}
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">
                    {{ __('admin.competitions.filter_status') }}
                </label>
                <select name="status"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 bg-white">
                    <option value="">{{ __('admin.competitions.all_statuses') }}</option>
                    <option value="setup" @selected(($filters['status'] ?? '') === 'setup')>
                        {{ __('admin.competitions.status_setup') }}
                    </option>
                    <option value="active" @selected(($filters['status'] ?? '') === 'active')>
                        {{ __('admin.competitions.status_active') }}
                    </option>
                    <option value="ended" @selected(($filters['status'] ?? '') === 'ended')>
                        {{ __('admin.competitions.status_ended') }}
                    </option>
                </select>
            </div>

            {{-- From date --}}
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">
                    {{ __('admin.competitions.from_date') }}
                </label>
                <input type="date" name="from" value="{{ $filters['from'] ?? '' }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-400">
            </div>

            {{-- To date --}}
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">
                    {{ __('admin.competitions.to_date') }}
                </label>
                <input type="date" name="to" value="{{ $filters['to'] ?? '' }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-400">
            </div>

            {{-- Buttons --}}
            <div class="flex gap-2 sm:col-span-2 lg:col-span-5 justify-end">
                <a href="{{ route('admin.competitions.index') }}"
                    class="px-4 py-2 text-sm border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50 transition">
                    {{ __('admin.competitions.reset_filters') }}
                </a>
                <button type="submit"
                    class="px-5 py-2 text-sm bg-gray-900 hover:bg-gray-800 text-white font-semibold rounded-lg transition">
                    {{ __('admin.competitions.filter_button') }}
                </button>
            </div>
        </form>
    </div>

    {{-- Results table --}}
    <div class="bg-white rounded-2xl shadow overflow-hidden">
        @if ($competitions->isEmpty())
            <div class="p-12 text-center text-gray-400 text-sm">
                {{ __('admin.competitions.empty') }}
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="px-4 py-3 text-start font-semibold text-gray-600">
                                {{ __('admin.competitions.col_room') }}</th>
                            <th class="px-4 py-3 text-start font-semibold text-gray-600">
                                {{ __('admin.competitions.col_title') }}</th>
                            <th class="px-4 py-3 text-start font-semibold text-gray-600 hidden md:table-cell">
                                {{ __('admin.competitions.col_judge') }}</th>
                            <th class="px-4 py-3 text-center font-semibold text-gray-600">
                                {{ __('admin.competitions.col_contestants') }}</th>
                            <th class="px-4 py-3 text-center font-semibold text-gray-600 hidden lg:table-cell">
                                {{ __('admin.competitions.col_rounds') }}</th>
                            <th class="px-4 py-3 text-center font-semibold text-gray-600">
                                {{ __('admin.competitions.col_status') }}</th>
                            <th class="px-4 py-3 text-start font-semibold text-gray-600 hidden xl:table-cell">
                                {{ __('admin.competitions.col_created') }}</th>
                            <th class="px-4 py-3 text-end font-semibold text-gray-600">
                                {{ __('admin.competitions.col_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($competitions as $competition)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-4 py-3 font-mono font-bold text-gray-800">
                                    {{ $competition->room_code }}
                                </td>
                                <td class="px-4 py-3 text-gray-700 max-w-xs truncate">
                                    {{ $competition->title }}
                                </td>
                                <td class="px-4 py-3 text-gray-500 hidden md:table-cell">
                                    @if ($competition->judge_name)
                                        <span class="block font-medium text-gray-700">{{ $competition->judge_name }}</span>
                                        @if ($competition->judge_email)
                                            <span class="text-xs text-gray-400">{{ $competition->judge_email }}</span>
                                        @endif
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center text-gray-700">
                                    {{ $competition->contestants_count }}
                                </td>
                                <td class="px-4 py-3 text-center text-gray-700 hidden lg:table-cell">
                                    {{ $competition->rounds_count }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @include('admin._partials.status-badge', [
                                        'status' => $competition->status->value,
                                    ])
                                </td>
                                <td class="px-4 py-3 text-gray-400 text-xs hidden xl:table-cell">
                                    {{ $competition->created_at->format('Y-m-d H:i') }}
                                </td>
                                <td class="px-4 py-3 text-end">
                                    <a href="{{ route('admin.competitions.show', $competition->id) }}"
                                        class="inline-block px-3 py-1 text-xs bg-amber-500 hover:bg-amber-400 text-gray-900 font-semibold rounded-lg transition">
                                        {{ __('admin.competitions.view') }}
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if ($competitions->hasPages())
                <div class="px-4 py-4 border-t border-gray-100 flex items-center justify-between gap-4 flex-wrap">
                    <p class="text-xs text-gray-400">
                        {{ __('admin.competitions.pagination_info', [
                            'from' => $competitions->firstItem(),
                            'to' => $competitions->lastItem(),
                            'total' => $competitions->total(),
                        ]) }}
                    </p>
                    <div>
                        {{ $competitions->links() }}
                    </div>
                </div>
            @endif
        @endif
    </div>

@endsection
