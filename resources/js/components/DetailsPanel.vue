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
import { computed } from 'vue';

const props = defineProps({
  teams: { type: Array, required: true },
});

const sortedTeams = computed(() =>
  [...props.teams].sort((a, b) => b.power - a.power)
);
</script>
