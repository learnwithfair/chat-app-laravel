<script setup>
defineProps(["users"]);
defineEmits(["start-chat", "open-search"]);
</script>

<template>
  <div class="p-4 border-b border-gray-100">
    <div class="flex items-center justify-between mb-2">
      <h3 class="text-xs font-semibold text-gray-500 uppercase">Online Now</h3>
      <button
        @click="$emit('open-search')"
        class="p-1.5 text-blue-500 bg-blue-100 rounded-full transition-colors cursor-pointer hover:bg-blue-200"
        title="Search Friends to Start Chat"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
          />
        </svg>
      </button>
    </div>
    <div class="flex space-x-3 overflow-x-auto pb-2">
      <div
        v-for="user in users"
        :key="user.id"
        @click="$emit('start-chat', user)"
        class="flex flex-col items-center cursor-pointer flex-shrink-0"
      >
        <div class="relative">
          <img
            :src="user.avatar"
            :alt="user.name"
            class="w-12 h-12 rounded-full object-cover border-2 border-green-500"
          />
          <span
            class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 border-2 border-white rounded-full"
          ></span>
        </div>
        <span class="text-xs text-gray-700 mt-1 max-w-[60px] truncate">{{
          user.name
        }}</span>
      </div>
    </div>
  </div>
</template>
