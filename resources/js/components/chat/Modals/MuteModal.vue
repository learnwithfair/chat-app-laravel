<script setup>
import { ref } from "vue";

const props = defineProps({
  conversationName: {
    type: String,
    required: true,
  },
  isMuted: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(["close", "mute"]);

const muteOptions = [
  { label: "15 minutes", value: 15 },
  { label: "1 hour", value: 60 },
  { label: "8 hours", value: 480 },
  { label: "24 hours", value: 1440 },
  { label: "Until I change", value: -1 },
];

const handleMute = (minutes) => {
  emit("mute", minutes);
  emit("close");
};

const handleUnmute = () => {
  emit("mute", 0); // 0 means unmute
  emit("close");
};
</script>

<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
      <!-- Header -->
      <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-semibold text-gray-900">
            {{ isMuted ? "Unmute" : "Mute" }} Notifications
          </h3>
          <button
            @click="$emit('close')"
            class="text-gray-400 hover:text-gray-500 transition-colors"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M6 18L18 6M6 6l12 12"
              />
            </svg>
          </button>
        </div>
        <p class="mt-1 text-sm text-gray-500">
          {{ conversationName }}
        </p>
      </div>

      <!-- Body -->
      <div class="px-6 py-4">
        <template v-if="isMuted">
          <!-- Currently Muted - Show Unmute Option -->
          <div class="text-center py-4">
            <div
              class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-yellow-100 mb-4"
            >
              <svg
                class="w-8 h-8 text-yellow-600"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M5.586 15H4a1 1 0 01-.707-1.707l1.586-1.586a1 1 0 01.707-.293h3.586a1 1 0 01.707.293l7 7a1 1 0 01-1.414 1.414l-7-7A1 1 0 019.172 13H5.586zM9 9V5a3 3 0 016 0v4M9 9v10m6-10v10"
                />
              </svg>
            </div>
            <p class="text-gray-700 mb-6">Notifications are currently muted</p>
            <button
              @click="handleUnmute"
              class="w-full px-4 py-3 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors font-medium"
            >
              Unmute Notifications
            </button>
          </div>
        </template>

        <template v-else>
          <!-- Not Muted - Show Mute Duration Options -->
          <p class="text-sm text-gray-600 mb-4">Mute notifications for:</p>
          <div class="space-y-2">
            <button
              v-for="option in muteOptions"
              :key="option.value"
              @click="handleMute(option.value)"
              class="w-full text-left px-4 py-3 border border-gray-200 rounded-lg hover:bg-gray-50 hover:border-blue-500 transition-all flex items-center justify-between group"
            >
              <span class="text-gray-700 group-hover:text-blue-600 font-medium">
                {{ option.label }}
              </span>
              <svg
                class="w-5 h-5 text-gray-400 group-hover:text-blue-500"
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
        </template>
      </div>

      <!-- Footer -->
      <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-lg">
        <button
          @click="$emit('close')"
          class="w-full px-4 py-2 text-gray-700 bg-yellow-400 rounded-lg hover:text-gray-900 transition-colors font-medium"
        >
          Cancel
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
button {
  cursor: pointer;
}
</style>
