<?php

namespace Tests\Feature;

use App\Enums\CompetitionStatus;
use App\Enums\RoundStatus;
use App\Events\CompetitionEnded;
use App\Events\RoundStarted;
use App\Events\ScoreUpdated;
use App\Models\Competition;
use App\Services\CompetitionService;
use App\Services\RoundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CompetitionFlowTest extends TestCase
{
    use RefreshDatabase;

    private function makeJudge(Competition $competition): void
    {
        session(['judge_token_' . $competition->room_code => $competition->judge_token]);
    }

    public function test_judge_setup_page_loads(): void
    {
        $this->get(route('home'))->assertStatus(200)->assertSee('Contest Bells');
    }

    public function test_judge_can_create_competition(): void
    {
        $res = $this->post(route('competition.store'), [
            'title'            => 'Science Quiz',
            'contestant_count' => 2,
            'names'            => ['Alice', 'Bob'],
        ]);

        $res->assertRedirect();

        $competition = Competition::first();
        $this->assertNotNull($competition);
        $this->assertEquals('Science Quiz', $competition->title);
        $this->assertEquals(CompetitionStatus::Active, $competition->status);
        $this->assertCount(2, $competition->contestants);
    }

    public function test_competition_requires_unique_names(): void
    {
        $this->post(route('competition.store'), [
            'title'            => 'Quiz',
            'contestant_count' => 2,
            'names'            => ['Alice', 'Alice'],
        ])->assertSessionHasErrors();
    }

    public function test_judge_dashboard_loads(): void
    {
        $competition = app(CompetitionService::class)->createCompetition('Test', ['A', 'B']);
        app(CompetitionService::class)->startCompetition($competition);
        $this->makeJudge($competition);

        $this->get(route('judge.dashboard', $competition->room_code))
            ->assertStatus(200)
            ->assertSee($competition->title);
    }

    public function test_judge_can_start_round(): void
    {
        $competition = app(CompetitionService::class)->createCompetition('Test', ['A', 'B']);
        app(CompetitionService::class)->startCompetition($competition);
        $this->makeJudge($competition);

        $res = $this->postJson(route('judge.rounds.start', $competition->room_code));
        $res->assertOk()->assertJsonStructure(['round_id', 'round_number', 'status']);

        Event::assertDispatched(RoundStarted::class);

        $competition->refresh();
        $this->assertNotNull($competition->current_round_id);
        $this->assertEquals(RoundStatus::Active, $competition->currentRound->status);
    }

    public function test_marking_correct_awards_point_and_broadcasts_score(): void
    {
        $competition = app(CompetitionService::class)->createCompetition('Test', ['A', 'B']);
        app(CompetitionService::class)->startCompetition($competition);
        $this->makeJudge($competition);

        $round = app(RoundService::class)->startRound($competition);
        $contestant = $competition->contestants->first();

        $round->update([
            'status'                   => RoundStatus::Locked,
            'first_buzz_contestant_id' => $contestant->id,
            'first_buzzed_at'          => now(),
            'answer_deadline_at'       => now()->addSeconds(10),
        ]);
        $round->refresh();

        $res = $this->postJson(
            route('judge.rounds.answer', [$competition->room_code, $round]),
            ['result' => 'correct']
        );
        $res->assertOk();

        $contestant->refresh();
        $this->assertEquals(1, $contestant->score);
        Event::assertDispatched(ScoreUpdated::class);
    }

    public function test_judge_can_end_competition(): void
    {
        $competition = app(CompetitionService::class)->createCompetition('Test', ['A', 'B']);
        app(CompetitionService::class)->startCompetition($competition);
        $this->makeJudge($competition);

        $this->post(route('judge.end', $competition->room_code))
            ->assertRedirect(route('competition.results', $competition->room_code));

        $competition->refresh();
        $this->assertEquals(CompetitionStatus::Ended, $competition->status);
        Event::assertDispatched(CompetitionEnded::class);
    }

    public function test_non_judge_cannot_start_round(): void
    {
        $competition = app(CompetitionService::class)->createCompetition('Test', ['A', 'B']);
        app(CompetitionService::class)->startCompetition($competition);

        $this->postJson(route('judge.rounds.start', $competition->room_code))
            ->assertForbidden();
    }

    public function test_results_page_shows_ranking(): void
    {
        $competition = app(CompetitionService::class)->createCompetition('Test', ['Alice', 'Bob']);
        app(CompetitionService::class)->startCompetition($competition);
        $competition->contestants->first()->update(['score' => 3]);
        $competition->contestants->last()->update(['score' => 1]);
        $competition->update(['status' => 'ended']);

        $this->get(route('competition.results', $competition->room_code))
            ->assertStatus(200)
            ->assertSee('Alice')
            ->assertSee('Bob');
    }
}
