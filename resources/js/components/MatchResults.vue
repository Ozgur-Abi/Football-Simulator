<template>
  <div class="bg-gray-900 rounded-xl p-4">
    <h2 class="text-lg font-bold mb-3 text-orange-400">Match Results</h2>

    <div v-if="Object.keys(matches).length === 0" class="text-gray-500 text-sm">
      No matches played yet. Click <em>Next Week</em> to start.
    </div>

    <div v-for="(weekMatches, week) in matches" :key="week" class="mb-4">
      <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Week {{ week }}</h3>
      <div v-for="m in weekMatches" :key="m.id" class="flex items-center justify-between py-1.5 border-b border-gray-800 text-sm">
        <span class="w-28 text-right font-medium">{{ m.home_team }}</span>
        <span class="mx-3 font-bold text-orange-300 min-w-[60px] text-center">
          <EditableScore
            v-if="m.played"
            :match-id="m.id"
            :home-goals="m.home_goals"
            :away-goals="m.away_goals"
            @save="(id, h, a) => $emit('edit-match', id, h, a)"
          />
          <span v-else class="text-gray-600">vs</span>
        </span>
        <span class="w-28 font-medium">{{ m.away_team }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import EditableScore from './EditableScore.vue';

defineProps({ matches: { type: Object, required: true } });
defineEmits(['edit-match']);
</script>
