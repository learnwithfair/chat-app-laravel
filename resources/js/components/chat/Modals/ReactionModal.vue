<template>
  <div
    @click="$emit('close')"
    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
  >
    <div
      @click.stop
      class="bg-white rounded-lg shadow-xl max-w-md w-full max-h-[70vh] overflow-hidden"
    >
      <!-- Header -->
      <div class="p-4 border-b border-gray-200 flex items-center justify-between">
        <h3 class="text-lg font-semibold">Reactions</h3>
        <button @click="$emit('close')" class="text-gray-500 hover:text-gray-700">
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

      <!-- Reaction Filter Tabs -->
      <div
        class="flex items-center space-x-1 p-2 border-b border-gray-200 overflow-x-auto"
      >
        <button
          @click="selectedFilter = 'all'"
          :class="[
            'px-6 py-2 rounded-full text-sm font-medium transition-colors whitespace-nowrap',
            selectedFilter === 'all'
              ? 'bg-blue-100 text-blue-600'
              : 'text-gray-600 bg-gray-100 hover:bg-gray-200',
          ]"
        >
          All &nbsp;{{ +totalCount }}
        </button>
        <button
          v-for="reaction in uniqueReactions"
          :key="reaction.emoji"
          @click="selectedFilter = reaction.emoji"
          :class="[
            'px-6 py-1 rounded-full text-sm font-medium transition-colors whitespace-nowrap flex items-center space-x-1',
            selectedFilter === reaction.emoji
              ? 'bg-blue-100 text-blue-600'
              : 'text-gray-600 bg-gray-100 hover:bg-gray-200',
          ]"
        >
          <span class="text-lg">{{ reaction.emoji }}</span>
          <span>{{ reaction.count }}</span>
        </button>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="p-8 text-center">
        <svg
          class="animate-spin h-8 w-8 mx-auto text-blue-500"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
        >
          <circle
            class="opacity-25"
            cx="12"
            cy="12"
            r="10"
            stroke="currentColor"
            stroke-width="4"
          ></circle>
          <path
            class="opacity-75"
            fill="currentColor"
            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
          ></path>
        </svg>
        <p class="mt-2 text-gray-500">Loading reactions...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="p-8 text-center text-red-500">
        <p>{{ error }}</p>
      </div>

      <!-- Users List -->
      <div v-else class="overflow-y-auto max-h-96">
        <div v-if="filteredUsers.length === 0" class="p-8 text-center text-gray-500">
          No reactions found
        </div>
        <div
          v-for="user in filteredUsers"
          :key="user.id"
          class="flex items-center justify-between p-4 hover:bg-gray-50 transition-colors"
        >
          <div class="flex items-center">
            <img
              :src="user.avatar"
              :alt="user.name"
              class="w-10 h-10 rounded-full object-cover"
            />
            <span class="ml-3 font-medium text-gray-900">{{ user.name }}</span>
          </div>
          <span class="text-2xl">{{ user.reaction }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from "vue";

const props = defineProps({
  messageId: {
    type: [String, Number],
    required: true,
  },
  reactions: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(["close", "fetch-reactions"]);

// State
const selectedFilter = ref("all");
const reactionUsers = ref([]);
const loading = ref(true);
const error = ref(null);

// Computed
const uniqueReactions = computed(() => {
  const grouped = {};
  props.reactions.forEach((r) => {
    if (grouped[r.emoji]) {
      grouped[r.emoji].count += r.count;
    } else {
      grouped[r.emoji] = { emoji: r.emoji, count: r.count };
    }
  });
  return Object.values(grouped);
});

const totalCount = computed(() => {
  return props.reactions.reduce((sum, r) => sum + r.count, 0);
});

const filteredUsers = computed(() => {
  if (selectedFilter.value === "all") {
    return reactionUsers.value;
  }
  return reactionUsers.value.filter((user) => user.reaction === selectedFilter.value);
});

// Fetch reaction users from API
const fetchReactionUsers = async () => {
  loading.value = true;
  error.value = null;

  try {
    emit("fetch-reactions", {
      messageId: props.messageId,
      callback: (users) => {
        reactionUsers.value = users;
        loading.value = false;
      },
      errorCallback: (err) => {
        error.value = "Failed to load reactions";
        loading.value = false;
      },
    });
  } catch (err) {
    error.value = "Failed to load reactions";
    loading.value = false;
  }
};

// Watch for modal open
watch(
  () => props.messageId,
  (newId) => {
    if (newId) {
      fetchReactionUsers();
    }
  },
  { immediate: true }
);
</script>

<style scoped>
/* Smooth transitions */
button {
  transition: all 0.2s ease-in-out;
  cursor: pointer;
}
</style>
