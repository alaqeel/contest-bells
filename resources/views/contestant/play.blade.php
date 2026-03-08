@extends('layouts.app')
@section('title', $contestant->display_name . ' — Buzzer')

@push('head')
    <meta name="room-code" content="{{ $competition->room_code }}">
    <meta name="contestant-id" content="{{ $contestant->id }}">
@endpush

@section('content')
    <div class="min-h-full flex flex-col items-center justify-between px-4 py-8 select-none" id="play-screen">

        {{-- Top bar --}}
        <div class="w-full max-w-sm">
            <div class="flex items-center justify-between mb-1">
                <h1 class="text-xl font-black truncate">{{ $contestant->display_name }}</h1>
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-green-400" id="connection-dot"
                        title="{{ __('contestant.connected') }}"></span>
                    <span class="text-xs text-gray-400" id="connection-label">{{ __('contestant.connected') }}</span>
                </div>
            </div>
            <p class="text-sm text-gray-400">
                {{ __('common.room') }} <span class="font-mono text-indigo-400">{{ $competition->room_code }}</span>
            </p>
        </div>

        {{-- Score --}}
        <div class="text-center">
            <p class="text-xs text-gray-500 uppercase tracking-widest">{{ __('contestant.score') }}</p>
            <p class="text-6xl font-black text-white mt-1" id="score">{{ $contestant->score }}</p>
        </div>

        {{-- Status message --}}
        <div class="text-center text-sm font-semibold text-gray-400 min-h-[2rem]" id="status-msg">
            @if ($competition->isEnded())
                {{ __('contestant.competition_ended') }}
            @elseif (!$competition->currentRound || $competition->currentRound->status->value === 'pending')
                {{ __('contestant.waiting_for_round') }}
            @elseif ($competition->currentRound->status->value === 'active')
                {{ __('contestant.buzz_active') }}
            @elseif ($competition->currentRound->status->value === 'locked')
                @if ($competition->currentRound->first_buzz_contestant_id == $contestant->id)
                    {{ __('contestant.you_buzzed_first') }}
                @else
                    {{ __('contestant.was_first', ['name' => $competition->currentRound->firstBuzzContestant?->display_name]) }}
                @endif
            @endif
        </div>

        {{-- Buzzer button --}}
        <div class="flex-1 flex items-center justify-center w-full max-w-xs">
            <button id="buzz-btn" onclick="buzz()"
                class="w-64 h-64 rounded-full font-black text-4xl shadow-2xl transition-all duration-100 touch-none
                   active:scale-95 active:shadow-inner
                   {{ $competition->isEnded()
                       ? 'bg-gray-700 text-gray-500 cursor-not-allowed'
                       : ($competition->currentRound?->status->value === 'active'
                           ? 'bg-red-600 hover:bg-red-500 text-white shadow-red-900/50 cursor-pointer animate-pulse'
                           : 'bg-gray-700 text-gray-500 cursor-not-allowed') }}"
                {{ $competition->isEnded() || $competition->currentRound?->status->value !== 'active' ? 'disabled' : '' }}>
                {{ __('contestant.buzz') }}
            </button>
        </div>

        {{-- Lockout countdown --}}
        <div id="lockout-area" class="text-center hidden">
            <p class="text-orange-400 font-bold">{{ __('contestant.locked_out', ['seconds' => '']) }}<span
                    id="lockout-countdown">10</span>s</p>
        </div>

        {{-- Bottom spacer --}}
        <div class="h-8"></div>
    </div>

    {{-- Sound placeholder (can swap src for a real sound file) --}}
    <audio id="buzz-sound" src="/sounds/buzz.mp3" preload="auto"></audio>
@endsection

