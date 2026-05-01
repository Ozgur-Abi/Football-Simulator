<?php

namespace App\Services\League;

use App\Models\FixtureMatch;
use App\Models\Team;
use App\Services\League\ValueObjects\StandingRow;
use Illuminate\Support\Collection;

/**
 * Fast Monte Carlo predictor.
 *
 * Works entirely on plain arrays in the inner loop — no Eloquent clones,
 * no O(n) collection searches. ~10x faster than the naïve version.
 *
 * Algorithm:
 *   1. Compute current standings once (from played matches).
 *   2. For each simulation, start from those standing totals and simulate
 *      only the remaining fixtures, applying 3/1/0 + goal-diff delta.
 *   3. Count championship wins per team, divide by SIMULATIONS.
 */
class ChampionshipPredictor
{
    private const SIMULATIONS  = 2000;
    private const HOME_BOOST   = 1.10;
    private const BASE_GOALS   = 3.0;

    public function __construct(
        private readonly StandingsCalculator $calculator,
    ) {}

    public function predict(Collection $teams, Collection $allMatches): array
    {
        // --- build fast lookup structures ---
        $teamPower = [];
        foreach ($teams as $t) {
            $teamPower[$t->id] = $t->power;
        }

        // Base state from already-played matches
        $playedMatches  = $allMatches->where('played', true)->values();
        $baseRows       = $this->calculator->calculate($teams, $playedMatches)->keyBy('teamId');

        $basePts = [];
        $baseGd  = [];
        $baseGf  = [];
        foreach ($baseRows as $id => $row) {
            $basePts[$id] = $row->points;
            $baseGd[$id]  = $row->goalDiff;
            $baseGf[$id]  = $row->goalsFor;
        }

        // Remaining fixtures as plain arrays (no Eloquent in the inner loop)
        $remaining = $allMatches->where('played', false)->values()
            ->map(fn (FixtureMatch $m) => [$m->home_team_id, $m->away_team_id])
            ->toArray();

        if (empty($remaining)) {
            // Season over — leader is already decided
            $leader = $baseRows->sortByDesc(fn (StandingRow $r) => sprintf(
                '%06d%06d%06d', $r->points, $r->goalDiff + 999, $r->goalsFor
            ))->keys()->first();

            return collect($basePts)->map(fn ($_, $id) => $id === $leader ? 100.0 : 0.0)->toArray();
        }

        // Short-circuit: mathematically clinched
        $maxPossible = array_fill_keys(array_keys($basePts), 0);
        foreach ($remaining as [$h, $a]) {
            $maxPossible[$h] += 3;
            $maxPossible[$a] += 3;
        }
        $leaderPts = max($basePts);
        $leaderIds = array_keys($basePts, $leaderPts);
        if (count($leaderIds) === 1) {
            $leaderId = $leaderIds[0];
            $clinched = true;
            foreach ($basePts as $id => $pts) {
                if ($id !== $leaderId && ($pts + $maxPossible[$id]) >= $leaderPts) {
                    $clinched = false;
                    break;
                }
            }
            if ($clinched) {
                return array_map(fn ($id) => $id === $leaderId ? 100.0 : 0.0, array_combine(
                    array_keys($basePts), array_keys($basePts)
                ));
            }
        }

        // --- Monte Carlo ---
        $wins = array_fill_keys(array_keys($basePts), 0);

        for ($sim = 0; $sim < self::SIMULATIONS; $sim++) {
            $pts = $basePts;
            $gd  = $baseGd;
            $gf  = $baseGf;

            foreach ($remaining as [$h, $a]) {
                $hg = $this->poisson($teamPower[$h] * self::HOME_BOOST / ($teamPower[$h] * self::HOME_BOOST + $teamPower[$a]) * self::BASE_GOALS);
                $ag = $this->poisson($teamPower[$a]                    / ($teamPower[$h] * self::HOME_BOOST + $teamPower[$a]) * self::BASE_GOALS);

                $gf[$h] += $hg; $gd[$h] += $hg - $ag;
                $gf[$a] += $ag; $gd[$a] += $ag - $hg;

                if ($hg > $ag)      { $pts[$h] += 3; }
                elseif ($ag > $hg)  { $pts[$a] += 3; }
                else                { $pts[$h]++; $pts[$a]++; }
            }

            // Determine winner (points → GD → GF)
            $winner = null;
            $best   = [-PHP_INT_MAX, -PHP_INT_MAX, -PHP_INT_MAX];
            foreach ($pts as $id => $p) {
                $score = [$p, $gd[$id], $gf[$id]];
                if ($score > $best) { $best = $score; $winner = $id; }
            }
            $wins[$winner]++;
        }

        return array_map(fn (int $w) => round($w / self::SIMULATIONS * 100, 1), $wins);
    }

    private function poisson(float $lambda): int
    {
        $L = exp(-$lambda);
        $k = 0;
        $p = 1.0;
        do { $k++; $p *= (float) mt_rand() / mt_getrandmax(); } while ($p > $L);
        return $k - 1;
    }
}
