<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Services\League\LeagueOrchestrator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeagueController extends Controller
{
    public function __construct(private readonly LeagueOrchestrator $league) {}

    public function state(): JsonResponse
    {
        return response()->json($this->league->getState());
    }

    public function playWeek(): JsonResponse
    {
        $this->league->playWeek();
        return response()->json($this->league->getState());
    }

    public function playAll(): JsonResponse
    {
        $this->league->playAll();
        return response()->json($this->league->getState());
    }

    public function reset(): JsonResponse
    {
        $this->league->reset();
        return response()->json($this->league->getState());
    }

    public function editMatch(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'home_goals' => 'required|integer|min:0|max:20',
            'away_goals' => 'required|integer|min:0|max:20',
        ]);

        $this->league->editMatch($id, $data['home_goals'], $data['away_goals']);
        return response()->json($this->league->getState());
    }

    public function addTeam(Request $request): JsonResponse
    {
        if (Team::count() >= 12) {
            return response()->json(['message' => 'Maximum of 12 teams reached.'], 422);
        }

        $data = $request->validate([
            'name'  => 'required|string|min:1|max:50|unique:teams,name',
            'power' => 'required|integer|min:1|max:100',
        ]);

        $this->league->addTeam($data['name'], $data['power']);
        return response()->json($this->league->getState());
    }
}
