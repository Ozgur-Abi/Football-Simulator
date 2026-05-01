<template>
  <div class="bg-gray-900 rounded-xl p-4">
    <h2 class="text-lg font-bold mb-3 text-orange-400">Championship Predictions</h2>

    <!-- Loading skeleton -->
    <div v-if="loading" class="space-y-3">
      <div v-for="n in 6" :key="n" class="mb-3">
        <div class="flex justify-between text-sm mb-1">
          <div class="h-4 bg-gray-700 rounded w-28 animate-pulse"></div>
          <div class="h-4 bg-gray-700 rounded w-10 animate-pulse"></div>
        </div>
        <div class="h-2 bg-gray-700 rounded-full overflow-hidden">
          <div class="h-full bg-gray-600 rounded-full animate-pulse" style="width: 60%"></div>
        </div>
      </div>
    </div>

    <!-- Actual predictions -->
    <div v-else>
      <div v-for="(pct, teamId) in predictions" :key="teamId" class="mb-3">
        <div class="flex justify-between text-sm mb-1">
          <span class="font-medium">{{ teamName(teamId) }}</span>
          <span class="font-bold" :class="pct === 100 ? 'text-yellow-400' : 'text-orange-300'">{{ pct }}%</span>
        </div>
        <div class="h-2 bg-gray-700 rounded-full overflow-hidden">
          <div
            class="h-full rounded-full transition-all duration-700"
            :class="pct === 100 ? 'bg-yellow-400' : 'bg-orange-500'"
            :style="{ width: pct + '%' }"
          ></div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  predictions: { type: Object, required: true },
  standings:   { type: Array,  required: true },
  loading:     { type: Boolean, default: false },
});

function teamName(id) {
  return props.standings.find(r => r.team_id == id)?.team_name ?? id;
}
</script>
