<template>
  <div class="bg-gray-900 rounded-xl p-4">
    <h2 class="text-lg font-bold mb-3 text-orange-400">Simulation Details</h2>

    <table class="w-full text-sm mb-4">
      <thead>
        <tr class="text-gray-500 border-b border-gray-700">
          <th class="text-left py-1 font-medium">Team</th>
          <th class="text-right py-1 font-medium">Base</th>
          <th class="text-right py-1 font-medium">Home (×1.10)</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="t in sortedTeams" :key="t.id" class="border-b border-gray-800">
          <td class="py-1">{{ t.name }}</td>
          <td class="text-right py-1 font-mono">{{ t.power }}</td>
          <td class="text-right py-1 font-mono text-orange-300">{{ (t.power * 1.10).toFixed(1) }}</td>
        </tr>
      </tbody>
    </table>

    <!-- Add Team -->
    <div v-if="canAddTeam" class="mb-4">
      <button
        v-if="!showForm"
        @click="showForm = true"
        class="w-full py-1.5 text-sm rounded border border-gray-700 text-gray-300 hover:border-orange-500 hover:text-orange-400 transition"
      >
        + Add Team
      </button>

      <form v-else @submit.prevent="submit" class="space-y-2 p-3 bg-gray-800 rounded">
        <input
          v-model="name"
          type="text"
          maxlength="50"
          placeholder="Team name"
          class="w-full px-2 py-1 text-sm bg-gray-900 border border-gray-700 rounded focus:outline-none focus:border-orange-500"
          required
        />
        <div class="flex gap-2">
          <input
            v-model.number="power"
            type="number"
            min="1"
            max="100"
            placeholder="Power 1–100"
            class="flex-1 px-2 py-1 text-sm bg-gray-900 border border-gray-700 rounded focus:outline-none focus:border-orange-500"
            required
          />
          <button
            type="button"
            @click="randomizePower"
            class="px-2 py-1 text-sm bg-gray-700 rounded hover:bg-gray-600"
            title="Random power based on existing teams"
          >🎲</button>
        </div>
        <div class="flex gap-2">
          <button
            type="submit"
            class="flex-1 py-1 text-sm bg-orange-500 text-white rounded hover:bg-orange-600"
          >Add</button>
          <button
            type="button"
            @click="cancel"
            class="flex-1 py-1 text-sm bg-gray-700 rounded hover:bg-gray-600"
          >Cancel</button>
        </div>
      </form>
    </div>

    <div class="text-xs space-y-1 pt-3 border-t border-gray-700">
      <div class="text-gray-500 font-semibold mb-2 uppercase tracking-wide">Assumptions</div>
      <div class="flex justify-between">
        <span class="text-gray-400">Home advantage</span>
        <span class="font-mono text-orange-300">×1.10</span>
      </div>
      <div class="flex justify-between">
        <span class="text-gray-400">Base goals (avg per game)</span>
        <span class="font-mono text-orange-300">3.0</span>
      </div>
      <div class="flex justify-between">
        <span class="text-gray-400">Goal distribution</span>
        <span class="font-mono text-orange-300">Poisson</span>
      </div>
      <div class="flex justify-between">
        <span class="text-gray-400">Monte Carlo runs</span>
        <span class="font-mono text-orange-300">2,000</span>
      </div>
      <div class="flex justify-between">
        <span class="text-gray-400">Tiebreakers</span>
        <span class="font-mono text-orange-300">PTS → GD → GF</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
  teams: { type: Array, required: true },
});

const emit = defineEmits(['add-team']);

const sortedTeams = computed(() =>
  [...props.teams].sort((a, b) => b.power - a.power)
);

const canAddTeam = computed(() => props.teams.length < 12);

const showForm = ref(false);
const name     = ref('');
const power    = ref(null);

function randomizePower() {
  const powers = props.teams.map(t => t.power);
  const min = Math.min(...powers);
  const max = Math.max(...powers);
  power.value = Math.floor(Math.random() * (max - min + 1)) + min;
}

function submit() {
  if (!name.value.trim() || !power.value) return;
  emit('add-team', name.value.trim(), power.value);
  cancel();
}

function cancel() {
  showForm.value = false;
  name.value     = '';
  power.value    = null;
}
</script>
