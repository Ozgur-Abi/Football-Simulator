<template>
  <div class="max-w-6xl mx-auto px-4 py-8">
    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
      <div>
        <h1 class="text-2xl font-bold text-white">🏆 Insider Champions League</h1>
        <p class="text-gray-400 text-sm mt-1">
          Week {{ state?.current_week ?? 0 }} / {{ state?.total_weeks ?? 6 }}
          <span v-if="leagueOver" class="ml-2 text-yellow-400 font-semibold">— Season complete!</span>
        </p>
      </div>
      <ControlButtons
        :disabled="loading"
        :league-over="leagueOver"
        @next-week="onNextWeek"
        @play-all="onPlayAll"
        @reset="onReset"
      />
    </div>

    <!-- Error -->
    <div v-if="error" class="mb-4 p-3 bg-red-900 border border-red-700 rounded-lg text-sm text-red-300">
      {{ error }}
    </div>

    <!-- Loading shimmer -->
    <div v-if="loading && !state" class="text-gray-500 text-center py-20">Loading…</div>

    <!-- Main layout -->
    <div v-else-if="state" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Left: standings + predictions -->
      <div class="space-y-6">
        <LeagueTable :standings="state.standings" />
        <PredictionsPanel
          v-if="state.predictions"
          :predictions="state.predictions"
          :standings="state.standings"
          :loading="loading"
        />
        <DetailsPanel :teams="state.teams" @add-team="onAddTeam" />
      </div>

      <!-- Right: match results -->
      <div class="lg:col-span-2">
        <MatchResults
          :matches="state.matches"
          @edit-match="onEditMatch"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import LeagueTable      from './components/LeagueTable.vue';
import PredictionsPanel from './components/PredictionsPanel.vue';
import DetailsPanel     from './components/DetailsPanel.vue';
import MatchResults     from './components/MatchResults.vue';
import ControlButtons   from './components/ControlButtons.vue';
import * as api         from './api/league.js';

const state   = ref(null);
const loading = ref(false);
const error   = ref(null);

const leagueOver = computed(() =>
  state.value !== null && state.value.current_week >= state.value.total_weeks
);

async function load(fn) {
  loading.value = true;
  error.value   = null;
  try {
    state.value = await fn();
  } catch (e) {
    error.value = e?.response?.data?.message ?? 'Something went wrong.';
  } finally {
    loading.value = false;
  }
}

const onNextWeek  = () => load(api.playWeek);
const onPlayAll   = () => load(api.playAll);
const onReset     = () => load(api.resetLeague);
const onEditMatch = (id, h, a) => load(() => api.editMatch(id, h, a));
const onAddTeam   = (name, power) => {
  if (state.value && state.value.current_week > 0) {
    if (!confirm('Adding a team resets the league. All played matches will be lost. Continue?')) return;
  }
  load(() => api.addTeam(name, power));
};

onMounted(() => load(api.getState));
</script>
