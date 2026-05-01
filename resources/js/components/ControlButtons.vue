<template>
  <div class="flex flex-wrap gap-3">
    <button
      @click="$emit('next-week')"
      :disabled="disabled || leagueOver"
      class="px-5 py-2 rounded-lg font-semibold bg-orange-500 hover:bg-orange-400 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
    >
      Next Week
    </button>
    <button
      @click="$emit('play-all')"
      :disabled="disabled || leagueOver"
      class="px-5 py-2 rounded-lg font-semibold bg-green-600 hover:bg-green-500 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
    >
      Play All
    </button>
    <button
      @click="$emit('reset')"
      :disabled="disabled"
      class="px-5 py-2 rounded-lg font-semibold bg-gray-700 hover:bg-gray-600 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
    >
      Reset
    </button>
  </div>
</template>

<script setup>
defineProps({
  disabled:   { type: Boolean, default: false },
  leagueOver: { type: Boolean, default: false },
});
defineEmits(['next-week', 'play-all', 'reset']);
</script>
