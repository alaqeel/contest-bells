<?php

namespace App\Services;

use App\Enums\RoundStatus;
use App\Events\BuzzAccepted;
use App\Models\BuzzAttempt;
use App\Models\Competition;
use App\Models\Contestant;
use App\Models\Round;
use Illuminate\Support\Facades\DB;

class BuzzService
{
    /**
     * Accept or reject a buzz attempt.
     *
     * This is the core atomic method. It uses a DB transaction with row-level
     * locking (lockForUpdate) to ensure that only the first valid buzz wins,
     * even under concurrent requests.
     *
     * @return array{accepted: bool, reason: string|null}
     */
    public function handleBuzz(Competition $competition, Round $round, Contestant $contestant): array
    {
        $attemptedAt = now();
        $accepted    = false;
        $reason      = null;

        DB::transaction(function () use (
            $competition,
            $round,
            $contestant,
            $attemptedAt,
            &$accepted,
            &$reason
        ) {
            // Lock the round row to prevent concurrent winners
            $round = Round::lockForUpdate()->find($round->id);

            // Guard: round must be active
            if ($round->status !== RoundStatus::Active) {
                $reason = 'round_not_active';
                $this->recordAttempt($round, $contestant, $attemptedAt, false, $reason);
                return;
            }

            // Guard: contestant must not be locked out
            if ($contestant->isLockedOutInRound($round)) {
                $reason = 'contestant_locked_out';
                $this->recordAttempt($round, $contestant, $attemptedAt, false, $reason);
                return;
            }

            // Guard: a winner already exists (race condition safety)
            if ($round->first_buzz_contestant_id !== null) {
                $reason = 'winner_already_set';
                $this->recordAttempt($round, $contestant, $attemptedAt, false, $reason);
                return;
            }

            // Accept this buzz — set winner and lock the round
            $deadline = $attemptedAt->copy()->addSeconds(10);
            $round->update([
                'status'                   => RoundStatus::Locked,
                'first_buzz_contestant_id' => $contestant->id,
                'first_buzzed_at'          => $attemptedAt,
                'answer_deadline_at'       => $deadline,
            ]);

            $this->recordAttempt($round, $contestant, $attemptedAt, true, null);
            $accepted = true;
        });

        if ($accepted) {
            $round->refresh();
            BuzzAccepted::dispatch($competition, $round, $contestant);
        }

        return ['accepted' => $accepted, 'reason' => $reason];
    }

    private function recordAttempt(
        Round $round,
        Contestant $contestant,
        \Carbon\Carbon $attemptedAt,
        bool $accepted,
        ?string $reason
    ): void {
        BuzzAttempt::create([
            'round_id'         => $round->id,
            'contestant_id'    => $contestant->id,
            'attempted_at'     => $attemptedAt,
            'accepted'         => $accepted,
            'rejection_reason' => $reason,
        ]);
    }
}
