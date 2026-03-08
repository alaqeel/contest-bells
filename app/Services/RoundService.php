<?php

namespace App\Services;

use App\Enums\RoundStatus;
use App\Events\RoundCompleted;
use App\Events\RoundReset;
use App\Events\RoundStarted;
use App\Events\ScoreUpdated;
use App\Models\Competition;
use App\Models\Round;
use Illuminate\Support\Facades\DB;

class RoundService
{
    public function __construct(
        private readonly CompetitionService $competitionService,
    ) {}

    /**
     * Create and start a new round for the competition.
     */
    public function startRound(Competition $competition): Round
    {
        $nextNumber = $competition->rounds()->max('round_number') + 1;

        $round = Round::create([
            'competition_id' => $competition->id,
            'round_number'   => $nextNumber,
            'status'         => RoundStatus::Active,
            'buzz_opened_at' => now(),
        ]);

        $competition->update(['current_round_id' => $round->id]);

        // Clear any existing lockouts for the new round (fresh start)
        RoundStarted::dispatch($competition, $round);

        return $round;
    }

    /**
     * Reset current round back to active (clears first buzz, lockouts stay).
     */
    public function resetRound(Competition $competition, Round $round): void
    {
        DB::transaction(function () use ($round) {
            $round->lockForUpdate();
            $round->update([
                'status'                  => RoundStatus::Active,
                'first_buzz_contestant_id' => null,
                'first_buzzed_at'         => null,
                'answer_deadline_at'      => null,
                'buzz_opened_at'          => now(),
            ]);
            // Remove lockouts for this round
            $round->lockouts()->delete();
        });

        $round->refresh();
        RoundReset::dispatch($competition, $round);
    }

    /**
     * Mark the answer correct: award point, complete round, update scoreboard.
     */
    public function markCorrect(Competition $competition, Round $round): void
    {
        $contestant = $round->firstBuzzContestant;
        if ($contestant) {
            $contestant->increment('score');
            $contestant->refresh();
        }

        DB::transaction(function () use ($round) {
            $round->update([
                'status'      => RoundStatus::Completed,
                'resolved_at' => now(),
            ]);
        });

        RoundCompleted::dispatch($competition, $round, true);
        ScoreUpdated::dispatch($competition, $this->competitionService->getScoreboard($competition));
    }

    /**
     * Mark the answer wrong: lock contestant 10 seconds, re-activate buzzers for others.
     */
    public function markWrong(Competition $competition, Round $round): void
    {
        $contestant = $round->firstBuzzContestant;
        $lockedUntil = now()->addSeconds(10);

        DB::transaction(function () use ($round, $contestant, $lockedUntil) {
            // Lock out the wrong-answering contestant
            if ($contestant) {
                \App\Models\ContestantLockout::updateOrCreate(
                    ['contestant_id' => $contestant->id, 'round_id' => $round->id],
                    ['locked_until'  => $lockedUntil]
                );
            }

            // Reset round to active — clear the first buzz winner
            $round->update([
                'status'                  => RoundStatus::Active,
                'first_buzz_contestant_id' => null,
                'first_buzzed_at'         => null,
                'answer_deadline_at'      => null,
                'buzz_opened_at'          => now(),
            ]);
        });

        $round->refresh();

        if ($contestant) {
            \App\Events\ContestantLockedOut::dispatch(
                $competition,
                $round,
                $contestant,
                $lockedUntil->toIso8601String()
            );
        }

        RoundReset::dispatch($competition, $round);
    }
}
