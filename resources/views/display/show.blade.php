@extends('layouts.app')
@section('title', __('scoreboard.scoreboard') . ' — ' . $competition->title)

@push('head')
    <meta name="room-code" content="{{ $competition->room_code }}">
@endpush

@section('content')
    <div class="min-h-full flex flex-col items-center justify-center px-4 py-8" id="display">

        <h1 class="text-5xl font-black text-center mb-2">{{ $competition->title }}</h1>
        <p class="text-gray-500 text-sm mb-10">Room: <span
                class="font-mono text-indigo-400">{{ $competition->room_code }}</span></p>

        {{-- First buzzer --}}
        <div id="display-buzzer" class="text-center mb-10 min-h-[6rem]"
            style="{{ !$competition->currentRound?->first_buzz_contestant_id ? 'display:none' : '' }}">
            <p class="text-gray-400 text-sm">{{ __('scoreboard.buzzed_first') }}</p>
            <p class="text-7xl font-black text-yellow-400" id="display-buzzer-name">
                {{ $competition->currentRound?->firstBuzzContestant?->display_name ?? '' }}
            </p>
        </div>

        {{-- Scoreboard --}}
        <div class="w-full max-w-lg">
            <h2 class="text-xs text-gray-500 uppercase tracking-widest text-center mb-4">{{ __('scoreboard.scoreboard') }}</h2>
            <div class="space-y-3" id="display-scoreboard">
                @foreach ($competition->contestants->sortByDesc('score')->values() as $i => $c)
                    <div class="flex items-center gap-4 bg-gray-900 rounded-2xl px-6 py-4" data-cid="{{ $c->id }}">
                        <span class="text-3xl font-black text-gray-600">{{ $loop->iteration }}</span>
                        <span class="flex-1 text-2xl font-bold">{{ $c->display_name }}</span>
                        <span class="text-3xl font-black text-white"
                            data-score="{{ $c->id }}">{{ $c->score }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <p class="mt-10 text-gray-700 text-xs">{{ __('scoreboard.public_display') }}</p>
    </div>
@endsection

@push('scripts')
    <script>
        const ROOM_CODE = document.querySelector('meta[name="room-code"]').content;
        if (window.Echo) {
            window.Echo.channel('competition.' + ROOM_CODE)
                .listen('.BuzzAccepted', e => {
                    document.getElementById('display-buzzer').style.display = '';
                    document.getElementById('display-buzzer-name').textContent = e.contestant_name;
                })
                .listen('.RoundReset', () => {
                    document.getElementById('display-buzzer').style.display = 'none';
                })
                .listen('.RoundStarted', () => {
                    document.getElementById('display-buzzer').style.display = 'none';
                })
                .listen('.ScoreUpdated', e => {
                    e.scoreboard.forEach(c => {
                        const el = document.querySelector('[data-score="' + c.id + '"]');
                        if (el) el.textContent = c.score;
                    });
                })
                .listen('.CompetitionEnded', e => {
                    window.location.href = e.results_url;
                });
        }
    </script>
@endpush
