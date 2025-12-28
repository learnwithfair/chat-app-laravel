<template>
  <div @click="$emit('close')" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div @click.stop class="bg-white rounded-lg shadow-xl max-w-md w-full">
      <div class="p-4 border-b border-gray-200 flex items-center justify-between">
        <h3 class="text-lg font-semibold">Message Details</h3>
        <button @click="$emit('close')" class="text-gray-500 hover:text-gray-700">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
      
      <div class="p-6" v-if="message">
        <div class="mb-4">
          <p class="text-sm font-semibold text-gray-500 mb-1">Message</p>
          <p class="text-gray-900">{{ message.text }}</p>
        </div>
        
        <div class="mb-4">
          <p class="text-sm font-semibold text-gray-500 mb-1">Sent at</p>
          <p class="text-gray-900">{{ message.time }}</p>
        </div>
        
        <div class="mb-4">
          <p class="text-sm font-semibold text-gray-500 mb-1">Status</p>
          <p class="text-gray-900 capitalize">{{ message.status }}</p>
        </div>
        
        <div v-if="message.isEdited" class="mb-4">
          <p class="text-sm font-semibold text-gray-500 mb-1">Edited</p>
          <p class="text-gray-900">Yes</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps(['message']);
defineEmits(['close']);
</script>