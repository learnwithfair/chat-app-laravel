<template>
  <div class="p-4 border-b border-gray-100">
    <h3 class="text-xs font-semibold text-gray-500 uppercase mb-2">Online Now</h3>
    <div class="flex space-x-3 overflow-x-auto pb-2">
      <div
        v-for="user in users"
        :key="user.id"
        @click="$emit('start-chat', user)"
        class="flex flex-col items-center cursor-pointer flex-shrink-0"
      >
        <div class="relative">
          <img :src="user.avatar" :alt="user.name" class="w-12 h-12 rounded-full object-cover border-2 border-green-500" />
          <span class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 border-2 border-white rounded-full"></span>
        </div>
        <span class="text-xs text-gray-700 mt-1 max-w-[60px] truncate">{{ user.name }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps(['users']);
defineEmits(['start-chat']);
</script>