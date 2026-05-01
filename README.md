# Insider Champions League

A football league simulator built for the Insider One interview project.

## Stack

| Layer | Tech |
|-------|------|
| Backend | PHP 8.4 / Laravel 13 |
| Frontend | Vue 3 (Composition API) + Vite + Tailwind CSS 4 |
| Database | SQLite (zero-config, file-based) |
| Tests | PHPUnit 12 |
| Deploy | Railway |

## Local Setup

```bash
git clone <repo-url> && cd insider-cl

# PHP dependencies
composer install

# JS dependencies
npm install

# Environment
cp .env.example .env
php artisan key:generate

# Database
touch database/database.sqlite
php artisan migrate:fresh --seed

# Start servers (two terminals)
php artisan serve          # http://127.0.0.1:8000
npm run dev                # Vite HMR
```

Then open `http://127.0.0.1:8000`.

## Running Tests

```bash
php artisan test
```

18 tests — all pass.

## Key Design Decisions

### Architecture: Laravel API + Vue 3 SPA
The backend exposes a JSON API (`/api/league/*`). The Vue SPA mounts on a single Blade view. This is the standard pattern for testable Laravel applications — you test JSON endpoints, not rendered HTML.

### OOP Services (`app/Services/League/`)
Each service has one responsibility:

| Class | Responsibility |
|-------|---------------|
| `FixtureGenerator` | Circle-method round-robin schedule (double leg) |
| `MatchSimulator` | Poisson-goal simulation with 10% home advantage |
| `StandingsCalculator` | Pure function over played matches — standings are **derived, never stored** |
| `ChampionshipPredictor` | Monte Carlo (5,000 runs) reusing `MatchSimulator` |
| `LeagueOrchestrator` | Façade the controller calls |

### Match Simulation Formula
```
homeStrength = home.power × 1.10   (10% home boost)
awayStrength = away.power
λ_home = (homeStrength / total) × 3.0
λ_away = (awayStrength / total) × 3.0
goals  = poisson(λ)                 (Knuth algorithm)
```
This matches ~2.8 goals/game (real PL average), produces natural upsets via Poisson tails, and respects power gaps.

### Championship Predictions
Monte Carlo approach: simulate the remaining fixtures 5,000 times, count how often each team finishes first. This reuses the simulator — prediction logic can never drift from match logic. Short-circuits to 100%/0% when a title is mathematically clinched.

### Standings Derivation
Standings are computed fresh from played matches on every request. Editing a match result therefore can't desync the table — there is nothing to desync.

### Vue Component Pattern
Container/presentational split: `App.vue` owns all state and calls the API; `LeagueTable`, `MatchResults`, `PredictionsPanel`, `ControlButtons`, and `EditableScore` are dumb children that receive props and emit events.

## Teams

| Team | Power |
|------|-------|
| Galatasaray | 88 |
| Fenerbahçe | 86 |
| Beşiktaş | 78 |
| Trabzonspor | 75 |

Powers are tunable in `database/seeders/TeamSeeder.php`.

## League Format

- 4 teams, double round-robin → **6 matchdays, 12 matches**
- Premier League scoring: 3 / 1 / 0 (win / draw / loss)
- Tiebreaker: points → goal difference → goals scored
- Championship predictions appear from **week 4** onwards (last 3 weeks)
