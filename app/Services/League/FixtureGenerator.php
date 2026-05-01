<?php

namespace App\Services\League;

use App\Models\Team;
use Illuminate\Support\Collection;

/**
 * Generates a double round-robin fixture using the circle method.
 *
 * Even team count: every team plays every week.
 * Odd team count: a ghost team (id 0) is appended; whoever is paired with the
 * ghost gets a bye that week. Pairs involving the ghost are dropped from the
 * output, so for n=odd we get n weeks per leg with (n-1)/2 matches per week.
 */
class FixtureGenerator
{
    private const BYE = 0;

    public function generate(Collection $teams): array
    {
        $ids = $teams->pluck('id')->values()->toArray();

        if (count($ids) % 2 !== 0) {
            $ids[] = self::BYE;
        }

        $count   = count($ids);
        $rounds  = $count - 1;
        $half    = intdiv($count, 2);
        $fixture = [];
        $week    = 1;

        // First leg
        for ($round = 0; $round < $rounds; $round++) {
            $pairs = [];
            for ($i = 0; $i < $half; $i++) {
                $home = $ids[$i];
                $away = $ids[$count - 1 - $i];

                if ($home === self::BYE || $away === self::BYE) {
                    continue;
                }

                $pairs[] = ['week' => $week, 'home_team_id' => $home, 'away_team_id' => $away];
            }
            $fixture[] = $pairs;
            $week++;

            // Rotate all but the first element
            $last  = array_pop($ids);
            array_splice($ids, 1, 0, [$last]);
        }

        // Second leg — swap home/away of each first-leg round
        $firstLegWeeks = $fixture;
        foreach ($firstLegWeeks as $roundPairs) {
            $reversed = [];
            foreach ($roundPairs as $pair) {
                $reversed[] = [
                    'week'         => $week,
                    'home_team_id' => $pair['away_team_id'],
                    'away_team_id' => $pair['home_team_id'],
                ];
            }
            $fixture[] = $reversed;
            $week++;
        }

        // Flatten to a plain list of match data arrays
        return array_merge(...$fixture);
    }
}
