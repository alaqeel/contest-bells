<?php

namespace App\Http\Controllers;

use App\Models\Competition;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class DisplayController extends Controller
{
    /** Public display/scoreboard screen (for TV/projector) */
    public function show(string $roomCode): View
    {
        $competition = Competition::where('room_code', $roomCode)
            ->with(['contestants', 'currentRound.firstBuzzContestant'])
            ->firstOrFail();

        return view('display.show', compact('competition'));
    }

    /** GET: polling state for public display page */
    public function state(string $roomCode): JsonResponse
    {
        $competition = Competition::where('room_code', $roomCode)
            ->with(['contestants', 'currentRound.firstBuzzContestant'])
            ->firstOrFail();

        $round = $competition->currentRound;

        return response()->json([
            'status'     => $competition->status->value,
            'round'      => $round ? [
                'status'            => $round->status->value,
                'first_buzzer_name' => $round->firstBuzzContestant?->display_name,
            ] : null,
            'scoreboard' => $competition->contestants
                ->sortByDesc('score')
                ->values()
                ->map(fn($c) => [
                    'id'    => $c->id,
                    'name'  => $c->display_name,
                    'score' => $c->score,
                ]),
        ]);
    }
}
