<?php

namespace Tests\Feature;

use App\Events\ContestantClaimed;
use App\Services\CompetitionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ContestantClaimTest extends TestCase
{
    use RefreshDatabase;

    public function test_contestant_join_page_loads(): void
    {
        $competition = app(CompetitionService::class)->createCompetition('Quiz', ['Alice', 'Bob']);
        app(CompetitionService::class)->startCompetition($competition);

        $this->get(route('contestant.join', $competition->room_code))
            ->assertStatus(200)
            ->assertSee('Alice')
            ->assertSee('Bob');
    }

    public function test_contestant_can_claim_name(): void
    {
        $competition = app(CompetitionService::class)->createCompetition('Quiz', ['Alice', 'Bob']);
        app(CompetitionService::class)->startCompetition($competition);
        $alice = $competition->contestants->first();

        $this->post(route('contestant.claim', $competition->room_code), [
            'contestant_id' => $alice->id,
        ])->assertRedirect();

        $alice->refresh();
        $this->assertNotNull($alice->claim_token);
        $this->assertNotNull($alice->claimed_at);

        Event::assertDispatched(ContestantClaimed::class);
    }

    public function test_cannot_claim_same_name_twice(): void
    {
        $competition = app(CompetitionService::class)->createCompetition('Quiz', ['Alice', 'Bob']);
        app(CompetitionService::class)->startCompetition($competition);
        $alice = $competition->contestants->first();

        $this->post(route('contestant.claim', $competition->room_code), [
            'contestant_id' => $alice->id,
        ]);

        $this->flushSession();
        $res = $this->post(route('contestant.claim', $competition->room_code), [
            'contestant_id' => $alice->id,
        ]);

        $res->assertRedirect();
        $res->assertSessionHasErrors();
    }

    public function test_contestant_buzzer_page_requires_claim_token(): void
    {
        $competition = app(CompetitionService::class)->createCompetition('Quiz', ['Alice', 'Bob']);
        app(CompetitionService::class)->startCompetition($competition);
        $alice = $competition->contestants->first();

        $this->get(route('contestant.play', [
            'roomCode'     => $competition->room_code,
            'contestantId' => $alice->id,
        ]))->assertStatus(403);
    }

    public function test_claimed_contestant_can_access_buzzer_page(): void
    {
        $competition = app(CompetitionService::class)->createCompetition('Quiz', ['Alice', 'Bob']);
        app(CompetitionService::class)->startCompetition($competition);
        $alice = $competition->contestants->first();

        $this->post(route('contestant.claim', $competition->room_code), [
            'contestant_id' => $alice->id,
        ]);
        $alice->refresh();

        $this->get(route('contestant.play', [
            'roomCode'     => $competition->room_code,
            'contestantId' => $alice->id,
        ]))->assertStatus(200)->assertSee('BUZZ');
    }
}
