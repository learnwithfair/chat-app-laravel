<template>
  <div
    v-if="pinnedMessages.length > 0"
    class="bg-blue-50 border-b border-blue-200 px-4 py-2 flex items-center justify-between"
  >
    <div class="flex items-center space-x-3 flex-1 min-w-0">
      <!-- Pin Icon -->
      <span>📌</span>

      <!-- Message Content (Clickable) -->
      <div class="flex-1 min-w-0 cursor-pointer" @click="scrollToCurrentPinned">
        <p class="text-sm font-medium text-blue-900">
          Pinned Message
          <span v-if="pinnedMessages.length > 1" class="text-blue-600">
            ({{ currentIndex + 1 }}/{{ pinnedMessages.length }})
          </span>
        </p>
        <p class="text-xs text-blue-700 truncate">
          {{ currentMessage?.text || "📎 Attachment" }}
        </p>
      </div>

      <!-- Navigation Arrows (if multiple) -->
      <div
        v-if="pinnedMessages.length > 1"
        class="flex items-center space-x-1 flex-shrink-0"
      >
        <button
          @click="previousPinned"
          class="p-1 hover:bg-blue-100 rounded transition-colors"
          :disabled="currentIndex === 0"
          :class="{ 'opacity-50 cursor-not-allowed': currentIndex === 0 }"
        >
          <svg
            class="w-4 h-4 text-blue-600"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M15 19l-7-7 7-7"
            />
          </svg>
        </button>
        <button
          @click="nextPinned"
          class="p-1 hover:bg-blue-100 rounded transition-colors"
          :disabled="currentIndex === pinnedMessages.length - 1"
          :class="{
            'opacity-50 cursor-not-allowed': currentIndex === pinnedMessages.length - 1,
          }"
        >
          <svg
            class="w-4 h-4 text-blue-600"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M9 5l7 7-7 7"
            />
          </svg>
        </button>
      </div>
    </div>

    <!-- Close Button -->
    <button
      @click="emit('close')"
      class="ml-2 p-1 hover:bg-blue-100 rounded transition-colors flex-shrink-0"
    >
      <svg
        class="w-4 h-4 text-blue-600"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
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

<script setup>
import { ref, computed, watch } from "vue";

const props = defineProps({
  pinnedMessages: {
    type: Array,
    required: true,
  },
});

const emit = defineEmits(["close", "scroll-to-message"]);

const currentIndex = ref(0);

const currentMessage = computed(() => props.pinnedMessages[currentIndex.value]);

const nextPinned = () => {
  if (currentIndex.value < props.pinnedMessages.length - 1) {
    currentIndex.value++;
    scrollToCurrentPinned();
  }
};

const previousPinned = () => {
  if (currentIndex.value > 0) {
    currentIndex.value--;
    scrollToCurrentPinned();
  }
};

const scrollToCurrentPinned = () => {
  const message = currentMessage.value;
  if (message) {
    emit("scroll-to-message", message.id);
  }
};

// Reset index when pinned messages change
watch(
  () => props.pinnedMessages.length,
  () => {
    currentIndex.value = 0;
  }
);
</script>

<style scoped>
button {
  cursor: pointer;
}
</style>
