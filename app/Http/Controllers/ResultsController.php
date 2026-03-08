<?php

namespace App\Http\Controllers;

use App\Models\Competition;
use Illuminate\View\View;

class ResultsController extends Controller
{
    public function show(string $roomCode): View
    {
        $competition = Competition::where('room_code', $roomCode)
            ->with(['contestants' => fn($q) => $q->orderByDesc('score')])
            ->firstOrFail();

        $contestants = $competition->contestants->values();

        return view('results.show', compact('competition', 'contestants'));
    }
}
