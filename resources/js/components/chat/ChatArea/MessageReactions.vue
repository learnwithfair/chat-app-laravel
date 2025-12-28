<template>
  <div
    class="flex flex-wrap gap-1 mt-1"
    :class="alignRight ? 'justify-end' : ''"
  >
    <button
      v-for="reaction in reactions"
      :key="reaction.emoji"
      @click.stop="$emit('show-details', reaction)"
      class="flex items-center space-x-1 bg-white border border-gray-200 rounded-full px-2 py-1 text-xs hover:bg-gray-50 transition-colors shadow-sm"
    >
      <span>{{ reaction.emoji }}</span>
      <span class="font-semibold text-gray-700">{{ reaction.count }}</span>
    </button>
  </div>
</template>

<script setup>
defineProps({
  reactions: Array,
  alignRight: Boolean
});
defineEmits(['show-details']);
</script>