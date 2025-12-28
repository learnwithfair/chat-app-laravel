<template>
  <div class="space-y-2 mb-6">
    <div v-for="i in 3" :key="i" class="p-2 hover:bg-gray-50 rounded">
      <p class="text-sm text-blue-600 hover:underline cursor-pointer">https://example.com/link{{ i }}</p>
      <p class="text-xs text-gray-500 mt-1">Shared 2 days ago</p>
    </div>
  </div>
</template>