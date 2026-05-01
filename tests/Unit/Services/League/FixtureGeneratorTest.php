<?php

namespace Tests\Unit\Services\League;

use App\Models\Team;
use App\Services\League\FixtureGenerator;
use Illuminate\Support\Collection;
use Tests\TestCase;

class FixtureGeneratorTest extends TestCase
{
    private FixtureGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = new FixtureGenerator();
    }

    private function makeTeams(int $count = 6): Collection
    {
        $ids = range(1, $count);
        return collect($ids)->map(fn ($i) =>
            tap(new Team(), fn ($m) => $m->forceFill(['id' => $i, 'name' => "Team$i", 'power' => 80]))
        );
    }

    public function test_generates_correct_match_count(): void
    {
        // n teams → n*(n-1) matches (double round-robin)
        $matches = $this->generator->generate($this->makeTeams(6));
        $this->assertCount(30, $matches); // 6*5 = 30
    }

    public function test_generates_correct_week_count(): void
    {
        $matches = $this->generator->generate($this->makeTeams(6));
        $weeks   = array_unique(array_column($matches, 'week'));
        $this->assertCount(10, $weeks); // 6 teams → 10 weeks (2*(6-1))
    }

    public function test_each_week_has_correct_match_count(): void
    {
        // n teams → n/2 matches per week
        $matches = $this->generator->generate($this->makeTeams(6));
        $byWeek  = collect($matches)->groupBy('week');
        foreach ($byWeek as $week => $weekMatches) {
            $this->assertCount(3, $weekMatches, "Week $week should have 3 matches");
        }
    }

    public function test_no_team_plays_itself(): void
    {
        $matches = $this->generator->generate($this->makeTeams(6));
        foreach ($matches as $m) {
            $this->assertNotEquals($m['home_team_id'], $m['away_team_id']);
        }
    }

    public function test_each_pair_plays_home_and_away(): void
    {
        $matches = $this->generator->generate($this->makeTeams(6));
        $pairs   = array_map(fn ($m) => $m['home_team_id'].'-'.$m['away_team_id'], $matches);

        // 6 teams × 5 opponents × 2 legs = 30 unique pair strings
        $this->assertCount(30, array_unique($pairs));
    }
}
