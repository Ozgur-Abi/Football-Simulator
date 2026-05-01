<template>
  <div class="bg-gray-900 rounded-xl p-4">
    <h2 class="text-lg font-bold mb-3 text-orange-400">League Table</h2>
    <table class="w-full text-sm">
      <thead>
        <tr class="text-gray-400 border-b border-gray-700">
          <th class="text-left py-1 pr-2">#</th>
          <th class="text-left py-1">Team</th>
          <th class="py-1 text-center">P</th>
          <th class="py-1 text-center">W</th>
          <th class="py-1 text-center">D</th>
          <th class="py-1 text-center">L</th>
          <th class="py-1 text-center">GD</th>
          <th class="py-1 text-center font-bold text-white">PTS</th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="(row, i) in standings"
          :key="row.team_id"
          class="border-b border-gray-800 hover:bg-gray-800 transition-colors"
        >
          <td class="py-2 pr-2 text-gray-500">{{ i + 1 }}</td>
          <td class="py-2 font-medium">{{ row.team_name }}</td>
          <td class="py-2 text-center text-gray-300">{{ row.played }}</td>
          <td class="py-2 text-center text-green-400">{{ row.won }}</td>
          <td class="py-2 text-center text-gray-400">{{ row.drawn }}</td>
          <td class="py-2 text-center text-red-400">{{ row.lost }}</td>
          <td class="py-2 text-center" :class="row.goal_diff >= 0 ? 'text-green-300' : 'text-red-300'">
            {{ row.goal_diff > 0 ? '+' : '' }}{{ row.goal_diff }}
          </td>
          <td class="py-2 text-center font-bold text-orange-300">{{ row.points }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
defineProps({ standings: { type: Array, required: true } });
</script>
