<script setup>
import { router } from "@inertiajs/vue3";
import { usePage } from "@inertiajs/vue3";

import ConversationItem from "./ConversationItem.vue";
import OnlineUsers from "./OnlineUsers.vue";
import SearchBar from "./SearchBar.vue";
import { generateAvatar } from "../../../Utils/Chat/avatarHelper";
import { ref, computed } from "vue";
import Startchatmodal from "../Modals/Startchatmodal.vue";

// Tabs
const tabs = [
  { label: "All", value: "all" },
  { label: "Personal", value: "private" },
  { label: "Groups", value: "group" },
];

const page = usePage();
const user = computed(() => page.props.auth.user);

// Props
const props = defineProps({
  conversations: Array,
  activeConversation: Object,
  activeTab: String,
  searchQuery: String,
  onlineUsers: Array,
  conversationPagination: Object,
  // Start Chat Modal props
  showStartChatModal: Boolean,
  startChatUsers: Array,
  startChatLoading: Boolean,
  startChatPagination: Object,
});

// Emits
const emit = defineEmits([
  "select",
  "update-tab",
  "update-search",
  "create-group",
  "start-chat",
  "loadMore",
  // Start Chat Modal events
  "open-start-chat-modal",
  "close-start-chat-modal",
  "search-start-chat-users",
  "load-more-start-chat-users",
  "select-start-chat-user",
]);

// Scroll ref
const conversationListRef = ref(null);

// Scroll handler for load more
const onScroll = (e) => {
  const { scrollTop, scrollHeight, clientHeight } = e.target;
  const scrolledToBottom = scrollHeight - scrollTop - clientHeight < 100;

  if (
    scrolledToBottom &&
    props.conversationPagination?.hasMore &&
    !props.conversationPagination?.loading
  ) {
    emit("loadMore");
  }
};

// Empty state messages
const getEmptyStateTitle = () => {
  if (props.searchQuery) {
    return "No Results Found";
  }

  switch (props.activeTab) {
    case "private":
      return "No Personal Chats";
    case "group":
      return "No Group Conversations";
    default:
      return "No Conversations Yet";
  }
};

const getEmptyStateDescription = () => {
  if (props.searchQuery) {
    return `We couldn't find any conversations matching "${props.searchQuery}". Try a different search term.`;
  }

  switch (props.activeTab) {
    case "private":
      return "Start a new conversation with your contacts to begin chatting.";
    case "group":
      return "Create a group to start collaborating with multiple people at once.";
    default:
      return "Your conversation list is empty. Start chatting with someone to get started.";
  }
};

// Logout
const logout = () => {
  if (!confirm("Are you sure you want to logout?")) return;
  router.post("/logout");
};
</script>

