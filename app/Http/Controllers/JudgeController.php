<?php

namespace App\Http\Controllers;

use App\Models\Competition;
use App\Models\Round;
use App\Services\CompetitionService;
use App\Services\RoundService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class JudgeController extends Controller
{
    public function __construct(
        private readonly RoundService $roundService,
        private readonly CompetitionService $competitionService,
    ) {}

    /** Judge dashboard */
    public function dashboard(string $roomCode): View
    {
        $competition = Competition::where('room_code', $roomCode)->firstOrFail();
        $competition->load(['contestants', 'currentRound.lockouts', 'rounds']);

        return view('judge.dashboard', compact('competition'));
    }

    /** POST: start a new round */
    public function startRound(string $roomCode): JsonResponse
    {
        $competition = Competition::where('room_code', $roomCode)->firstOrFail();
        $this->abortIfNotJudge($competition);
        $this->abortIfEnded($competition);

        $round = $this->roundService->startRound($competition);

        return response()->json([
            'round_id'     => $round->id,
            'round_number' => $round->round_number,
            'status'       => $round->status->value,
        ]);
    }

    /** POST: reset current round buzzers */
    public function resetRound(string $roomCode): JsonResponse
    {
        $competition = Competition::where('room_code', $roomCode)
            ->with('currentRound')
            ->firstOrFail();
        $this->abortIfNotJudge($competition);

        $round = $competition->currentRound;
        abort_if(!$round, 422, 'No active round.');

        $this->roundService->resetRound($competition, $round);

        return response()->json(['status' => 'reset']);
    }

    /** POST: mark answer correct or wrong */
    public function markAnswer(string $roomCode, Round $round): JsonResponse
    {
        $competition = Competition::where('room_code', $roomCode)->firstOrFail();
        $this->abortIfNotJudge($competition);

        $result = request()->input('result');
        abort_unless(in_array($result, ['correct', 'wrong']), 422, 'Invalid result.');

        if ($result === 'correct') {
            $this->roundService->markCorrect($competition, $round);
        } else {
            $this->roundService->markWrong($competition, $round);
        }

        return response()->json(['status' => $result]);
    }

    /** POST: end competition */
    public function endCompetition(string $roomCode): RedirectResponse
    {
        $competition = Competition::where('room_code', $roomCode)->firstOrFail();
        $this->abortIfNotJudge($competition);

        $this->competitionService->endCompetition($competition);

        return redirect()->route('competition.results', $roomCode);
    }

    /** GET: fetch state as JSON for polling fallback */
    public function state(string $roomCode): JsonResponse
    {
        $competition = Competition::where('room_code', $roomCode)
            ->with(['contestants', 'currentRound.firstBuzzContestant', 'currentRound.lockouts'])
            ->firstOrFail();

        $round = $competition->currentRound;

        return response()->json([
            'status'       => $competition->status->value,
            'round'        => $round ? [
                'id'            => $round->id,
                'number'        => $round->round_number,
                'status'        => $round->status->value,
                'first_buzzer'  => $round->firstBuzzContestant?->display_name,
                'answer_deadline_at' => $round->answer_deadline_at?->toIso8601String(),
            ] : null,
            'scoreboard'   => $this->competitionService->getScoreboard($competition),
            'contestants'  => $competition->contestants->map(fn($c) => [
                'id'           => $c->id,
                'name'         => $c->display_name,
                'claimed'      => $c->isClaimed(),
                'score'        => $c->score,
                'is_connected' => $c->is_connected,
            ]),
        ]);
    }

    private function abortIfNotJudge(Competition $competition): void
    {
        $sessionToken = session('judge_token_' . $competition->room_code);
        abort_unless(
            $sessionToken && $sessionToken === $competition->judge_token,
            403,
            'Judge access required.'
        );
    }

    private function abortIfEnded(Competition $competition): void
    {
        abort_if($competition->isEnded(), 422, 'Competition has ended.');
    }
}
