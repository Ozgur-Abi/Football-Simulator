<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Services\League\LeagueOrchestrator;
use Illuminate\Database\Seeder;

class TeamSeeder extends Seeder
{
    public function run(LeagueOrchestrator $orchestrator): void
    {
        Team::query()->delete();

        Team::insert([
            ['name' => 'Galatasaray',       'power' => 88, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Fenerbahçe',        'power' => 86, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Beşiktaş',          'power' => 78, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Trabzonspor',       'power' => 75, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Başakşehir',        'power' => 72, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Adana Demirspor',   'power' => 69, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $orchestrator->init();
    }
}
