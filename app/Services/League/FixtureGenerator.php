<?php

namespace App\Services\League;

use App\Models\Team;
use Illuminate\Support\Collection;

/**
 * Generates a double round-robin fixture using the circle method.
 * For 4 teams → 6 weeks × 2 matches = 12 matches total.
 */
class FixtureGenerator
{
    public function generate(Collection $teams): array
    {
        $ids     = $teams->pluck('id')->values()->toArray();
        $count   = count($ids);
        $rounds  = $count - 1;
        $half    = $count / 2;
        $fixture = [];
        $week    = 1;

        // First leg
        for ($round = 0; $round < $rounds; $round++) {
            $pairs = [];
            for ($i = 0; $i < $half; $i++) {
                $home = $ids[$i];
                $away = $ids[$count - 1 - $i];
                $pairs[] = ['week' => $week, 'home_team_id' => $home, 'away_team_id' => $away];
            }
            $fixture[] = $pairs;
            $week++;

            // Rotate all but the last element
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
