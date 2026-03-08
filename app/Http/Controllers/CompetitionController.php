<?php

namespace App\Http\Controllers;

use App\Http\Requests\Judge\CreateCompetitionRequest;
use App\Services\CompetitionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CompetitionController extends Controller
{
    public function __construct(
        private readonly CompetitionService $competitionService,
    ) {}

    /** Judge setup/home page */
    public function create(): View
    {
        return view('judge.setup');
    }

    /** Create competition and redirect judge to dashboard */
    public function store(CreateCompetitionRequest $request): RedirectResponse
    {
        $names = collect($request->input('names', []))
            ->map(fn($n) => trim($n))
            ->filter()
            ->values()
            ->all();

        $competition = $this->competitionService->createCompetition(
            $request->input('title', 'Quiz Competition'),
            $names
        );

        $this->competitionService->startCompetition($competition);

        // Store judge token in session so they can revisit the dashboard
        session(['judge_token_' . $competition->room_code => $competition->judge_token]);

        return redirect()->route('judge.dashboard', $competition->room_code)
            ->with('success', 'Competition created! Share the join link with contestants.');
    }
}
