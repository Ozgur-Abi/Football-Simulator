<?php

namespace App\Services\League;

use App\Models\FixtureMatch;
use App\Models\Team;
use App\Services\League\ValueObjects\StandingRow;
use Illuminate\Support\Collection;

/**
 * Computes standings purely from played matches — nothing is stored.
 * Sort: points DESC → goal diff DESC → goals for DESC.
 */
class StandingsCalculator
{
    public function calculate(Collection $teams, Collection $matches): Collection
    {
        $rows = $teams->mapWithKeys(fn (Team $t) => [
            $t->id => [
                'team_id'       => $t->id,
                'team_name'     => $t->name,
                'played'        => 0,
                'won'           => 0,
                'drawn'         => 0,
                'lost'          => 0,
                'goals_for'     => 0,
                'goals_against' => 0,
            ],
        ])->toArray();

        foreach ($matches->where('played', true) as $match) {
            $h = $match->home_team_id;
            $a = $match->away_team_id;
            $hg = $match->home_goals;
            $ag = $match->away_goals;

            $rows[$h]['played']++;
            $rows[$a]['played']++;
            $rows[$h]['goals_for']     += $hg;
            $rows[$h]['goals_against'] += $ag;
            $rows[$a]['goals_for']     += $ag;
            $rows[$a]['goals_against'] += $hg;

            if ($hg > $ag) {
                $rows[$h]['won']++;
                $rows[$a]['lost']++;
            } elseif ($hg < $ag) {
                $rows[$a]['won']++;
                $rows[$h]['lost']++;
            } else {
                $rows[$h]['drawn']++;
                $rows[$a]['drawn']++;
            }
        }

        return collect($rows)
            ->map(fn (array $r) => new StandingRow(
                teamId:       $r['team_id'],
                teamName:     $r['team_name'],
                played:       $r['played'],
                won:          $r['won'],
                drawn:        $r['drawn'],
                lost:         $r['lost'],
                goalsFor:     $r['goals_for'],
                goalsAgainst: $r['goals_against'],
                goalDiff:     $r['goals_for'] - $r['goals_against'],
                points:       $r['won'] * 3 + $r['drawn'],
            ))
            ->sortByDesc(fn (StandingRow $row) => sprintf(
                '%06d%06d%06d',
                $row->points,
                $row->goalDiff + 999,
                $row->goalsFor
            ))
            ->values();
    }
}
