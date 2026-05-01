<?php

namespace App\Services\League;

use App\Models\FixtureMatch;
use App\Models\Team;

/**
 * Single façade the controller talks to.
 * All read-write coordination lives here; services stay pure.
 */
class LeagueOrchestrator
{
    public function __construct(
        private readonly FixtureGenerator      $fixtureGenerator,
        private readonly MatchSimulator        $simulator,
        private readonly StandingsCalculator   $standingsCalculator,
        private readonly ChampionshipPredictor $predictor,
    ) {}

    public function init(): void
    {
        FixtureMatch::query()->delete();
        $teams   = Team::all();
        $matches = $this->fixtureGenerator->generate($teams);

        foreach ($matches as $m) {
            FixtureMatch::create($m + ['played' => false]);
        }
    }

    public function playWeek(): void
    {
        $nextWeek = FixtureMatch::where('played', false)
            ->orderBy('week')
            ->value('week');

        if ($nextWeek === null) {
            return;
        }

        $teams = Team::all()->keyBy('id');

        FixtureMatch::where('week', $nextWeek)->each(function (FixtureMatch $match) use ($teams) {
            $result = $this->simulator->simulate(
                $teams[$match->home_team_id],
                $teams[$match->away_team_id],
            );
            $match->update([
                'home_goals' => $result->homeGoals,
                'away_goals' => $result->awayGoals,
                'played'     => true,
            ]);
        });
    }

    public function playAll(): void
    {
        while (FixtureMatch::where('played', false)->exists()) {
            $this->playWeek();
        }
    }

    public function editMatch(int $id, int $homeGoals, int $awayGoals): void
    {
        $match = FixtureMatch::findOrFail($id);
        $match->update([
            'home_goals' => $homeGoals,
            'away_goals' => $awayGoals,
            'played'     => true,
        ]);
    }

    public function getState(): array
    {
        $teams   = Team::all();
        $matches = FixtureMatch::with(['homeTeam', 'awayTeam'])->orderBy('week')->get();

        $currentWeek = $matches->where('played', true)->max('week') ?? 0;
        $totalWeeks  = $matches->max('week') ?? 6;
        $standings   = $this->standingsCalculator->calculate($teams, $matches);

        $predictions = null;
        if ($currentWeek > 0 && $currentWeek >= $totalWeeks - 2) {
            $predictions = $this->predictor->predict($teams, $matches);
        }

        $matchesByWeek = $matches->groupBy('week')->map(fn ($weekMatches) =>
            $weekMatches->map(fn (FixtureMatch $m) => [
                'id'           => $m->id,
                'home_team'    => $m->homeTeam->name,
                'away_team'    => $m->awayTeam->name,
                'home_goals'   => $m->home_goals,
                'away_goals'   => $m->away_goals,
                'played'       => $m->played,
            ])->values()
        );

        return [
            'current_week' => $currentWeek,
            'total_weeks'  => $totalWeeks,
            'standings'    => $standings->map(fn ($row) => $row->toArray())->values(),
            'matches'      => $matchesByWeek,
            'predictions'  => $predictions,
        ];
    }

    public function reset(): void
    {
        $this->init();
    }
}
