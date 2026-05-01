<template>
  <span v-if="!editing" @click="startEdit" class="cursor-pointer hover:text-orange-400 transition-colors" title="Click to edit">
    {{ homeGoals }} – {{ awayGoals }}
  </span>
  <span v-else class="inline-flex items-center gap-1">
    <input
      v-model.number="draftHome"
      type="number" min="0" max="20"
      class="w-10 text-center bg-gray-700 border border-orange-500 rounded text-white text-sm p-0.5"
    />
    <span>–</span>
    <input
      v-model.number="draftAway"
      type="number" min="0" max="20"
      class="w-10 text-center bg-gray-700 border border-orange-500 rounded text-white text-sm p-0.5"
    />
    <button @click="save"   class="text-green-400 hover:text-green-300 text-xs font-bold px-1">✓</button>
    <button @click="cancel" class="text-gray-500 hover:text-gray-300 text-xs px-1">✕</button>
  </span>
</template>

<script setup>
import { ref } from 'vue';

const props = defineProps({
  matchId:   { type: Number, required: true },
  homeGoals: { type: Number, default: 0 },
  awayGoals: { type: Number, default: 0 },
});
const emit = defineEmits(['save']);

const editing   = ref(false);
const draftHome = ref(0);
const draftAway = ref(0);

function startEdit() {
  draftHome.value = props.homeGoals;
  draftAway.value = props.awayGoals;
  editing.value   = true;
}
function save() {
  emit('save', props.matchId, draftHome.value, draftAway.value);
  editing.value = false;
}
function cancel() {
  editing.value = false;
}
</script>
