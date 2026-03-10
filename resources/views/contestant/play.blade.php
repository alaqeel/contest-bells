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

    {{-- Confetti canvas (hidden by default; shown only during confetti animation) --}}
    <canvas id="confetti-canvas"
        style="display:none;position:fixed;inset:0;width:100%;height:100%;pointer-events:none;z-index:50"
        aria-hidden="true"></canvas>
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

        // ── Bell ring (Web Audio API) ────────────────────────────────────────
        function playBell() {
            try {
                const ctx = new(window.AudioContext || window.webkitAudioContext)();
                // First partial: fundamental
                const osc1 = ctx.createOscillator();
                const gain1 = ctx.createGain();
                osc1.connect(gain1);
                gain1.connect(ctx.destination);
                osc1.type = 'sine';
                osc1.frequency.setValueAtTime(880, ctx.currentTime);
                osc1.frequency.exponentialRampToValueAtTime(660, ctx.currentTime + 0.4);
                gain1.gain.setValueAtTime(0.7, ctx.currentTime);
                gain1.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 1.8);
                osc1.start(ctx.currentTime);
                osc1.stop(ctx.currentTime + 1.8);
                // Second partial: overtone adds brightness
                const osc2 = ctx.createOscillator();
                const gain2 = ctx.createGain();
                osc2.connect(gain2);
                gain2.connect(ctx.destination);
                osc2.type = 'sine';
                osc2.frequency.setValueAtTime(1760, ctx.currentTime);
                osc2.frequency.exponentialRampToValueAtTime(1320, ctx.currentTime + 0.3);
                gain2.gain.setValueAtTime(0.3, ctx.currentTime);
                gain2.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 1.2);
                osc2.start(ctx.currentTime);
                osc2.stop(ctx.currentTime + 1.2);
            } catch (e) {
                /* Web Audio not available */
            }
        }

        function playFail() {
            try {
                const ctx = new(window.AudioContext || window.webkitAudioContext)();
                // Descending "wah-wah" trombone-style tones
                const notes = [{
                        freq: 380,
                        t: 0.00,
                        len: 0.45
                    },
                    {
                        freq: 300,
                        t: 0.30,
                        len: 0.45
                    },
                    {
                        freq: 220,
                        t: 0.60,
                        len: 0.70
                    },
                ];
                notes.forEach(({
                    freq,
                    t,
                    len
                }) => {
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.type = 'sawtooth';
                    osc.frequency.setValueAtTime(freq * 1.08, ctx.currentTime + t);
                    osc.frequency.linearRampToValueAtTime(freq, ctx.currentTime + t + len * 0.6);
                    gain.gain.setValueAtTime(0, ctx.currentTime + t);
                    gain.gain.linearRampToValueAtTime(0.28, ctx.currentTime + t + 0.04);
                    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + t + len);
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.start(ctx.currentTime + t);
                    osc.stop(ctx.currentTime + t + len);
                });
            } catch (e) {
                /* Web Audio unavailable */ }
        }

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
                    playBell();
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
                    document.getElementById('buzz-btn').classList.remove('ring-4', 'ring-yellow-400');
                    if (firstBuzzerId === CONTESTANT_ID) {
                        setStatus(TRANS.correct_answer, 'text-green-400');
                        launchConfetti();
                    } else {
                        setStatus(TRANS.round_over, 'text-gray-400');
                        playFail();
                    }
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
                /* ignore network blip */
            }
        }

        pollInterval = setInterval(pollState, 800);

        // ── Confetti ─────────────────────────────────────────────────────────────
        (function() {
            const COLORS = ['#FF595E', '#FFCA3A', '#6A4C93', '#1982C4', '#8AC926', '#FF924C', '#C77DFF', '#06D6A0'];

            function random(min, max) {
                return Math.random() * (max - min) + min;
            }

            function Particle(canvas) {
                this.x = random(0, canvas.width);
                this.y = random(-canvas.height * 0.5, 0);
                this.w = random(8, 18);
                this.h = random(4, 10);
                this.color = COLORS[Math.floor(Math.random() * COLORS.length)];
                this.angle = random(0, Math.PI * 2);
                this.spin = random(-0.15, 0.15);
                this.vx = random(-2, 2);
                this.vy = random(2, 6);
                this.alpha = 1;
            }

            Particle.prototype.update = function() {
                this.x += this.vx;
                this.y += this.vy;
                this.angle += this.spin;
                this.vy += 0.07; // gravity
                this.alpha -= 0.008; // slow fade
            };

            Particle.prototype.draw = function(ctx) {
                ctx.save();
                ctx.globalAlpha = Math.max(this.alpha, 0);
                ctx.translate(this.x, this.y);
                ctx.rotate(this.angle);
                ctx.fillStyle = this.color;
                ctx.fillRect(-this.w / 2, -this.h / 2, this.w, this.h);
                ctx.restore();
            };

            let particles = [];
            let rafId = null;

            function loop(canvas, ctx) {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                particles = particles.filter(p => p.alpha > 0 && p.y < canvas.height + 40);
                particles.forEach(p => {
                    p.update();
                    p.draw(ctx);
                });
                if (particles.length > 0) {
                    rafId = requestAnimationFrame(() => loop(canvas, ctx));
                } else {
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                    canvas.style.display = 'none';
                    rafId = null;
                }
            }

            function playCheer() {
                try {
                    const ctx = new(window.AudioContext || window.webkitAudioContext)();
                    const duration = 2.5;

                    // ── Crowd noise: white-noise buffer filtered to "roar" shape ──
                    const bufLen = ctx.sampleRate * duration;
                    const buffer = ctx.createBuffer(1, bufLen, ctx.sampleRate);
                    const data = buffer.getChannelData(0);
                    for (let i = 0; i < bufLen; i++) data[i] = Math.random() * 2 - 1;

                    const noise = ctx.createBufferSource();
                    noise.buffer = buffer;

                    // Band-pass 400–3000 Hz — gives it a muffled crowd feel
                    const bp = ctx.createBiquadFilter();
                    bp.type = 'bandpass';
                    bp.frequency.value = 1200;
                    bp.Q.value = 0.5;

                    const noiseGain = ctx.createGain();
                    noiseGain.gain.setValueAtTime(0, ctx.currentTime);
                    noiseGain.gain.linearRampToValueAtTime(0.35, ctx.currentTime + 0.15); // fast swell
                    noiseGain.gain.linearRampToValueAtTime(0.25, ctx.currentTime + 0.8);
                    noiseGain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + duration);

                    noise.connect(bp);
                    bp.connect(noiseGain);
                    noiseGain.connect(ctx.destination);
                    noise.start(ctx.currentTime);
                    noise.stop(ctx.currentTime + duration);

                    // ── Fanfare: three ascending major-chord tones ──
                    const fanfare = [{
                            freq: 523.25,
                            t: 0.00,
                            len: 1.8
                        }, // C5
                        {
                            freq: 659.25,
                            t: 0.12,
                            len: 1.6
                        }, // E5
                        {
                            freq: 783.99,
                            t: 0.24,
                            len: 1.4
                        }, // G5
                        {
                            freq: 1046.5,
                            t: 0.36,
                            len: 1.2
                        }, // C6 (octave)
                    ];
                    fanfare.forEach(({
                        freq,
                        t,
                        len
                    }) => {
                        const osc = ctx.createOscillator();
                        const gain = ctx.createGain();
                        osc.type = 'triangle';
                        osc.frequency.value = freq;
                        gain.gain.setValueAtTime(0, ctx.currentTime + t);
                        gain.gain.linearRampToValueAtTime(0.25, ctx.currentTime + t + 0.05);
                        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + t + len);
                        osc.connect(gain);
                        gain.connect(ctx.destination);
                        osc.start(ctx.currentTime + t);
                        osc.stop(ctx.currentTime + t + len);
                    });
                } catch (e) {
                    /* Web Audio unavailable */ }
            }

            window.launchConfetti = function() {
                const canvas = document.getElementById('confetti-canvas');
                canvas.width = window.innerWidth;
                canvas.height = window.innerHeight;
                canvas.style.display = 'block';
                const ctx = canvas.getContext('2d');

                playCheer();

                // Spawn 180 pieces in three bursts
                for (let i = 0; i < 180; i++) {
                    particles.push(new Particle(canvas));
                }

                if (!rafId) loop(canvas, ctx);
            };
        }());
    </script>
@endpush
