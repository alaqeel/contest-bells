<?php

namespace App\Services;

use App\Enums\RoundStatus;
use App\Models\Competition;
use App\Models\ContestantLockout;
use App\Models\Round;
use Illuminate\Support\Facades\DB;

class RoundService
{
    public function __construct(
        private readonly CompetitionService $competitionService,
    ) {}

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

        return $round;
    }

    public function resetRound(Competition $competition, Round $round): void
    {
        DB::transaction(function () use ($round) {
            $round->lockForUpdate();
            $round->update([
                'status'                   => RoundStatus::Active,
                'first_buzz_contestant_id' => null,
                'first_buzzed_at'          => null,
                'answer_deadline_at'       => null,
                'buzz_opened_at'           => now(),
            ]);
            $round->lockouts()->delete();
        });
    }

    public function markCorrect(Competition $competition, Round $round): void
    {
        $contestant = $round->firstBuzzContestant;
        if ($contestant) {
            $contestant->increment('score');
        }

        DB::transaction(function () use ($round) {
            $round->update([
                'status'      => RoundStatus::Completed,
                'resolved_at' => now(),
            ]);
        });
    }

    public function markWrong(Competition $competition, Round $round): void
    {
        $contestant  = $round->firstBuzzContestant;
        $lockedUntil = now()->addSeconds(10);

        DB::transaction(function () use ($round, $contestant, $lockedUntil) {
            if ($contestant) {
                ContestantLockout::updateOrCreate(
                    ['contestant_id' => $contestant->id, 'round_id' => $round->id],
                    ['locked_until'  => $lockedUntil]
                );
            }

            $round->update([
                'status'                   => RoundStatus::Active,
                'first_buzz_contestant_id' => null,
                'first_buzzed_at'          => null,
                'answer_deadline_at'       => null,
                'buzz_opened_at'           => now(),
            ]);
        });
    }
}
