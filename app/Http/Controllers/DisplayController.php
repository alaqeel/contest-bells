<?php

namespace App\Http\Controllers;

use App\Models\Competition;
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
}
