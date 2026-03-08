@extends('layouts.admin')

@section('title', $competition->title . ' — ' . __('admin.competition.title'))

@section('content')

    {{-- Back link + heading --}}
    <div class="flex items-start justify-between mb-6 flex-wrap gap-3">
        <div>
            <a href="{{ route('admin.competitions.index') }}"
                class="text-sm text-gray-500 hover:text-gray-700 transition mb-2 inline-block">
                ← {{ __('admin.competition.back') }}
            </a>
            <h2 class="text-2xl font-bold text-gray-800">{{ $competition->title }}</h2>
        </div>
        <div class="flex items-center gap-3 flex-wrap">
            @include('admin._partials.status-badge', ['status' => $competition->status->value])

            @if (!$competition->isEnded() && $competition->status->value !== 'setup')
                <form method="POST" action="{{ route('admin.competitions.end', $competition->id) }}"
                    onsubmit="return confirm('{{ __('admin.competition.end_confirm') }}')">
                    @csrf
                    <button type="submit"
                        class="px-4 py-2 text-sm bg-red-600 hover:bg-red-500 text-white font-semibold rounded-lg transition">
                        {{ __('admin.competition.end_competition') }}
                    </button>
                </form>
            @elseif ($competition->isEnded())
                <span class="text-xs text-gray-400 italic">{{ __('admin.competition.already_ended') }}</span>
            @endif
        </div>
    </div>

    {{-- ======================================================
     Section 1: Overview
     ====================================================== --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">

        {{-- Room / timestamps --}}
        <div class="bg-white rounded-2xl shadow p-6 space-y-3">
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-2">
                    <dt class="text-gray-500 font-medium">{{ __('admin.competition.room_code') }}</dt>
                    <dd class="font-mono font-bold text-gray-800">{{ $competition->room_code }}</dd>
                </div>
                <div class="flex justify-between gap-2">
                    <dt class="text-gray-500 font-medium">{{ __('admin.competition.created_at') }}</dt>
                    <dd class="text-gray-700">{{ $competition->created_at->format('Y-m-d H:i') }}</dd>
                </div>
                <div class="flex justify-between gap-2">
                    <dt class="text-gray-500 font-medium">{{ __('admin.competition.started_at') }}</dt>
                    <dd class="text-gray-700">
                        {{ $competition->started_at ? $competition->started_at->format('Y-m-d H:i') : __('admin.competition.not_started') }}
                    </dd>
                </div>
                <div class="flex justify-between gap-2">
                    <dt class="text-gray-500 font-medium">{{ __('admin.competition.ended_at') }}</dt>
                    <dd class="text-gray-700">
                        {{ $competition->ended_at ? $competition->ended_at->format('Y-m-d H:i') : __('admin.competition.not_ended') }}
                    </dd>
                </div>
            </dl>
        </div>

        {{-- Judge info --}}
        <div class="bg-white rounded-2xl shadow p-6">
            <h3 class="text-base font-semibold text-gray-700 mb-4">{{ __('admin.competition.judge_section') }}</h3>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-2">
                    <dt class="text-gray-500 font-medium">{{ __('admin.competition.judge_name') }}</dt>
                    <dd class="text-gray-800">{{ $competition->judge_name ?: __('admin.competition.unknown') }}</dd>
                </div>
                <div class="flex justify-between gap-2">
                    <dt class="text-gray-500 font-medium">{{ __('admin.competition.judge_email') }}</dt>
                    <dd class="text-gray-800">{{ $competition->judge_email ?: __('admin.competition.unknown') }}</dd>
                </div>
            </dl>
        </div>

        {{-- Quick stats --}}
        <div class="bg-white rounded-2xl shadow p-6 flex flex-col justify-center gap-4">
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-500 font-medium">{{ __('admin.competitions.col_contestants') }}</span>
                <span class="text-2xl font-bold text-amber-600">{{ $competition->contestants->count() }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-500 font-medium">{{ __('admin.competitions.col_rounds') }}</span>
                <span class="text-2xl font-bold text-gray-700">{{ $competition->rounds->count() }}</span>
            </div>
        </div>
    </div>

    {{-- ======================================================
     Section 2: Contestants & Scores
     ====================================================== --}}
    <div class="bg-white rounded-2xl shadow overflow-hidden mb-8">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="text-base font-semibold text-gray-700">{{ __('admin.competition.contestants_section') }}</h3>
        </div>

        @php
            $sorted = $competition->contestants->sortByDesc('score')->values();
            $winner = $sorted->first();
        @endphp

        <table class="min-w-full text-sm">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-100">
                    <th class="px-4 py-3 text-start font-semibold text-gray-600 w-12">
                        {{ __('admin.competition.col_rank') }}</th>
                    <th class="px-4 py-3 text-start font-semibold text-gray-600">{{ __('admin.competition.col_name') }}
                    </th>
                    <th class="px-4 py-3 text-center font-semibold text-gray-600">{{ __('admin.competition.col_score') }}
                    </th>
                    <th class="px-4 py-3 text-start font-semibold text-gray-600 hidden sm:table-cell">
                        {{ __('admin.competition.col_claimed') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($sorted as $idx => $contestant)
                    <tr class="{{ $idx === 0 && $competition->isEnded() ? 'bg-amber-50' : '' }}">
                        <td class="px-4 py-3 text-center font-bold text-gray-500">{{ $idx + 1 }}</td>
                        <td class="px-4 py-3 text-gray-800 font-medium">
                            {{ $contestant->display_name }}
                            @if ($idx === 0 && $competition->isEnded())
                                <span
                                    class="ms-2 inline-block px-2 py-0.5 text-xs bg-amber-400 text-gray-900 font-bold rounded-full">
                                    🏆 {{ __('admin.competition.winner_badge') }}
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center font-bold text-gray-800">{{ $contestant->score }}</td>
                        <td class="px-4 py-3 text-gray-400 text-xs hidden sm:table-cell">
                            {{ $contestant->claimed_at ? $contestant->claimed_at->format('Y-m-d H:i') : __('admin.competition.unclaimed') }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- ======================================================
     Section 3: Round History
     ====================================================== --}}
    <div class="bg-white rounded-2xl shadow overflow-hidden mb-8">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="text-base font-semibold text-gray-700">{{ __('admin.competition.rounds_section') }}</h3>
        </div>

        @if ($competition->rounds->isEmpty())
            <p class="px-6 py-8 text-center text-gray-400 text-sm">{{ __('admin.competition.no_rounds') }}</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100">
                            <th class="px-4 py-3 text-start font-semibold text-gray-600">
                                {{ __('admin.competition.col_round_no') }}</th>
                            <th class="px-4 py-3 text-center font-semibold text-gray-600">
                                {{ __('admin.competition.col_round_status') }}</th>
                            <th class="px-4 py-3 text-start font-semibold text-gray-600">
                                {{ __('admin.competition.col_first_buzz') }}</th>
                            <th class="px-4 py-3 text-start font-semibold text-gray-600 hidden md:table-cell">
                                {{ __('admin.competition.col_buzz_opened') }}</th>
                            <th class="px-4 py-3 text-start font-semibold text-gray-600 hidden md:table-cell">
                                {{ __('admin.competition.col_first_buzzed') }}</th>
                            <th class="px-4 py-3 text-start font-semibold text-gray-600 hidden lg:table-cell">
                                {{ __('admin.competition.col_resolved') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($competition->rounds as $round)
                            <tr>
                                <td class="px-4 py-3 font-bold text-gray-700">{{ $round->round_number }}</td>
                                <td class="px-4 py-3 text-center">
                                    @include('admin._partials.status-badge', [
                                        'status' => $round->status->value,
                                    ])
                                </td>
                                <td class="px-4 py-3 text-gray-700 font-medium">
                                    {{ $round->firstBuzzContestant?->display_name ?? __('admin.competition.no_buzz') }}
                                </td>
                                <td class="px-4 py-3 text-gray-400 text-xs hidden md:table-cell">
                                    {{ $round->buzz_opened_at?->format('H:i:s') ?? __('admin.competition.no_buzz') }}
                                </td>
                                <td class="px-4 py-3 text-gray-400 text-xs hidden md:table-cell">
                                    {{ $round->first_buzzed_at?->format('H:i:s') ?? __('admin.competition.no_buzz') }}
                                </td>
                                <td class="px-4 py-3 text-gray-400 text-xs hidden lg:table-cell">
                                    {{ $round->resolved_at?->format('H:i:s') ?? __('admin.competition.no_buzz') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- ======================================================
     Section 4: Buzz Attempt Log
     ====================================================== --}}
    <div class="bg-white rounded-2xl shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="text-base font-semibold text-gray-700">{{ __('admin.competition.buzz_log_section') }}</h3>
        </div>

        @php
            $allAttempts = $competition->rounds
                ->flatMap(fn($r) => $r->buzzAttempts->map(fn($a) => ['round' => $r->round_number, 'attempt' => $a]))
                ->sortByDesc(fn($item) => $item['attempt']->attempted_at);
        @endphp

        @if ($allAttempts->isEmpty())
            <p class="px-6 py-8 text-center text-gray-400 text-sm">{{ __('admin.competition.no_attempts') }}</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100">
                            <th class="px-4 py-3 text-start font-semibold text-gray-600">
                                {{ __('admin.competition.col_attempt_round') }}</th>
                            <th class="px-4 py-3 text-start font-semibold text-gray-600">
                                {{ __('admin.competition.col_attempt_contestant') }}</th>
                            <th class="px-4 py-3 text-start font-semibold text-gray-600">
                                {{ __('admin.competition.col_attempt_time') }}</th>
                            <th class="px-4 py-3 text-center font-semibold text-gray-600">
                                {{ __('admin.competition.col_attempt_accepted') }}</th>
                            <th class="px-4 py-3 text-start font-semibold text-gray-600 hidden md:table-cell">
                                {{ __('admin.competition.col_attempt_reason') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($allAttempts as $item)
                            @php $attempt = $item['attempt']; @endphp
                            <tr class="{{ $attempt->accepted ? 'bg-green-50' : '' }}">
                                <td class="px-4 py-3 font-bold text-gray-600">{{ $item['round'] }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $attempt->contestant?->display_name ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-500 text-xs font-mono">
                                    {{ $attempt->attempted_at->format('H:i:s.u') }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if ($attempt->accepted)
                                        <span
                                            class="inline-block px-2 py-0.5 text-xs bg-green-100 text-green-700 font-semibold rounded-full">
                                            {{ __('admin.competition.accepted_yes') }}
                                        </span>
                                    @else
                                        <span
                                            class="inline-block px-2 py-0.5 text-xs bg-red-100 text-red-600 font-semibold rounded-full">
                                            {{ __('admin.competition.accepted_no') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-400 text-xs hidden md:table-cell">
                                    {{ $attempt->rejection_reason ?? '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

@endsection
