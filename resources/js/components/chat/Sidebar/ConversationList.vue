<script setup>
import { router } from "@inertiajs/vue3";
import { usePage } from "@inertiajs/vue3";

import ConversationItem from "./ConversationItem.vue";
import OnlineUsers from "./OnlineUsers.vue";
import SearchBar from "./SearchBar.vue";
import { generateAvatar } from "../../../Utils/Chat/avatarHelper";
import { computed } from "vue";

const tabs = [
  { label: "All", value: "all" },
  { label: "Personal", value: "private" },
  { label: "Groups", value: "group" },
];

const page = usePage();
const user = computed(() => page.props.auth.user);

defineProps({
  conversations: Array,
  activeConversation: Object,
  activeTab: String,
  searchQuery: String,
  onlineUsers: Array,
});

defineEmits(["select", "update-tab", "update-search", "create-group", "start-chat"]);

const logout = () => {
  if (!confirm("Are you sure you want to logout?")) return;
  router.post("/logout");
};
</script>

<template>
  <div
    :class="[
      'bg-white border-r border-gray-200 flex flex-col transition-all duration-300 w-full md:w-96',
      activeConversation ? 'hidden md:flex' : 'flex',
    ]"
  >
    <!-- Header -->
    <div class="p-4 pb-0">
      <div class="flex items-center justify-between mb-4">
        <!-- User Info -->
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

        <div class="flex items-center space-x-3">
          <!-- Create Group -->
          <button
            @click="$emit('create-group')"
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
        @update:model-value="$emit('update-search', $event)"
      />
    </div>

    <!-- Online Users -->
    <OnlineUsers
      v-if="onlineUsers.length > 0"
      :users="onlineUsers"
      @start-chat="$emit('start-chat', $event)"
    />

    <!-- Tabs -->
    <div class="flex space-x-1 mb-1 bg-gray-100 rounded-lg p-1">
      <button
        v-for="tab in tabs"
        :key="tab.value"
        @click="$emit('update-tab', tab.value)"
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

    <!-- Conversation List -->
    <div class="flex-1 overflow-y-auto">
      <ConversationItem
        v-for="conversation in conversations"
        :key="conversation.id"
        :conversation="conversation"
        :is-active="activeConversation?.id === conversation.id"
        @select="$emit('select', conversation)"
      />
    </div>
  </div>
</template>
