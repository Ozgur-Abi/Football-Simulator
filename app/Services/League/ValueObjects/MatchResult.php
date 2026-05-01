<?php

namespace App\Services\League\ValueObjects;

readonly class MatchResult
{
    public function __construct(
        public int $homeGoals,
        public int $awayGoals,
    ) {}

    public function homeWon(): bool
    {
        return $this->homeGoals > $this->awayGoals;
    }

    public function awayWon(): bool
    {
        return $this->awayGoals > $this->homeGoals;
    }

    public function isDraw(): bool
    {
        return $this->homeGoals === $this->awayGoals;
    }
}
