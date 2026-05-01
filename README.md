# Insider Champions League

A football league simulator built for the Insider One interview project.

**Live demo:** https://football-simulator-6rgn.onrender.com

## Stack

| Layer | Tech |
|-------|------|
| Backend | PHP 8.4 / Laravel 13 |
| Frontend | Vue 3 (Composition API) + Vite + Tailwind CSS 4 |
| Database | SQLite |
| Tests | PHPUnit 12 |
| Deploy | Render (Docker, free tier) |

## Local Setup

```bash
# Download the project
git clone https://github.com/Ozgur-Abi/Football-Simulator.git && cd Football-Simulator

# Install PHP packages (like npm install, but for PHP)
composer install

# Install JS packages
npm install

# Create the local config file and generate an encryption key
cp .env.example .env && php artisan key:generate

# Create the SQLite database file (just an empty file)
touch database/database.sqlite

# Run database migrations (create tables) and seed with teams + fixtures
php artisan migrate:fresh --seed

# Start the PHP dev server  →  http://127.0.0.1:8000
php artisan serve

# In a second terminal: start Vite (compiles Vue + CSS with hot reload)
npm run dev
```

Open `http://127.0.0.1:8000`.

## Tests

```bash
php artisan test   # 18 tests, all pass
```

## Teams

| Team | Power | Effective home power | Effective away power |
|------|------:|---------------------:|---------------------:|
| Galatasaray | 88 | 96.8 | 88 |
| Fenerbahçe | 86 | 94.6 | 86 |
| Beşiktaş | 78 | 85.8 | 78 |
| Trabzonspor | 75 | 82.5 | 75 |
| Başakşehir | 72 | 79.2 | 72 |
| Adana Demirspor | 69 | 75.9 | 69 |

Effective home power = `power × 1.10`. Powers are tunable in `TeamSeeder.php`.

## Match Simulation

```
homeStrength = home.power × 1.10
awayStrength = away.power
total        = homeStrength + awayStrength

λ_home = (homeStrength / total) × 3.0   → expected home goals
λ_away = (awayStrength / total) × 3.0   → expected away goals

home_goals = poisson(λ_home)
away_goals = poisson(λ_away)
```

`poisson(λ)` uses Knuth's algorithm. Average match produces ~3 goals.
Upsets occur naturally via Poisson tails.

## Championship Predictions

Monte Carlo: simulate all remaining fixtures **2,000 times**, count how often each team wins.
Reuses the same Poisson simulator — prediction physics can never drift from match physics.
Predictions are shown from **week 1** onwards.
Short-circuits to 100% / 0% when the title is mathematically clinched.

## League Format

6 teams, double round-robin → **10 matchdays, 30 matches** (3 per week).
Scoring: 3 / 1 / 0. Tiebreaker: points → goal difference → goals scored.

## Architecture

- **Laravel JSON API** + **Vue 3 SPA** on a single Blade page.
- Services in `app/Services/League/`: `FixtureGenerator`, `MatchSimulator`, `StandingsCalculator`, `ChampionshipPredictor`, `LeagueOrchestrator`.
- Standings are **derived from matches on every request** — editing a result can never corrupt the table.
- Vue uses a **container / presentational** split: `App.vue` owns state; all other components are stateless.