<template>
  <div
    class="conversation-list-container flex flex-col w-full md:w-96 bg-white border-r border-gray-200"
  >
    <!-- Header -->
    <div class="p-4 pb-0">
      <div class="flex items-center justify-between mb-4">
        <!-- User Info -->
        <a href="/settings/profile">
          <div class="flex items-center space-x-3">
            <img
              :src="user?.avatar_path || generateAvatar(user?.name)"
              :alt="user?.name"
              class="w-10 h-10 rounded-full object-cover border-2 border-gray-200"
            />
            <div>
              <h1 class="text-lg font-semibold text-gray-800">
                {{ user?.name || "User" }}
              </h1>
              <p class="text-xs text-gray-500">Active now</p>
            </div>
          </div>
        </a>

        <div class="flex items-center space-x-3">
          <!-- Create Group -->
          <button
            @click="emit('create-group')"
            class="p-2 text-blue-500 bg-blue-50 rounded-full transition-colors cursor-pointer hover:bg-blue-100"
            title="Create Group"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M17 20h-10M7 20v-2a3 3 0 015.356-1.857M17 20v-2a3 3 0 00-5.356-1.857
                 M15 7a3 3 0 11-6 0 3 3 0 016 0
                 M7 10a2 2 0 11-4 0 2 2 0 014 0
                 M17 10a2 2 0 11-4 0 2 2 0 014 0"
              />
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M19 8v4M21 10h-4"
              />
            </svg>
          </button>

          <!-- Logout -->
          <button
            @click="logout"
            class="p-2 text-red-500 bg-red-50 rounded-full transition-colors cursor-pointer hover:bg-red-100"
            title="Logout"
          >
            <svg
              class="w-6 h-6 text-red-600"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M17 16l4-4m0 0l-4-4m4 4H7
           m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7
           a3 3 0 013-3h4a3 3 0 013 3v1"
              />
            </svg>
          </button>
        </div>
      </div>

      <!-- Search Bar -->
      <SearchBar
        :model-value="searchQuery"
        @update:model-value="emit('update-search', $event)"
      />
    </div>

    <!-- Online Users -->
    <OnlineUsers
      v-if="onlineUsers.length > 0"
      :users="onlineUsers"
      @start-chat="emit('start-chat', $event)"
      @open-search="emit('open-start-chat-modal')"
    />

    <!-- Tabs -->
    <div class="flex space-x-1 mb-1 bg-gray-100 rounded-lg p-1">
      <button
        v-for="tab in tabs"
        :key="tab.value"
        @click="emit('update-tab', tab.value)"
        :class="[
          'flex-1 px-4 py-2 text-sm font-medium rounded-md transition-colors cursor-pointer',
          activeTab === tab.value
            ? 'bg-white text-blue-600 shadow-sm'
            : 'text-gray-600 hover:text-gray-800',
        ]"
      >
        {{ tab.label }}
      </button>
    </div>

    <!-- Conversation List Scrollable -->
    <div
      ref="conversationListRef"
      class="conversations-scroll telegram-scrollbar flex-1 overflow-y-auto px-2"
      @scroll="onScroll"
    >
      <!-- Empty State -->
      <div
        v-if="conversations.length === 0 && !conversationPagination.loading"
        class="empty-state"
      >
        <div class="empty-state-content">
          <svg
            class="empty-state-icon"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              v-if="activeTab === 'all'"
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"
            />
            <path
              v-else-if="activeTab === 'private'"
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
            />
            <path
              v-else-if="activeTab === 'group'"
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
            />
            <path
              v-else
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
            />
          </svg>

          <h3 class="empty-state-title">
            {{ getEmptyStateTitle() }}
          </h3>

          <p class="empty-state-description">
            {{ getEmptyStateDescription() }}
          </p>

          <button
            v-if="!searchQuery && activeTab === 'group'"
            @click="emit('create-group')"
            class="empty-state-button"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 4v16m8-8H4"
              />
            </svg>
            Create Group
          </button>

          <button
            v-else-if="!searchQuery"
            @click="emit('open-start-chat-modal')"
            class="empty-state-button"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 4v16m8-8H4"
              />
            </svg>
            Start a Chat
          </button>
        </div>
      </div>

      <!-- Conversation Items -->
      <ConversationItem
        v-for="conversation in conversations"
        :key="conversation.id"
        :conversation="conversation"
        :is-active="activeConversation?.id === conversation.id"
        @select="emit('select', conversation)"
      />
    </div>

    <!-- Loading Indicator -->
    <div v-if="conversationPagination.loading" class="loading-indicator">
      <div class="spinner"></div>
      <span>Loading more...</span>
    </div>

    <!-- Start Chat Modal -->
    <Startchatmodal
      :is-open="showStartChatModal"
      :users="startChatUsers"
      :pagination="startChatPagination"
      :loading="startChatLoading"
      @close="emit('close-start-chat-modal')"
      @select-user="emit('select-start-chat-user', $event)"
      @search="emit('search-start-chat-users', $event)"
      @load-more="emit('load-more-start-chat-users')"
    />
  </div>
</template>

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

/* Empty State */
.empty-state {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 400px;
  padding: 40px 20px;
}

.empty-state-content {
  text-align: center;
  max-width: 320px;
}

.empty-state-icon {
  width: 80px;
  height: 80px;
  margin: 0 auto 24px;
  color: #cbd5e1;
  stroke-width: 1.5;
}

.empty-state-title {
  font-size: 20px;
  font-weight: 600;
  color: #1e293b;
  margin-bottom: 12px;
}

.empty-state-description {
  font-size: 14px;
  color: #64748b;
  line-height: 1.6;
  margin-bottom: 24px;
}

.empty-state-button {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  background-color: #3b82f6;
  color: white;
  font-size: 14px;
  font-weight: 500;
  border-radius: 8px;
  border: none;
  cursor: pointer;
  transition: all 0.2s ease;
}

.empty-state-button:hover {
  background-color: #2563eb;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.empty-state-button:active {
  transform: translateY(0);
}

/* Loading */
.loading-indicator {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 20px;
  color: #666;
  font-size: 14px;
}

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
