<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FixtureMatch extends Model
{
    protected $fillable = ['week', 'home_team_id', 'away_team_id', 'home_goals', 'away_goals', 'played'];

    protected $casts = ['played' => 'boolean'];

    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }
}
