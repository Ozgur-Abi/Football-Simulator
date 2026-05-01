<?php

namespace App\Http\Controllers;

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
}
