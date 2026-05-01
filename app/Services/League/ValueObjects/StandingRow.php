<?php

namespace App\Services\League\ValueObjects;

readonly class StandingRow
{
    public function __construct(
        public int    $teamId,
        public string $teamName,
        public int    $played,
        public int    $won,
        public int    $drawn,
        public int    $lost,
        public int    $goalsFor,
        public int    $goalsAgainst,
        public int    $goalDiff,
        public int    $points,
    ) {}

    public function toArray(): array
    {
        return [
            'team_id'       => $this->teamId,
            'team_name'     => $this->teamName,
            'played'        => $this->played,
            'won'           => $this->won,
            'drawn'         => $this->drawn,
            'lost'          => $this->lost,
            'goals_for'     => $this->goalsFor,
            'goals_against' => $this->goalsAgainst,
            'goal_diff'     => $this->goalDiff,
            'points'        => $this->points,
        ];
    }
}
