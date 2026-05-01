<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    protected $fillable = ['name', 'power'];

    public function homeMatches(): HasMany
    {
        return $this->hasMany(FixtureMatch::class, 'home_team_id');
    }

    public function awayMatches(): HasMany
    {
        return $this->hasMany(FixtureMatch::class, 'away_team_id');
    }
}
