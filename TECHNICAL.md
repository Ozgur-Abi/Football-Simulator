# Technical Design — Insider Champions League

## Table of Contents
1. [Architecture Overview](#1-architecture-overview)
2. [Domain Services](#2-domain-services)
3. [Match Simulation — Full Formula](#3-match-simulation--full-formula)
4. [Championship Predictions — Monte Carlo](#4-championship-predictions--monte-carlo)
5. [Fixture Generation — Circle Method](#5-fixture-generation--circle-method)
6. [Standings — Derived, Never Stored](#6-standings--derived-never-stored)
7. [API Design](#7-api-design)
8. [Frontend Architecture](#8-frontend-architecture)
9. [Team Powers & Home Advantage](#9-team-powers--home-advantage)

---

## 1. Architecture Overview

```
Browser (Vue 3 SPA)
       │  axios JSON
       ▼
Laravel 13 (PHP 8.4)
  LeagueController
       │
  LeagueOrchestrator  ◄── single façade, owns coordination
       │
  ┌────┴─────────────────────────────┐
  ▼          ▼           ▼           ▼
FixtureGen  MatchSim  StandingsCalc  Predictor
```

The frontend is a single Vue app mounted on one Blade view. Every user action (Next Week, Play All, Edit, Reset) calls one API endpoint and replaces the entire state object — no partial updates, no optimistic UI. This keeps the Vue side simple and fully driven by server truth.

The backend is a plain Laravel JSON API with no auth, sessions, or queues. SQLite is used deliberately: zero infrastructure, reviewer can `git clone && php artisan serve` with no database credentials.

---

## 2. Domain Services

All live under `app/Services/League/`. Each class has one reason to change.

| Class | Input | Output | Side effects |
|-------|-------|--------|--------------|
| `FixtureGenerator` | `Collection<Team>` | `array<array>` of match rows | None |
| `MatchSimulator` | `Team $home, Team $away` | `MatchResult` value object | None |
| `StandingsCalculator` | teams + matches | `Collection<StandingRow>` | None |
| `ChampionshipPredictor` | teams + matches | `array<team_id => float>` | None |
| `LeagueOrchestrator` | — | — | DB reads/writes |

All four service classes are **pure** (no DB access). Only the orchestrator touches the database. This means every service is trivially unit-testable with plain PHP objects — no database, no HTTP, no mocking framework required.

### Value Objects

`MatchResult` and `StandingRow` are `readonly` classes — immutable, no setters, no identity. They cross service boundaries safely and communicate intent clearly.

```php
readonly class MatchResult {
    public function __construct(
        public int $homeGoals,
        public int $awayGoals,
    ) {}
    public function homeWon(): bool  { return $this->homeGoals > $this->awayGoals; }
    public function awayWon(): bool  { return $this->awayGoals > $this->homeGoals; }
    public function isDraw(): bool   { return $this->homeGoals === $this->awayGoals; }
}
```

---

## 3. Match Simulation — Full Formula

### Step 1 — Effective Strengths

```
homeStrength = home.power × HOME_BOOST      (HOME_BOOST = 1.10)
awayStrength = away.power
total        = homeStrength + awayStrength
```

The 10 % home boost is a single tunable constant. It shifts the expected goals ratio without adding complexity.

### Step 2 — Expected Goals (λ)

```
λ_home = (homeStrength / total) × BASE_GOALS    (BASE_GOALS = 3.0)
λ_away = (awayStrength / total) × BASE_GOALS
```

`BASE_GOALS = 3.0` is tuned so that an evenly matched game produces ~3 total goals (≈ real Premier League average of 2.8). The ratio `homeStrength / total` distributes that budget according to relative power.

**Example — Galatasaray (88) hosting Adana Demirspor (69):**
```
homeStrength = 88 × 1.10 = 96.8
awayStrength = 69
total        = 165.8

λ_home = (96.8 / 165.8) × 3.0 = 1.752
λ_away = (69   / 165.8) × 3.0 = 1.248
```

### Step 3 — Poisson Sampling (Knuth Algorithm)

```php
function poisson(float $lambda): int {
    $L = exp(-$lambda);
    $k = 0;  $p = 1.0;
    do { $k++; $p *= mt_rand() / mt_getrandmax(); } while ($p > $L);
    return $k - 1;
}
```

Knuth's method samples from a Poisson distribution using only a uniform RNG. A Poisson variable naturally allows upsets: even if λ_home = 2.5 and λ_away = 0.5, the away side can still score 2 and win. The probability of that outcome is low but non-zero — exactly like real football.

### Why Poisson (not a flat random roll)?

A flat random roll (e.g. "home wins if rand > 0.4") can only produce win/draw/loss. Poisson produces a full scoreline — 2-1, 0-0, 4-2 — which is needed for goal difference tiebreakers and makes the simulation feel realistic.

---

## 4. Championship Predictions — Monte Carlo

### Algorithm

```
1. Compute current standings from played matches (base state).
2. Collect remaining (unplayed) fixtures as plain [home_id, away_id] pairs.
3. Repeat SIMULATIONS = 2,000 times:
   a. Copy base pts/gd/gf arrays.
   b. For each remaining fixture, sample goals with the Poisson formula.
   c. Apply 3/1/0 points and goal-diff delta.
   d. Find the winner by [pts, gd, gf] comparison.
   e. Increment wins[winner].
4. Return wins[team] / SIMULATIONS × 100 for each team.
```

### Why Monte Carlo (not a formula)?

A closed-form prediction requires enumerating every possible outcome combination. With 12 remaining matches that's up to 3^12 = 531,441 branches. Monte Carlo trades exactness for speed: 2,000 samples run in < 50 ms and produce percentages accurate to ±2 %.

More importantly, Monte Carlo **reuses the simulator**. The prediction model uses the exact same Poisson formula as the match engine. If you change HOME_BOOST from 1.10 to 1.15, both match results and predictions update automatically — they can never diverge.

### Short-Circuit Optimisations

**Season over:** if no fixtures remain, return 100 % for the current leader, 0 % for everyone else.

**Mathematically clinched:** if the leader's current points exceed the maximum achievable points of every other team (`current_pts + 3 × remaining_games`), return 100 % / 0 % immediately without running Monte Carlo.

### Performance

The inner simulation loop operates entirely on plain PHP arrays (`$pts`, `$gd`, `$gf`) — no Eloquent models, no Collection methods, no object clones inside the loop. The remaining fixtures are pre-converted to `[$home_id, $away_id]` integer arrays before the loop starts. This gives roughly 10× the throughput of a naïve Eloquent-based approach.

---

## 5. Fixture Generation — Circle Method

The **circle method** generates a balanced round-robin schedule where no team appears twice in the same week.

```
Teams: [A, B, C, D, E, F]  (n=6, n/2=3 matches per week)

Round 1: fix team[0], rotate the rest clockwise each round.
Week 1: A-F, B-E, C-D
Week 2: A-E, F-D, B-C
Week 3: A-D, E-C, F-B
Week 4: A-C, D-B, E-F
Week 5: A-B, C-F, D-E
```

This produces `n-1 = 5` weeks for the first leg. The second leg is generated by swapping home and away for every match, producing another 5 weeks (weeks 6–10).

**Result for 6 teams:** 10 weeks × 3 matches = 30 total matches. Each team plays 10 games (5 home, 5 away).

The algorithm is implemented generically in `FixtureGenerator` — changing the number of teams in the seeder automatically produces the correct schedule.

---

## 6. Standings — Derived, Never Stored

Standings have no table. `StandingsCalculator::calculate()` is a pure function:

```
Input:  Collection<Team>, Collection<FixtureMatch (played=true)>
Output: Collection<StandingRow> sorted by [points DESC, gd DESC, gf DESC]
```

Each call re-aggregates from scratch. This has two significant consequences:

1. **Edit a match result → standings update instantly.** There is no cached standings row to get out of sync.
2. **Unit tests are trivial.** Pass in a hand-crafted array of match results, assert the output. No database, no seeding, no teardown.

The sort key is a zero-padded string comparison:
```php
sprintf('%06d%06d%06d', $points, $goalDiff + 999, $goalsFor)
```
Adding 999 to goal difference shifts the range from [-999, +999] into [0, 1998], making descending string sort correct for negative values.

---

## 7. API Design

All endpoints under `/api/league`. Every mutating action returns the full updated state — the same response as `GET /state`. The Vue frontend replaces its entire state object on every response. No partial updates, no cache invalidation.

| Method | Path | Action |
|--------|------|--------|
| GET | `/api/league/state` | Full snapshot |
| POST | `/api/league/play-week` | Simulate next matchday |
| POST | `/api/league/play-all` | Simulate all remaining weeks |
| POST | `/api/league/reset` | Re-seed league from scratch |
| PATCH | `/api/league/match/{id}` | Edit a played match result |

### State Response Shape

```json
{
  "current_week": 3,
  "total_weeks": 10,
  "standings": [
    { "team_id": 1, "team_name": "Galatasaray", "played": 3,
      "won": 2, "drawn": 1, "lost": 0, "goals_for": 7,
      "goals_against": 3, "goal_diff": 4, "points": 7 }
  ],
  "matches": {
    "1": [ { "id": 1, "home_team": "Galatasaray", "away_team": "Adana Demirspor",
             "home_goals": 2, "away_goals": 1, "played": true } ]
  },
  "predictions": { "1": 42.5, "2": 31.0, "3": 14.2, "4": 7.8, "5": 3.1, "6": 1.4 }
}
```

`predictions` is `null` before week 1 is played, then a `team_id → percentage` map thereafter.

---

## 8. Frontend Architecture

### Container / Presentational Split

`App.vue` is the only stateful component. It owns `state`, `loading`, and `error` refs and calls every API endpoint. All other components are **presentational** — they receive props and emit events, hold no internal state, and can be tested in isolation.

```
App.vue (stateful — owns state, calls API)
├── ControlButtons.vue  (emits: next-week, play-all, reset)
├── LeagueTable.vue     (props: standings)
├── PredictionsPanel.vue (props: predictions, standings, loading)
└── MatchResults.vue    (props: matches; emits: edit-match)
    └── EditableScore.vue (props: homeGoals, awayGoals; emits: save)
```

### Loading State

All four action handlers (Next Week, Play All, Reset, Edit) flow through a single `load(fn)` wrapper:

```js
async function load(fn) {
    loading.value = true;
    error.value   = null;
    try   { state.value = await fn(); }
    catch { error.value = e?.response?.data?.message ?? 'Something went wrong.'; }
    finally { loading.value = false; }
}
```

While `loading` is true:
- Buttons are disabled (prevents double-submit).
- `PredictionsPanel` shows animated skeleton bars instead of stale percentages.

### Inline Score Editing (Extra Credit)

`EditableScore` is a small, self-contained component. It renders as plain text until clicked; on click it reveals two number inputs and ✓ / ✕ buttons. On confirm it emits `save(homeGoals, awayGoals)` to the parent. The parent (`App.vue`) calls `api.editMatch(id, h, a)` which hits `PATCH /api/league/match/{id}`, and returns the full updated state — including recalculated standings.

---

## 9. Team Powers & Home Advantage

### Power Scale

Powers are integers in [1, 100] representing overall squad strength. The values below are loosely based on 2024–25 Süper Lig form:

| Team | Power | λ_home (vs equal opp.) | λ_away (vs equal opp.) |
|------|------:|----------------------:|----------------------:|
| Galatasaray | 88 | 1.65 | 1.35 |
| Fenerbahçe | 86 | 1.64 | 1.36 |
| Beşiktaş | 78 | 1.62 | 1.38 |
| Trabzonspor | 75 | 1.61 | 1.39 |
| Başakşehir | 72 | 1.60 | 1.40 |
| Adana Demirspor | 69 | 1.59 | 1.41 |

*"vs equal opponent" means the opponent also has power 80, so the λ values shown reflect only the home advantage.*

### Head-to-Head Example

**Galatasaray (88) hosting Adana Demirspor (69):**

```
homeStrength = 88 × 1.10 = 96.8
awayStrength = 69.0
total        = 165.8

λ_home = 96.8 / 165.8 × 3.0 = 1.752   → Galatasaray expected goals
λ_away = 69.0 / 165.8 × 3.0 = 1.248   → Adana Demirspor expected goals

P(Galatasaray win) ≈ 51 %
P(Draw)            ≈ 23 %
P(Adana win)       ≈ 26 %
```

Despite the large power gap, Adana has a 26 % win probability — realistic for a top-flight upset.

### Why Turkish Süper Lig Teams?

Insider One is a Turkish company. Using recognisable local teams makes the simulator immediately relatable to reviewers without sacrificing any technical depth.
