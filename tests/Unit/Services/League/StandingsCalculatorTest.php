<?php

namespace Tests\Unit\Services\League;

use App\Models\FixtureMatch;
use App\Models\Team;
use App\Services\League\StandingsCalculator;
use Illuminate\Support\Collection;
use Tests\TestCase;

class StandingsCalculatorTest extends TestCase
{
    private StandingsCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new StandingsCalculator();
    }

    private function team(int $id, string $name): Team
    {
        return tap(new Team(), fn ($m) => $m->forceFill(['id' => $id, 'name' => $name, 'power' => 80]));
    }

    private function match(int $h, int $a, int $hg, int $ag): FixtureMatch
    {
        return tap(new FixtureMatch(), fn ($m) => $m->forceFill([
            'id'           => rand(1, 9999),
            'week'         => 1,
            'home_team_id' => $h,
            'away_team_id' => $a,
            'home_goals'   => $hg,
            'away_goals'   => $ag,
            'played'       => true,
        ]));
    }

    public function test_win_awards_3_points(): void
    {
        $teams   = collect([$this->team(1, 'A'), $this->team(2, 'B')]);
        $matches = collect([$this->match(1, 2, 2, 0)]);

        $rows = $this->calculator->calculate($teams, $matches);
        $a    = $rows->firstWhere('teamId', 1);

        $this->assertEquals(3, $a->points);
        $this->assertEquals(1, $a->won);
    }

    public function test_draw_awards_1_point_each(): void
    {
        $teams   = collect([$this->team(1, 'A'), $this->team(2, 'B')]);
        $matches = collect([$this->match(1, 2, 1, 1)]);

        $rows = $this->calculator->calculate($teams, $matches);
        $this->assertEquals(1, $rows->firstWhere('teamId', 1)->points);
        $this->assertEquals(1, $rows->firstWhere('teamId', 2)->points);
    }

    public function test_sorted_by_points_desc(): void
    {
        $teams   = collect([$this->team(1, 'A'), $this->team(2, 'B'), $this->team(3, 'C')]);
        $matches = collect([
            $this->match(1, 2, 1, 0), // A wins
            $this->match(1, 3, 1, 0), // A wins
        ]);

        $rows = $this->calculator->calculate($teams, $matches);
        $this->assertEquals(1, $rows->first()->teamId); // A is top
    }

    public function test_goal_difference_is_tiebreaker(): void
    {
        $teams   = collect([$this->team(1, 'A'), $this->team(2, 'B'), $this->team(3, 'C')]);
        $matches = collect([
            $this->match(1, 3, 3, 0), // A +3
            $this->match(2, 3, 1, 0), // B +1
            $this->match(1, 2, 0, 0), // draw
        ]);

        $rows = $this->calculator->calculate($teams, $matches);
        // A and B both have 4 pts, but A has better GD
        $this->assertEquals(1, $rows->first()->teamId);
    }
}
