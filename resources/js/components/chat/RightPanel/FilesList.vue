<template>
  <div class="space-y-2 mb-6">
    <div v-for="i in 5" :key="i" class="flex items-center p-2 hover:bg-gray-50 rounded cursor-pointer">
      <svg class="w-8 h-8 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M8 4a3 3 0 00-3 3v4a5 5 0 0010 0V7a1 1 0 112 0v4a7 7 0 11-14 0V7a5 5 0 0110 0v4a3 3 0 11-6 0V7a1 1 0 012 0v4a1 1 0 102 0V7a3 3 0 00-3-3z" clip-rule="evenodd" />
      </svg>
      <div class="ml-3 flex-1">
        <p class="text-sm font-medium text-gray-900">Document {{ i }}.pdf</p>
        <p class="text-xs text-gray-500">2.5 MB</p>
      </div>
    </div>
  </div>
</template>