@push('scripts')
    <script>
        const ROOM_CODE = document.querySelector('meta[name="room-code"]').content;
        const CONTESTANT_ID = parseInt(document.querySelector('meta[name="contestant-id"]').content);
        const BASE_URL = '/play/' + ROOM_CODE + '/' + CONTESTANT_ID;
        const TRANS = {!! json_encode([
            'buzz_active' => __('contestant.buzz_active'),
            'you_buzzed_first' => __('contestant.you_buzzed_first'),
            'was_first' => __('contestant.was_first'),
            'waiting_for_round' => __('contestant.waiting_for_round'),
            'locked_out_msg' => __('contestant.locked_out_msg'),
            'locked_out_short' => __('contestant.locked_out_short'),
            'not_accepted' => __('contestant.not_accepted'),
            'too_late' => __('contestant.too_late'),
            'competition_ended' => __('contestant.competition_ended_excl'),
            'round_over' => __('contestant.round_over'),
            'correct_answer' => __('contestant.correct_answer'),
            'buzzers_reset' => __('contestant.buzzers_reset'),
            'connected' => __('contestant.connected'),
            'disconnected' => __('contestant.disconnected'),
            'reconnecting' => __('contestant.reconnecting'),
        ]) !!};

        let buzzerEnabled = {{ $competition->currentRound?->status->value === 'active' ? 'true' : 'false' }};
        let lockoutInterval = null;

        function setBuzzerState(enabled, pulse = false) {
            buzzerEnabled = enabled;
            const btn = document.getElementById('buzz-btn');
            btn.disabled = !enabled;
            btn.className = btn.className
                .replace(/bg-red-600 hover:bg-red-500 text-white shadow-red-900\/50 cursor-pointer animate-pulse/g, '')
                .replace(/bg-gray-700 text-gray-500 cursor-not-allowed/g, '')
                .trim();
            if (enabled) {
                btn.classList.add('bg-red-600', 'hover:bg-red-500', 'text-white', 'shadow-red-900/50', 'cursor-pointer');
                if (pulse) btn.classList.add('animate-pulse');
            } else {
                btn.classList.add('bg-gray-700', 'text-gray-500', 'cursor-not-allowed');
            }
        }

        function setStatus(msg, color = 'text-gray-400') {
            const el = document.getElementById('status-msg');
            el.className = 'text-center text-sm font-semibold min-h-[2rem] ' + color;
            el.textContent = msg;
        }

        function startLockoutCountdown(untilIso) {
            clearInterval(lockoutInterval);
            document.getElementById('lockout-area').classList.remove('hidden');
            setBuzzerState(false);
            setStatus(TRANS.locked_out_msg, 'text-orange-400');

            lockoutInterval = setInterval(() => {
                const rem = Math.ceil((new Date(untilIso).getTime() - Date.now()) / 1000);
                if (rem <= 0) {
                    clearInterval(lockoutInterval);
                    document.getElementById('lockout-area').classList.add('hidden');
                    // The round may already be active again from backend event
                } else {
                    document.getElementById('lockout-countdown').textContent = rem;
                }
            }, 250);
        }

        async function buzz() {
            if (!buzzerEnabled) return;
            setBuzzerState(false); // immediate optimistic UI disable

            // Play sound (ignore errors if file absent)
            try {
                document.getElementById('buzz-sound').play();
            } catch (e) {}

            const res = await fetch(BASE_URL + '/buzz', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
            });
            const data = await res.json();

            if (!res.ok) {
                if (data.reason === 'contestant_locked_out') {
                    setStatus(TRANS.locked_out_short, 'text-orange-400');
                } else {
                    setStatus(TRANS.not_accepted + ' ' + (data.reason || TRANS.too_late), 'text-gray-500');
                    // Re-enable if round is still active (another contestant won)
                }
            }
            // Accepted case is handled via BuzzAccepted broadcast event
        }

        // ── Polling ──────────────────────────────────────────────────────────────
        let lastRoundId = null;
        let lastRoundStatus = null;
        let lastFirstBuzzer = null;
        let pollInterval = null;

        function applyState(data) {
            // Score
            const scoreEl = document.getElementById('score');
            if (scoreEl && data.score !== undefined) scoreEl.textContent = data.score;

            // Competition ended
            if (data.competition_status === 'ended') {
                setBuzzerState(false);
                setStatus(TRANS.competition_ended, 'text-gray-400');
                setTimeout(() => window.location.href = '/results/' + ROOM_CODE, 1500);
                return;
            }

            const round = data.round;
            const roundStatus = round?.status ?? 'none';
            const firstBuzzerId = round?.first_buzzer_id ?? null;

            // Locked out handling
            if (data.is_locked && data.locked_until) {
                startLockoutCountdown(data.locked_until);
            } else if (!data.is_locked && lockoutInterval) {
                clearInterval(lockoutInterval);
                lockoutInterval = null;
                document.getElementById('lockout-area').classList.add('hidden');
            }

            // Round started (new round)
            if (round && round.id !== lastRoundId) {
                lastRoundId = round.id;
                lastFirstBuzzer = null;
                document.getElementById('buzz-btn').classList.remove('ring-4', 'ring-yellow-400');
            }

            // Status transitions
            if (roundStatus !== lastRoundStatus || firstBuzzerId !== lastFirstBuzzer) {
                lastRoundStatus = roundStatus;
                lastFirstBuzzer = firstBuzzerId;

                if (roundStatus === 'active' && !data.is_locked) {
                    setBuzzerState(true, true);
                    setStatus(TRANS.buzz_active, 'text-green-400');
                    document.getElementById('lockout-area').classList.add('hidden');
                } else if (roundStatus === 'locked') {
                    setBuzzerState(false);
                    document.getElementById('buzz-btn').classList.remove('ring-4', 'ring-yellow-400');
                    if (firstBuzzerId === CONTESTANT_ID) {
                        setStatus(TRANS.you_buzzed_first, 'text-yellow-400');
                        document.getElementById('buzz-btn').classList.add('ring-4', 'ring-yellow-400');
                    } else if (round.first_buzzer_name) {
                        setStatus(TRANS.was_first.replace(':name', round.first_buzzer_name), 'text-gray-500');
                    }
                } else if (roundStatus === 'completed') {
                    setBuzzerState(false);
                    setStatus(TRANS.round_over, 'text-gray-400');
                    document.getElementById('buzz-btn').classList.remove('ring-4', 'ring-yellow-400');
                } else if (roundStatus === 'none') {
                    setBuzzerState(false);
                    setStatus(TRANS.waiting_for_round, 'text-gray-400');
                }
            }
        }

        async function pollState() {
            try {
                const res = await fetch(BASE_URL + '/state', {
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                if (res.ok) applyState(await res.json());
            } catch (e) {
                /* ignore network blip */ }
        }

        pollInterval = setInterval(pollState, 800);
    </script>
@endpush
