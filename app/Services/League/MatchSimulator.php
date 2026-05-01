<?php

namespace App\Services\League;

use App\Models\Team;
use App\Services\League\ValueObjects\MatchResult;

/**
 * Simulates a match using Poisson-distributed goals.
 *
 * Formula:
 *   homeStrength = home.power * 1.10   (10% home advantage)
 *   awayStrength = away.power
 *   λ_home = (homeStrength / total) * BASE_GOALS
 *   λ_away = (awayStrength / total) * BASE_GOALS
 *   goals = poisson(λ)  — Knuth algorithm, ~2.8 goals/game on average
 */
class MatchSimulator
{
    private const HOME_BOOST   = 1.10;
    private const BASE_GOALS   = 3.0;

    public function simulate(Team $home, Team $away): MatchResult
    {
        $homeStr = $home->power * self::HOME_BOOST;
        $awayStr = $away->power;
        $total   = $homeStr + $awayStr;

        $homeGoals = $this->poisson(($homeStr / $total) * self::BASE_GOALS);
        $awayGoals = $this->poisson(($awayStr / $total) * self::BASE_GOALS);

        return new MatchResult($homeGoals, $awayGoals);
    }

    private function poisson(float $lambda): int
    {
        $L = exp(-$lambda);
        $k = 0;
        $p = 1.0;

        do {
            $k++;
            $p *= (float) mt_rand() / mt_getrandmax();
        } while ($p > $L);

        return $k - 1;
    }
}
