<template>
  <div class="bg-white border-b border-gray-200">
    <!-- Normal Header (when not searching) -->
    <div v-if="!isSearching" class="p-4 flex items-center justify-between">
      <div class="flex items-center">
        <button
          @click="$emit('back')"
          class="md:hidden mr-3 text-gray-600 hover:text-gray-800"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M15 19l-7-7 7-7"
            />
          </svg>
        </button>

        <img :src="avatar" :alt="name" class="w-10 h-10 rounded-full object-cover" />
        <div class="ml-3">
          <h2 class="text-lg font-semibold text-gray-900">{{ name }}</h2>
          <p class="text-xs text-gray-500">{{ subtitle }}</p>
        </div>
      </div>

      <div class="flex items-center space-x-2">
        <button
          @click="openSearch"
          class="p-2 text-gray-600 hover:bg-gray-100 rounded-full transition-colors"
          title="Search"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
            />
          </svg>
        </button>

        <button
          @click="$emit('audio-call')"
          class="p-2 text-gray-600 hover:bg-gray-100 rounded-full transition-colors"
          title="Audio Call"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"
            />
          </svg>
        </button>

        <button
          @click="$emit('video-call')"
          class="p-2 text-gray-600 hover:bg-gray-100 rounded-full transition-colors"
          title="Video Call"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"
            />
          </svg>
        </button>

        <button
          @click="$emit('toggle-info')"
          class="p-2 text-gray-600 hover:bg-gray-100 rounded-full transition-colors"
          title="Conversation Info"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
            />
          </svg>
        </button>
      </div>
    </div>

    <!-- Search Mode Header (expanded search) -->
    <div v-else class="p-4 flex items-center space-x-3">
      <!-- Back Button -->
      <button
        @click="closeSearch"
        class="text-gray-600 hover:text-gray-800 flex-shrink-0"
        title="Close search"
      >
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M10 19l-7-7m0 0l7-7m-7 7h18"
          />
        </svg>
      </button>

      <!-- Search Input -->
      <div class="flex-1 relative">
        <input
          ref="searchInput"
          v-model="searchQuery"
          @input="handleSearch"
          @keydown.escape="closeSearch"
          type="text"
          placeholder="Search in conversation..."
          class="w-full pl-10 pr-10 py-2 border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
        />

        <!-- Search Icon -->
        <svg
          class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
          />
        </svg>

        <!-- Clear Button -->
        <button
          v-if="searchQuery"
          @click="clearSearch"
          class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M6 18L18 6M6 6l12 12"
            />
          </svg>
        </button>
      </div>

      <!-- Results Count (optional) -->
      <div
        v-if="searchQuery && searchResults !== null"
        class="text-sm text-gray-600 whitespace-nowrap"
      >
        {{ searchResults }} results
      </div>
    </div>

    <!-- Search Results Navigation (when searching and has results) -->
    <div
      v-if="isSearching && searchQuery && searchResults > 0"
      class="px-4 pb-3 flex items-center justify-between border-t border-gray-100 pt-3"
    >
      <span class="text-sm text-gray-600">
        {{ currentResultIndex + 1 }} of {{ searchResults }}
      </span>

      <div class="flex items-center space-x-2">
        <button
          @click="previousResult"
          :disabled="currentResultIndex === 0"
          :class="[
            'p-2 rounded-full transition-colors',
            currentResultIndex === 0
              ? 'text-gray-300 cursor-not-allowed'
              : 'text-gray-600 hover:bg-gray-100',
          ]"
          title="Previous result"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M5 15l7-7 7 7"
            />
          </svg>
        </button>

        <button
          @click="nextResult"
          :disabled="currentResultIndex === searchResults - 1"
          :class="[
            'p-2 rounded-full transition-colors',
            currentResultIndex === searchResults - 1
              ? 'text-gray-300 cursor-not-allowed'
              : 'text-gray-600 hover:bg-gray-100',
          ]"
          title="Next result"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M19 9l-7 7-7-7"
            />
          </svg>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, nextTick } from "vue";

defineProps({
  name: String,
  subtitle: String,
  avatar: String,
});

const emit = defineEmits([
  "back",
  "audio-call",
  "video-call",
  "toggle-info",
  "search",
  "search-query-change",
  "search-next",
  "search-previous",
]);

const isSearching = ref(false);
const searchQuery = ref("");
const searchResults = ref(null);
const currentResultIndex = ref(0);
const searchInput = ref(null);

const openSearch = () => {
  isSearching.value = true;
  nextTick(() => {
    searchInput.value?.focus();
  });
};

const closeSearch = () => {
  isSearching.value = false;
  searchQuery.value = "";
  searchResults.value = null;
  currentResultIndex.value = 0;
  emit("search", { query: "", isActive: false });
};

const clearSearch = () => {
  searchQuery.value = "";
  searchResults.value = null;
  currentResultIndex.value = 0;
  searchInput.value?.focus();
  emit("search-query-change", "");
};

const handleSearch = () => {
  currentResultIndex.value = 0;
  emit("search-query-change", searchQuery.value);
};

const nextResult = () => {
  if (currentResultIndex.value < searchResults.value - 1) {
    currentResultIndex.value++;
    emit("search-next", currentResultIndex.value);
  }
};

const previousResult = () => {
  if (currentResultIndex.value > 0) {
    currentResultIndex.value--;
    emit("search-previous", currentResultIndex.value);
  }
};

// Watch for external search activation (from right panel)
watch(
  () => isSearching.value,
  (newValue) => {
    emit("search", { query: searchQuery.value, isActive: newValue });
  }
);

// Expose methods for parent component to trigger search
defineExpose({
  openSearch,
  closeSearch,
  setSearchResults: (count) => {
    searchResults.value = count;
  },
});
</script>

<style scoped>
/* Smooth transitions */
.transition-all {
  transition: all 0.3s ease-in-out;
}
button{
  cursor: pointer;
}
</style>
