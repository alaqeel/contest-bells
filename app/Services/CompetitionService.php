<?php

namespace App\Services;

use App\Enums\CompetitionStatus;
use App\Models\Competition;
use App\Models\Contestant;
use Illuminate\Support\Str;

class CompetitionService
{
    public function createCompetition(string $title, array $contestantNames, string $judgeName = ''): Competition
    {
        $competition = Competition::create([
            'room_code'        => $this->generateRoomCode(),
            'judge_token'      => Str::random(64),
            'title'            => $title,
            'judge_name'       => $judgeName ?: null,
            'status'           => CompetitionStatus::Setup,
            'contestant_count' => count($contestantNames),
        ]);

        foreach ($contestantNames as $name) {
            Contestant::create([
                'competition_id' => $competition->id,
                'display_name'   => trim($name),
                'score'          => 0,
            ]);
        }

        return $competition;
    }

    public function startCompetition(Competition $competition): void
    {
        $competition->update([
            'status'     => CompetitionStatus::Active,
            'started_at' => now(),
        ]);
    }

    public function endCompetition(Competition $competition): void
    {
        $competition->update([
            'status'           => CompetitionStatus::Ended,
            'ended_at'         => now(),
            'current_round_id' => null,
        ]);
    }

    public function getScoreboard(Competition $competition): array
    {
        return $competition->contestants()
            ->orderByDesc('score')
            ->get()
            ->map(fn($c) => [
                'id'    => $c->id,
                'name'  => $c->display_name,
                'score' => $c->score,
            ])
            ->toArray();
    }

    private function generateRoomCode(): string
    {
        do {
            $code = strtoupper(Str::random(6));
        } while (Competition::where('room_code', $code)->exists());

        return $code;
    }
}
