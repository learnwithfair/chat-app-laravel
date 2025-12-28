<template>
  <div class="bg-white border-b border-gray-200 p-4 flex items-center justify-between">
    <div class="flex items-center">
      <button @click="$emit('back')" class="md:hidden mr-3 text-gray-600 hover:text-gray-800">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
      </button>
      
      <img :src="avatar" :alt="name" class="w-10 h-10 rounded-full object-cover" />
      <div class="ml-3">
        <h2 class="text-lg font-semibold text-gray-900">{{ name }}</h2>
        <p class="text-xs text-gray-500">{{ subtitle }}</p>
      </div>
    </div>
    
    <div class="flex items-center space-x-2">
      <button @click="$emit('search')" class="p-2 text-gray-600 hover:bg-gray-100 rounded-full transition-colors" title="Search">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
      </button>
      
      <button @click="$emit('audio-call')" class="p-2 text-gray-600 hover:bg-gray-100 rounded-full transition-colors" title="Audio Call">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
        </svg>
      </button>
      
      <button @click="$emit('video-call')" class="p-2 text-gray-600 hover:bg-gray-100 rounded-full transition-colors" title="Video Call">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
        </svg>
      </button>
      
      <button @click="$emit('toggle-info')" class="p-2 text-gray-600 hover:bg-gray-100 rounded-full transition-colors" title="Conversation Info">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
      </button>
    </div>
  </div>
</template>

<script setup>
defineProps(['name', 'subtitle', 'avatar']);
defineEmits(['back', 'search', 'audio-call', 'video-call', 'toggle-info']);
</script>