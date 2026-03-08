<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CompetitionStatus;
use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Services\AdminReportService;
use App\Services\CompetitionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompetitionController extends Controller
{
    public function __construct(
        private readonly AdminReportService $report,
        private readonly CompetitionService $competitionService,
    ) {}

    /**
     * Paginated, searchable index of all competitions.
     */
    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'status', 'from', 'to']);

        $competitions = $this->report->filteredCompetitions($filters);

        return view('admin.competitions.index', compact('competitions', 'filters'));
    }

    /**
     * Full detail view for a single competition.
     */
    public function show(int $id): View
    {
        $competition = $this->report->competitionDetail($id);

        return view('admin.competitions.show', compact('competition'));
    }

    /**
     * Force-end a competition from the admin panel.
     * Only active competitions can be ended; setup ones are rejected.
     */
    public function end(int $id): RedirectResponse
    {
        $competition = Competition::findOrFail($id);

        if ($competition->status === CompetitionStatus::Ended) {
            return back()->with('error', __('admin.competition.already_ended'));
        }

        if ($competition->status === CompetitionStatus::Setup) {
            return back()->with('error', __('admin.competition.cannot_end'));
        }

        $this->competitionService->endCompetition($competition);

        return back()->with('success', __('admin.competition.ended_success'));
    }
}
