<template>
  <div @click="$emit('close')" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div @click.stop class="bg-white rounded-lg shadow-xl max-w-md w-full max-h-96 overflow-hidden">
      <div class="p-4 border-b border-gray-200 flex items-center justify-between">
        <h3 class="text-lg font-semibold">Reactions</h3>
        <button @click="$emit('close')" class="text-gray-500 hover:text-gray-700">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
      
      <div class="overflow-y-auto max-h-80">
        <div
          v-for="user in users"
          :key="user.id"
          class="flex items-center p-4 hover:bg-gray-50"
        >
          <img :src="user.avatar" :alt="user.name" class="w-10 h-10 rounded-full object-cover" />
          <span class="ml-3 font-medium text-gray-900">{{ user.name }}</span>
          <span class="ml-auto text-2xl">{{ user.reaction }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps(['users']);
defineEmits(['close']);
</script>
