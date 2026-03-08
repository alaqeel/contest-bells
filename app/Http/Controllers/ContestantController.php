<?php

namespace App\Http\Controllers;

use App\Events\ContestantClaimed;
use App\Models\Competition;
use App\Models\Contestant;
use App\Services\BuzzService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ContestantController extends Controller
{
    public function __construct(
        private readonly BuzzService $buzzService,
    ) {}

    /** Contestant join page — show name selection list */
    public function join(string $roomCode): View
    {
        $competition = Competition::where('room_code', $roomCode)
            ->with('contestants')
            ->firstOrFail();

        abort_if($competition->isEnded(), 410, 'This competition has ended.');

        return view('contestant.join', compact('competition'));
    }

    /**
     * Claim a contestant name.
     * Atomic: uses a transaction to prevent duplicate claims.
     */
    public function claim(string $roomCode): RedirectResponse|JsonResponse
    {
        $competition = Competition::where('room_code', $roomCode)->firstOrFail();
        abort_if($competition->isEnded(), 410, 'Competition has ended.');

        $contestantId = request()->input('contestant_id');

        $result = DB::transaction(function () use ($competition, $contestantId) {
            /** @var Contestant $contestant */
            $contestant = Contestant::lockForUpdate()
                ->where('id', $contestantId)
                ->where('competition_id', $competition->id)
                ->firstOrFail();

            if ($contestant->isClaimed()) {
                return ['error' => 'This name is already taken. Please choose another.'];
            }

            $token = Str::random(64);
            $contestant->update([
                'claim_token' => $token,
                'claimed_at'  => now(),
                'session_id'  => session()->getId(),
            ]);

            return ['contestant' => $contestant, 'token' => $token];
        });

        if (isset($result['error'])) {
            return back()->withErrors(['contestant_id' => $result['error']]);
        }

        // Store claim token in session
        $contestant = $result['contestant'];
        session(['claim_token_' . $contestant->id => $result['token']]);

        ContestantClaimed::dispatch($competition, $contestant);

        return redirect()->route('contestant.play', [
            'roomCode'     => $roomCode,
            'contestantId' => $contestant->id,
        ]);
    }

    /** Contestant buzzer screen */
    public function play(string $roomCode, int $contestantId): View
    {
        $competition = Competition::where('room_code', $roomCode)
            ->with(['contestants', 'currentRound'])
            ->firstOrFail();

        $contestant = Contestant::where('id', $contestantId)
            ->where('competition_id', $competition->id)
            ->firstOrFail();

        // Verify claim token
        $storedToken = session('claim_token_' . $contestant->id);
        abort_unless(
            $storedToken && $storedToken === $contestant->claim_token,
            403,
            'You have not claimed this contestant slot.'
        );

        return view('contestant.play', compact('competition', 'contestant'));
    }

    /** POST: buzz */
    public function buzz(string $roomCode, int $contestantId): JsonResponse
    {
        $competition = Competition::where('room_code', $roomCode)
            ->with('currentRound')
            ->firstOrFail();

        $contestant = Contestant::where('id', $contestantId)
            ->where('competition_id', $competition->id)
            ->firstOrFail();

        // Verify claim token
        $storedToken = session('claim_token_' . $contestant->id);
        abort_unless(
            $storedToken && $storedToken === $contestant->claim_token,
            403,
            'Unauthorized.'
        );

        $round = $competition->currentRound;
        abort_if(!$round, 422, 'No active round.');
        abort_if($competition->isEnded(), 422, 'Competition has ended.');

        $result = $this->buzzService->handleBuzz($competition, $round, $contestant);

        return response()->json($result, $result['accepted'] ? 200 : 422);
    }

    /** GET: current state for contestant (for reconnect) */
    public function state(string $roomCode, int $contestantId): JsonResponse
    {
        $competition = Competition::where('room_code', $roomCode)
            ->with(['currentRound.firstBuzzContestant', 'currentRound.lockouts'])
            ->firstOrFail();

        $contestant = Contestant::findOrFail($contestantId);
        $round = $competition->currentRound;

        $lockedUntil = null;
        $isLocked    = false;

        if ($round) {
            $lockout = $round->lockouts()
                ->where('contestant_id', $contestantId)
                ->where('locked_until', '>', now())
                ->first();

            if ($lockout) {
                $isLocked    = true;
                $lockedUntil = $lockout->locked_until->toIso8601String();
            }
        }

        return response()->json([
            'competition_status' => $competition->status->value,
            'score'              => $contestant->score,
            'round'              => $round ? [
                'id'                 => $round->id,
                'status'             => $round->status->value,
                'first_buzzer_id'    => $round->first_buzz_contestant_id,
                'first_buzzer_name'  => $round->firstBuzzContestant?->display_name,
                'answer_deadline_at' => $round->answer_deadline_at?->toIso8601String(),
            ] : null,
            'is_locked'   => $isLocked,
            'locked_until'=> $lockedUntil,
        ]);
    }
}
