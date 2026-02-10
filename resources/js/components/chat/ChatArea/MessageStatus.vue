<script setup>
defineProps({
  status: String,
  isMine: Boolean,
});
</script>
<template>
  <div class="flex items-center space-x-1">
    <!-- Single tick - Sent -->
    <svg
      v-if="status === 'sent'"
      class="w-4 h-4"
      :class="isMine ? 'text-blue-100' : 'text-gray-400'"
      fill="currentColor"
      viewBox="0 0 20 20"
    >
      <path
        fill-rule="evenodd"
        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
        clip-rule="evenodd"
      />
    </svg>

    <!-- Double tick - Delivered -->
    <div v-else-if="status === 'delivered'" class="flex -space-x-1">
      <svg
        class="w-4 h-4"
        :class="isMine ? 'text-blue-100' : 'text-gray-400'"
        fill="currentColor"
        viewBox="0 0 20 20"
      >
        <path
          fill-rule="evenodd"
          d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
          clip-rule="evenodd"
        />
      </svg>
      <svg
        class="w-4 h-4"
        :class="isMine ? 'text-blue-100' : 'text-gray-400'"
        fill="currentColor"
        viewBox="0 0 20 20"
      >
        <path
          fill-rule="evenodd"
          d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
          clip-rule="evenodd"
        />
      </svg>
    </div>

    <!-- Double tick (colored) - Seen -->
    <div v-else-if="status === 'seen'" class="flex -space-x-1">
      <svg class="w-4 h-4 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
        <path
          fill-rule="evenodd"
          d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
          clip-rule="evenodd"
        />
      </svg>
      <svg class="w-4 h-4 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
        <path
          fill-rule="evenodd"
          d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
          clip-rule="evenodd"
        />
      </svg>
    </div>
  </div>
</template>
