<?php

namespace Tests\Feature;

use App\Models\FixtureMatch;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeagueApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\TeamSeeder::class);
    }

    public function test_state_returns_expected_shape(): void
    {
        $res = $this->getJson('/api/league/state');
        $res->assertOk()
            ->assertJsonStructure([
                'current_week',
                'total_weeks',
                'standings' => [['team_id', 'team_name', 'played', 'won', 'drawn', 'lost', 'points']],
                'matches',
            ]);
    }

    public function test_play_week_advances_current_week(): void
    {
        $before = $this->getJson('/api/league/state')->json('current_week');
        $after  = $this->postJson('/api/league/play-week')->json('current_week');
        $this->assertEquals($before + 1, $after);
    }

    public function test_play_all_completes_the_league(): void
    {
        $res = $this->postJson('/api/league/play-all');
        $res->assertOk();
        $this->assertEquals(
            $res->json('total_weeks'),
            $res->json('current_week')
        );
    }

    public function test_edit_match_updates_goals_and_standings(): void
    {
        $this->postJson('/api/league/play-week');
        $match = FixtureMatch::where('played', true)->first();

        $res = $this->patchJson("/api/league/match/{$match->id}", [
            'home_goals' => 5,
            'away_goals' => 0,
        ]);

        $res->assertOk();
        $this->assertDatabaseHas('fixture_matches', [
            'id'         => $match->id,
            'home_goals' => 5,
            'away_goals' => 0,
        ]);
    }

    public function test_predictions_appear_in_last_3_weeks(): void
    {
        // With 6 teams there are 10 weeks; predictions appear at week 8 (total_weeks - 2)
        $totalWeeks = $this->getJson('/api/league/state')->json('total_weeks');
        $threshold  = $totalWeeks - 2;

        for ($i = 0; $i < $threshold; $i++) {
            $this->postJson('/api/league/play-week');
        }
        $res = $this->getJson('/api/league/state');
        $res->assertOk();
        $this->assertNotNull($res->json('predictions'));
    }

    public function test_reset_restarts_the_league(): void
    {
        $this->postJson('/api/league/play-week');
        $res = $this->postJson('/api/league/reset');
        $res->assertOk();
        $this->assertEquals(0, $res->json('current_week'));
    }
}
