<template>
  <div
    v-if="isOpen"
    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
    @click.self="closeModal"
  >
    <div
      class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4 max-h-[80vh] flex flex-col"
    >
      <!-- Header -->
      <div class="p-4 border-b border-gray-200 flex items-center justify-between">
        <h2 class="text-lg font-semibold text-gray-800">Friends List</h2>
        <button
          @click="closeModal"
          class="p-2 hover:bg-gray-100 rounded-full transition-colors"
        >
          <svg
            class="w-5 h-5 text-gray-500"
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

      <!-- Search Bar -->
      <div class="p-4 border-b border-gray-100">
        <div class="relative">
          <input
            v-model="searchQuery"
            @input="handleSearch"
            type="text"
            placeholder="Search users..."
            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          />
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
        </div>
      </div>

      <!-- User List -->
      <div
        ref="userListRef"
        class="flex-1 overflow-y-auto telegram-scrollbar"
        @scroll="handleScroll"
      >
        <div
          v-if="loading && users.length === 0"
          class="flex items-center justify-center py-12"
        >
          <div class="spinner"></div>
        </div>

        <div
          v-else-if="users.length === 0"
          class="flex flex-col items-center justify-center py-12 text-gray-500"
        >
          <svg
            class="w-16 h-16 mb-3 text-gray-300"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
            />
          </svg>
          <p class="text-sm">No users found</p>
        </div>

        <div v-else class="py-2">
          <div
            v-for="user in users"
            :key="user.id"
            @click="selectUser(user)"
            class="flex items-center space-x-3 px-4 py-3 hover:bg-gray-50 cursor-pointer transition-colors"
          >
            <div class="relative flex-shrink-0">
              <img
                :src="user.avatar"
                :alt="user.name"
                class="w-12 h-12 rounded-full object-cover border border-gray-200"
              />
              <span
                v-if="user.isOnline"
                class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 border-2 border-white rounded-full"
              ></span>
            </div>
            <div class="flex-1 min-w-0">
              <h3 class="text-sm font-medium text-gray-900 truncate">{{ user.name }}</h3>
              <p class="text-xs text-gray-500 truncate">{{ user.email }}</p>
            </div>
            <svg
              class="w-5 h-5 text-gray-400"
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
          </div>
        </div>

        <!-- Load More Indicator -->
        <div v-if="pagination.loading" class="flex items-center justify-center py-4">
          <div class="spinner"></div>
          <span class="ml-2 text-sm text-gray-500">Loading more...</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from "vue";
import { generateAvatar } from "../../../Utils/Chat/avatarHelper";

const props = defineProps({
  isOpen: Boolean,
  users: Array,
  pagination: Object,
  loading: Boolean,
});

const emit = defineEmits(["close", "select-user", "search", "load-more"]);

const searchQuery = ref("");
const userListRef = ref(null);
let searchTimeout = null;

const closeModal = () => {
  searchQuery.value = "";
  emit("close");
};

const selectUser = (user) => {
  emit("select-user", user);
  closeModal();
};

const handleSearch = () => {
  // Debounce search
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    emit("search", searchQuery.value);
  }, 300);
};

const handleScroll = (e) => {
  const { scrollTop, scrollHeight, clientHeight } = e.target;
  const scrolledToBottom = scrollHeight - scrollTop - clientHeight < 100;

  if (scrolledToBottom && props.pagination?.hasMore && !props.pagination?.loading) {
    emit("load-more");
  }
};

// Reset search when modal closes
watch(
  () => props.isOpen,
  (newVal) => {
    if (!newVal) {
      searchQuery.value = "";
    }
  }
);
</script>

<style scoped>
/* Scrollbar */
.telegram-scrollbar {
  scrollbar-width: thin;
  scrollbar-color: transparent transparent;
  transition: scrollbar-color 0.3s ease;
}

.telegram-scrollbar:hover {
  scrollbar-color: rgba(0, 0, 0, 0.2) transparent;
}

.telegram-scrollbar::-webkit-scrollbar {
  width: 6px;
}

.telegram-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}

.telegram-scrollbar::-webkit-scrollbar-thumb {
  background-color: transparent;
  border-radius: 3px;
  transition: background-color 0.3s ease;
}

.telegram-scrollbar:hover::-webkit-scrollbar-thumb {
  background-color: rgba(0, 0, 0, 0.2);
}

.telegram-scrollbar:hover::-webkit-scrollbar-thumb:hover {
  background-color: rgba(0, 0, 0, 0.35);
}

/* Loading Spinner */
.spinner {
  width: 20px;
  height: 20px;
  border: 3px solid #f3f3f3;
  border-top: 3px solid #3498db;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
}
</style>
