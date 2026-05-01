<?php

use App\Http\Controllers\LeagueController;
use Illuminate\Support\Facades\Route;

Route::prefix('league')->group(function () {
    Route::get('state',          [LeagueController::class, 'state']);
    Route::post('play-week',     [LeagueController::class, 'playWeek']);
    Route::post('play-all',      [LeagueController::class, 'playAll']);
    Route::post('reset',         [LeagueController::class, 'reset']);
    Route::patch('match/{id}',   [LeagueController::class, 'editMatch']);
    Route::post('team',          [LeagueController::class, 'addTeam']);
    Route::delete('team/{id}',   [LeagueController::class, 'removeTeam']);
});
