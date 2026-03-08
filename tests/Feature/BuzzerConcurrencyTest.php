<?php

namespace Tests\Feature;

use App\Enums\RoundStatus;
use App\Models\BuzzAttempt;
use App\Models\ContestantLockout;
use App\Services\BuzzService;
use App\Services\CompetitionService;
use App\Services\RoundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuzzerConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_buzz_is_accepted(): void
    {
        $competition = app(CompetitionService::class)->createCompetition('Quiz', ['Alice', 'Bob']);
        app(CompetitionService::class)->startCompetition($competition);
        $round   = app(RoundService::class)->startRound($competition);
        $alice   = $competition->contestants->first();

        $result = app(BuzzService::class)->handleBuzz($competition, $round, $alice);

        $this->assertTrue($result['accepted']);
        $this->assertNull($result['reason']);

        $round->refresh();
        $this->assertEquals(RoundStatus::Locked, $round->status);
        $this->assertEquals($alice->id, $round->first_buzz_contestant_id);
        $this->assertNotNull($round->answer_deadline_at);
    }

    public function test_second_buzz_is_rejected(): void
    {
        $competition = app(CompetitionService::class)->createCompetition('Quiz', ['Alice', 'Bob']);
        app(CompetitionService::class)->startCompetition($competition);
        $round = app(RoundService::class)->startRound($competition);
        $alice = $competition->contestants->first();
        $bob   = $competition->contestants->last();

        app(BuzzService::class)->handleBuzz($competition, $round, $alice); // winner
        $result = app(BuzzService::class)->handleBuzz($competition, $round, $bob);  // loser

        $this->assertFalse($result['accepted']);
        $this->assertNotNull($result['reason']);

        $this->assertEquals(1, BuzzAttempt::where('round_id', $round->id)->where('accepted', true)->count());
    }

    public function test_buzz_rejected_when_round_not_active(): void
    {
        $competition = app(CompetitionService::class)->createCompetition('Quiz', ['Alice', 'Bob']);
        app(CompetitionService::class)->startCompetition($competition);
        $round = app(RoundService::class)->startRound($competition);
        $alice = $competition->contestants->first();

        $round->update(['status' => RoundStatus::Completed]);

        $result = app(BuzzService::class)->handleBuzz($competition, $round, $alice);

        $this->assertFalse($result['accepted']);
        $this->assertEquals('round_not_active', $result['reason']);
    }

    public function test_locked_out_contestant_cannot_buzz(): void
    {
        $competition = app(CompetitionService::class)->createCompetition('Quiz', ['Alice', 'Bob']);
        app(CompetitionService::class)->startCompetition($competition);
        $round = app(RoundService::class)->startRound($competition);
        $alice = $competition->contestants->first();
        $bob   = $competition->contestants->last();

        app(BuzzService::class)->handleBuzz($competition, $round, $bob);
        app(RoundService::class)->markWrong($competition, $round->fresh());
        $round->refresh();

        ContestantLockout::updateOrCreate(
            ['contestant_id' => $alice->id, 'round_id' => $round->id],
            ['locked_until'  => now()->addSeconds(10)]
        );

        $result = app(BuzzService::class)->handleBuzz($competition, $round, $alice);

        $this->assertFalse($result['accepted']);
        $this->assertEquals('contestant_locked_out', $result['reason']);
    }

    public function test_all_buzz_attempts_are_logged(): void
    {
        $competition = app(CompetitionService::class)->createCompetition('Quiz', ['Alice', 'Bob']);
        app(CompetitionService::class)->startCompetition($competition);
        $round = app(RoundService::class)->startRound($competition);
        $alice = $competition->contestants->first();
        $bob   = $competition->contestants->last();

        app(BuzzService::class)->handleBuzz($competition, $round, $alice);
        app(BuzzService::class)->handleBuzz($competition, $round, $bob);

        $this->assertEquals(2, BuzzAttempt::where('round_id', $round->id)->count());
    }

    public function test_buzz_via_http_endpoint_requires_claim(): void
    {
        $competition = app(CompetitionService::class)->createCompetition('Quiz', ['Alice', 'Bob']);
        app(CompetitionService::class)->startCompetition($competition);
        $round = app(RoundService::class)->startRound($competition);
        $alice = $competition->contestants->first();

        $this->postJson(route('contestant.buzz', [
            'roomCode'     => $competition->room_code,
            'contestantId' => $alice->id,
        ]))->assertForbidden();
    }
}
