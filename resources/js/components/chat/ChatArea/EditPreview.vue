<script setup>
defineProps(["message"]);
defineEmits(["cancel"]);
</script>

<template>
  <div
    class="bg-yellow-50 border-t border-yellow-200 p-3 flex items-center justify-between"
  >
    <div class="flex items-center space-x-3 flex-1 min-w-0">
      <svg
        class="w-5 h-5 text-yellow-600"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
        />
      </svg>
      <div class="flex-1 min-w-0">
        <p class="text-xs font-semibold text-yellow-700">Editing message</p>
        <p class="text-sm text-gray-700 truncate">{{ message.text }}</p>
      </div>
    </div>
    <button @click="$emit('cancel')" class="text-gray-500 hover:text-gray-700">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M6 18L18 6M6 6l12 12"
        />
      </svg>
    </button>
  </div>
</template>

<style scoped>
button {
  cursor: pointer;
}
</style>
