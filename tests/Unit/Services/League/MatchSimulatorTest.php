<?php

namespace Tests\Unit\Services\League;

use App\Models\Team;
use App\Services\League\MatchSimulator;
use Tests\TestCase;

class MatchSimulatorTest extends TestCase
{
    private MatchSimulator $simulator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->simulator = new MatchSimulator();
    }

    private function team(int $power): Team
    {
        return tap(new Team(), fn ($m) => $m->forceFill(['id' => 1, 'name' => 'T', 'power' => $power]));
    }

    public function test_result_has_non_negative_goals(): void
    {
        $result = $this->simulator->simulate($this->team(80), $this->team(70));
        $this->assertGreaterThanOrEqual(0, $result->homeGoals);
        $this->assertGreaterThanOrEqual(0, $result->awayGoals);
    }

    public function test_stronger_team_wins_more_often(): void
    {
        $strong = $this->team(95);
        $weak   = $this->team(10);
        $strongWins = 0;

        for ($i = 0; $i < 500; $i++) {
            $r = $this->simulator->simulate($strong, $weak);
            if ($r->homeWon()) {
                $strongWins++;
            }
        }

        // With such a power gap the strong team should win >60% (very conservative)
        $this->assertGreaterThan(300, $strongWins);
    }

    public function test_match_result_home_away_helpers(): void
    {
        $r = $this->simulator->simulate($this->team(80), $this->team(80));
        // Exactly one of these must be true
        $this->assertEquals(1, (int)$r->homeWon() + (int)$r->awayWon() + (int)$r->isDraw());
    }
}
