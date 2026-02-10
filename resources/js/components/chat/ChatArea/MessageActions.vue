<script setup>
const props = defineProps({
  message: Object,
  isMine: Boolean,
});

const emit = defineEmits(["reply", "edit", "forward", "delete", "toggle-pin"]);
</script>

<template>
  <div
    :class="[
      'absolute top-0 hidden group-hover:flex items-center space-x-1 p-1 bg-white rounded-lg shadow-lg border border-gray-200 z-10',
      isMine ? '-left-37' : '-right-23',
    ]"
  >
    <button
      @click.stop="emit('reply', message)"
      class="p-1 hover:bg-gray-100 rounded"
      title="Reply"
    >
      <svg
        class="w-4 h-4 text-gray-600"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"
        />
      </svg>
    </button>

    <!-- Pin/Unpin -->
    <button
      @click.stop="emit('toggle-pin', message)"
      class="p-1 hover:bg-gray-100 rounded"
      :class="message.isPinned ? 'bg-gray-100' : ''"
      :title="message.isPinned ? 'Unpin' : 'Pin'"
    >
      <span>📌</span>
    </button>

    <button
      v-if="isMine && message.messageType === 'text'"
      @click.stop="emit('edit', message)"
      class="p-1 hover:bg-gray-100 rounded"
      title="Edit"
    >
      <svg
        class="w-4 h-4 text-gray-600"
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
    </button>

    <button
      @click.stop="emit('forward', message)"
      class="p-1 hover:bg-gray-100 rounded"
      title="Forward"
    >
      <svg
        class="w-4 h-4 text-gray-600"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M13 7l5 5m0 0l-5 5m5-5H6"
        />
      </svg>
    </button>

    <button
      @click.stop="emit('delete', message)"
      class="p-1 hover:bg-gray-100 rounded"
      title="Delete"
    >
      <svg
        class="w-4 h-4 text-red-600"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
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
