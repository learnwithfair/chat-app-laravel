<script setup>
defineProps(["message"]);
defineEmits(["cancel"]);
</script>
<template>
  <div class="bg-blue-50 border-t border-blue-200 p-3 flex items-center justify-between">
    <div class="flex items-center space-x-3 flex-1 min-w-0">
      <div class="w-1 h-12 bg-blue-500 rounded-full"></div>
      <div class="flex-1 min-w-0">
        <p class="text-xs font-semibold text-blue-700">
          Replying to {{ message.senderName }}
        </p>
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
