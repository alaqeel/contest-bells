@extends('layouts.app')
@section('title', __('judge.dashboard_title') . ' — ' . $competition->title)

@push('head')
    <meta name="room-code" content="{{ $competition->room_code }}">
@endpush

@section('content')
    <div class="min-h-full flex flex-col" id="dashboard">

        {{-- Header --}}
        <header class="bg-gray-900 border-b border-gray-800 px-4 py-3">
            <div class="max-w-5xl mx-auto flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="text-2xl">🔔</span>
                    <div>
                        <h1 class="font-bold text-lg leading-tight">{{ $competition->title }}</h1>
                        <p class="text-xs text-gray-400">{{ __('common.room') }}: <span
                                class="font-mono font-bold text-indigo-400">{{ $competition->room_code }}</span></p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span id="comp-status-badge"
                        class="text-xs px-3 py-1 rounded-full font-semibold
                           {{ $competition->isEnded() ? 'bg-red-900 text-red-300' : 'bg-green-900 text-green-300' }}">
                        {{ strtoupper($competition->status->value) }}
                    </span>
                    @if (!$competition->isEnded())
                        <form action="{{ route('judge.end', $competition->room_code) }}" method="POST"
                            onsubmit="return confirm('{{ __('judge.end_confirm') }}')">
                            @csrf
                            <button
                                class="text-xs px-3 py-1 bg-red-800/60 hover:bg-red-700 text-red-300 rounded-lg transition">
                                {{ __('judge.end_competition') }}
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </header>

        <main class="flex-1 max-w-5xl w-full mx-auto px-4 py-6 grid grid-cols-1 md:grid-cols-3 gap-6">

            {{-- LEFT: Round controls + Current Round --}}
            <div class="md:col-span-2 space-y-5">

                {{-- Join link card --}}
                <div class="bg-gray-900 rounded-2xl p-5 border border-gray-800">
                    <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-3">
                        {{ __('judge.share_with_contestants') }}
                    </h2>
                    <div class="flex gap-2">
                        <input id="join-url" readonly value="{{ url('/join/' . $competition->room_code) }}"
                            class="flex-1 bg-gray-800 rounded-xl px-3 py-2 text-sm text-indigo-300 font-mono truncate border border-gray-700">
                        <button onclick="copyJoinLink()"
                            class="px-4 py-2 bg-indigo-700 hover:bg-indigo-600 text-white rounded-xl text-sm font-semibold transition">
                            {{ __('common.copy') }}
                        </button>
                    </div>
                    {{-- QR code placeholder --}}
                    <div class="mt-3" id="qr-container">
                        {!! QrCode::size(120)->generate(url('/join/' . $competition->room_code)) !!}
                    </div>
                </div>

                {{-- Round status card --}}
                <div class="bg-gray-900 rounded-2xl p-5 border border-gray-800">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wider">
                            {{ __('judge.current_round') }}</h2>
                        <span id="round-badge" class="text-xs px-3 py-1 rounded-full font-bold bg-gray-800 text-gray-400">
                            {{ $competition->currentRound ? __('judge.round_number', ['number' => $competition->currentRound->round_number]) : __('judge.no_round') }}
                        </span>
                    </div>

                    {{-- First buzzer display --}}
                    <div id="first-buzzer-area"
                        class="text-center py-6 mb-4
                     {{ $competition->currentRound?->first_buzz_contestant_id ? 'block' : 'hidden' }}">
                        <p class="text-xs text-gray-500 mb-1">{{ __('judge.first_buzzer') }}</p>
                        <p class="text-3xl font-black text-yellow-400" id="first-buzzer-name">
                            {{ $competition->currentRound?->firstBuzzContestant?->display_name ?? '' }}
                        </p>
                        {{-- 10-second answer timer --}}
                        <div id="answer-timer" class="mt-2 text-xl font-mono font-bold text-orange-400 hidden">
                            <span id="timer-seconds">10</span>s
                        </div>
                    </div>

                    {{-- No buzz yet placeholder --}}
                    <div id="waiting-buzz"
                        class="text-center py-6 mb-4
                     {{ $competition->currentRound && !$competition->currentRound->first_buzz_contestant_id ? 'block' : ($competition->currentRound ? 'hidden' : 'block') }}">
                        <p class="text-gray-500 text-sm">
                            {{ $competition->currentRound ? __('judge.waiting_buzz') : __('judge.no_round_started') }}
                        </p>
                    </div>

                    {{-- Control buttons --}}
                    @if (!$competition->isEnded())
                        <div class="flex flex-wrap gap-3 justify-center" id="round-controls">

                            {{-- Start Round --}}
                            <button id="btn-start-round" onclick="startRound()"
                                class="px-5 py-3 bg-green-700 hover:bg-green-600 text-white font-bold rounded-xl transition text-sm shadow-lg shadow-green-900/30
                               {{ $competition->currentRound?->status->value === 'active' || $competition->currentRound?->status->value === 'locked' ? 'opacity-50 cursor-not-allowed' : '' }}"
                                {{ $competition->currentRound?->status->value === 'active' || $competition->currentRound?->status->value === 'locked' ? 'disabled' : '' }}>
                                {{ __('judge.start_round') }}
                            </button>

                            {{-- Reset Buzzers --}}
                            <button id="btn-reset" onclick="resetRound()"
                                class="px-5 py-3 bg-yellow-700 hover:bg-yellow-600 text-white font-bold rounded-xl transition text-sm
                               {{ !$competition->currentRound ? 'opacity-50 cursor-not-allowed' : '' }}"
                                {{ !$competition->currentRound ? 'disabled' : '' }}>
                                {{ __('judge.reset_buzzers') }}
                            </button>

                            {{-- Correct --}}
                            <button id="btn-correct" onclick="markAnswer('correct')"
                                class="px-5 py-3 bg-blue-700 hover:bg-blue-600 text-white font-bold rounded-xl transition text-sm
                               {{ $competition->currentRound?->status->value !== 'locked' ? 'opacity-50 cursor-not-allowed' : '' }}"
                                {{ $competition->currentRound?->status->value !== 'locked' ? 'disabled' : '' }}>
                                {{ __('judge.correct') }}
                            </button>

                            {{-- Wrong --}}
                            <button id="btn-wrong" onclick="markAnswer('wrong')"
                                class="px-5 py-3 bg-red-700 hover:bg-red-600 text-white font-bold rounded-xl transition text-sm
                               {{ $competition->currentRound?->status->value !== 'locked' ? 'opacity-50 cursor-not-allowed' : '' }}"
                                {{ $competition->currentRound?->status->value !== 'locked' ? 'disabled' : '' }}>
                                {{ __('judge.wrong') }}
                            </button>

                        </div>
                    @endif
                </div>

                {{-- Recent buzz log --}}
                <div class="bg-gray-900 rounded-2xl p-5 border border-gray-800">
                    <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-3">
                        {{ __('judge.event_log') }}</h2>
                    <ul id="event-log" class="text-xs text-gray-400 space-y-1 max-h-32 overflow-y-auto font-mono">
                        <li class="text-gray-600">{{ __('judge.ready') }}</li>
                    </ul>
                </div>
            </div>

            {{-- RIGHT: Contestants + Scoreboard --}}
            <div class="space-y-5">

                {{-- Contestants --}}
                <div class="bg-gray-900 rounded-2xl p-5 border border-gray-800">
                    <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-3">
                        {{ __('judge.contestants') }}</h2>
                    <ul class="space-y-2" id="contestant-list">
                        @foreach ($competition->contestants as $c)
                            <li id="contestant-{{ $c->id }}"
                                class="flex items-center justify-between bg-gray-800 rounded-xl px-3 py-2">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="w-2 h-2 rounded-full {{ $c->isClaimed() ? 'bg-green-400' : 'bg-gray-600' }}"
                                        id="claimed-dot-{{ $c->id }}"></span>
                                    <span class="font-medium text-sm">{{ $c->display_name }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-gray-500" id="claimed-label-{{ $c->id }}">
                                        {{ $c->isClaimed() ? __('judge.joined') : __('judge.waiting') }}
                                    </span>
                                    <span class="font-bold text-indigo-300 text-sm" id="score-{{ $c->id }}">
                                        {{ $c->score }}
                                    </span>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>

                {{-- Scoreboard --}}
                <div class="bg-gray-900 rounded-2xl p-5 border border-gray-800">
                    <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-3">
                        {{ __('judge.scoreboard') }}</h2>
                    <ol class="space-y-2" id="scoreboard">
                        @foreach ($competition->contestants->sortByDesc('score')->values() as $i => $c)
                            <li class="flex items-center justify-between">
                                <span class="text-gray-400 text-sm">{{ $i + 1 }}. {{ $c->display_name }}</span>
                                <span class="font-bold text-white"
                                    data-score-id="{{ $c->id }}">{{ $c->score }}</span>
                            </li>
                        @endforeach
                    </ol>
                </div>

            </div>
        </main>
    </div>
@endsection

@push('scripts')
    <script>
        const ROOM_CODE = document.querySelector('meta[name="room-code"]').content;
        const BASE_URL = '/judge/' + ROOM_CODE;
        const TRANS = {!! json_encode([
            'copy' => __('common.copy'),
            'copied' => __('common.copied'),
            'round_number' => __('judge.round_number'),
            'waiting_buzz' => __('judge.waiting_buzz'),
            'joined' => __('judge.joined'),
        ]) !!};
        let currentRound = @json(
            $competition->currentRound
                ? ['id' => $competition->currentRound->id, 'status' => $competition->currentRound->status->value]
                : null);
        let timerInterval = null;

        // ── Helpers ─────────────────────────────────────────────────────────────────
        function log(msg) {
            const li = document.createElement('li');
            li.textContent = new Date().toLocaleTimeString() + ' ' + msg;
            const ul = document.getElementById('event-log');
            ul.prepend(li);
            if (ul.children.length > 30) ul.lastElementChild.remove();
        }

        function setButtonState(roundStatus) {
            const s = roundStatus || 'none';
            document.getElementById('btn-start-round').disabled = (s === 'active' || s === 'locked');
            document.getElementById('btn-reset').disabled = (s === 'none');
            document.getElementById('btn-correct').disabled = (s !== 'locked');
            document.getElementById('btn-wrong').disabled = (s !== 'locked');
            ['btn-start-round', 'btn-reset', 'btn-correct', 'btn-wrong'].forEach(id => {
                const el = document.getElementById(id);
                if (el.disabled) el.classList.add('opacity-50', 'cursor-not-allowed');
                else el.classList.remove('opacity-50', 'cursor-not-allowed');
            });
        }

        function updateScoreboard(scoreboard) {
            scoreboard.forEach(c => {
                const el = document.querySelector('[data-score-id="' + c.id + '"]');
                if (el) el.textContent = c.score;
                const s2 = document.getElementById('score-' + c.id);
                if (s2) s2.textContent = c.score;
            });
        }

        function startAnswerTimer(deadlineIso) {
            clearInterval(timerInterval);
            const deadline = new Date(deadlineIso).getTime();
            const timerEl = document.getElementById('answer-timer');
            const secEl = document.getElementById('timer-seconds');
            timerEl.classList.remove('hidden');
            timerInterval = setInterval(() => {
                const rem = Math.ceil((deadline - Date.now()) / 1000);
                if (rem <= 0) {
                    clearInterval(timerInterval);
                    secEl.textContent = '0';
                    timerEl.classList.add('text-red-400');
                    log('⏰ Answer timer expired');
                } else {
                    secEl.textContent = rem;
                }
            }, 250);
        }

        // ── Actions ──────────────────────────────────────────────────────────────────
        async function startRound() {
            const res = await fetch(BASE_URL + '/rounds/start', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            if (res.ok) {
                currentRound = {
                    id: data.round_id,
                    status: data.status
                };
                document.getElementById('round-badge').textContent =
                    TRANS.round_number.replace(':number', data.round_number);
                document.getElementById('waiting-buzz').classList.remove('hidden');
                document.getElementById('first-buzzer-area').classList.add('hidden');
                document.getElementById('waiting-buzz').querySelector('p').textContent = TRANS.waiting_buzz;
                document.getElementById('answer-timer').classList.add('hidden');
                clearInterval(timerInterval);
                setButtonState('active');
                log('▶ Round ' + data.round_number + ' started');
            }
        }

        async function resetRound() {
            if (!currentRound) return;
            const res = await fetch(BASE_URL + '/rounds/reset', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });
            if (res.ok) {
                document.getElementById('waiting-buzz').classList.remove('hidden');
                document.getElementById('first-buzzer-area').classList.add('hidden');
                document.getElementById('answer-timer').classList.add('hidden');
                clearInterval(timerInterval);
                setButtonState('active');
                log('↺ Buzzers reset');
            }
        }

        async function markAnswer(result) {
            if (!currentRound) return;
            const res = await fetch(BASE_URL + '/rounds/' + currentRound.id + '/answer', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    result
                }),
            });
            if (res.ok) {
                clearInterval(timerInterval);
                document.getElementById('answer-timer').classList.add('hidden');
                if (result === 'correct') {
                    document.getElementById('first-buzzer-area').classList.add('hidden');
                    document.getElementById('waiting-buzz').classList.remove('hidden');
                    document.getElementById('waiting-buzz').querySelector('p').textContent =
                        '✅ Correct! Start next round.';
                    setButtonState('completed');
                    log('✓ Correct answer marked');
                } else {
                    log('✗ Wrong answer — buzzers re-opened for others');
                    setButtonState('active');
                }
            }
        }

        function copyJoinLink() {
            navigator.clipboard.writeText(document.getElementById('join-url').value);
            document.querySelector('button[onclick="copyJoinLink()"]').textContent = TRANS.copied;
            setTimeout(() => document.querySelector('button[onclick="copyJoinLink()"]').textContent = TRANS.copy, 2000);
        }

        // ── Realtime via Echo ────────────────────────────────────────────────────────
        if (window.Echo) {
            window.Echo.channel('competition.' + ROOM_CODE)

                .listen('.RoundStarted', e => {
                    currentRound = {
                        id: e.round_id,
                        status: 'active'
                    };
                    log('▶ Round ' + e.round_number + ' started (realtime)');
                })

                .listen('.BuzzAccepted', e => {
                    document.getElementById('first-buzzer-name').textContent = e.contestant_name;
                    document.getElementById('first-buzzer-area').classList.remove('hidden');
                    document.getElementById('waiting-buzz').classList.add('hidden');
                    if (currentRound) currentRound.status = 'locked';
                    setButtonState('locked');
                    if (e.answer_deadline_at) startAnswerTimer(e.answer_deadline_at);
                    log('🔔 ' + e.contestant_name + ' buzzed first!');
                })

                .listen('.RoundReset', e => {
                    document.getElementById('first-buzzer-area').classList.add('hidden');
                    document.getElementById('waiting-buzz').classList.remove('hidden');
                    if (currentRound) currentRound.status = 'active';
                    clearInterval(timerInterval);
                    document.getElementById('answer-timer').classList.add('hidden');
                    setButtonState('active');
                    log('↺ Round reset (realtime)');
                })

                .listen('.ScoreUpdated', e => {
                    updateScoreboard(e.scoreboard);
                    log('📊 Scoreboard updated');
                })

                .listen('.ContestantClaimed', e => {
                    const dot = document.getElementById('claimed-dot-' + e.contestant_id);
                    const lbl = document.getElementById('claimed-label-' + e.contestant_id);
                    if (dot) dot.className = dot.className.replace('bg-gray-600', 'bg-green-400');
                    if (lbl) lbl.textContent = TRANS.joined;
                    log('👤 ' + e.contestant_name + ' joined');
                })

                .listen('.RoundCompleted', e => {
                    if (currentRound) currentRound.status = 'completed';
                    clearInterval(timerInterval);
                    document.getElementById('answer-timer').classList.add('hidden');
                    setButtonState('completed');
                })

                .listen('.CompetitionEnded', () => {
                    window.location.href = '/results/' + ROOM_CODE;
                });
        }

        // Init button states
        setButtonState(currentRound?.status || 'none');
    </script>
@endpush
