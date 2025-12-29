<template>
  <div class="hidden lg:block w-80 bg-white border-l border-gray-200 overflow-y-auto">
    <div class="p-6">
      <!-- Conversation Info -->
      <div class="text-center mb-6">
        <img
          :src="conversation.avatar || conversation.members?.[0]?.avatar"
          :alt="conversation.name"
          class="w-24 h-24 rounded-full mx-auto mb-3 object-cover"
        />
        <h3 class="text-xl font-semibold text-gray-900">{{ conversation.name }}</h3>
        <p class="text-sm text-gray-500">
          <template v-if="conversation.type === 'group'">
            {{ conversation.members?.length }} members
          </template>
          <template v-else>
            {{ conversation.isOnline ? "Online" : "Offline" }}
          </template>
        </p>
      </div>

      <!-- Tabs -->
      <div class="flex border-b border-gray-200 mb-4">
        <button
          v-for="tab in tabs"
          :key="tab.value"
          @click="$emit('update-tab', tab.value)"
          :class="[
            'flex-1 py-2 text-sm font-medium border-b-2 transition-colors',
            activeTab === tab.value
              ? 'border-blue-500 text-blue-600'
              : 'border-transparent text-gray-500 hover:text-gray-700',
          ]"
        >
          {{ tab.label }}
        </button>
      </div>

      <!-- Tab Content -->
      <GroupMembers
        v-if="activeTab === 'members' && conversation.type === 'group'"
        :members="conversation.members"
        @add-member="$emit('add-member')"
        @make-admin="$emit('make-admin', $event)"
        @remove-admin="$emit('remove-admin', $event)"
        @remove-member="$emit('remove-member', $event)"
      />

      <MediaGallery v-if="activeTab === 'media'" />
      <FilesList v-if="activeTab === 'files'" />
      <LinksList v-if="activeTab === 'links'" />

      <!-- Group Settings -->
      <GroupSettings
        v-if="conversation.type === 'group' && activeTab === 'members'"
        :settings="conversation.settings"
        @update="$emit('update-settings', $event)"
      />

      <!-- Actions -->
      <div class="space-y-2 mt-6">
        <!-- Search in Conversation (Triggers Header Search) -->
        <button
          @click="$emit('trigger-search')"
          class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded flex items-center transition-colors"
        >
          <svg
            class="w-5 h-5 mr-3 text-gray-600"
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
          Search in conversation
        </button>

        <button
          @click="toggleMute"
          class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded flex items-center transition-colors"
        >
          <svg
            class="w-5 h-5 mr-3 text-gray-600"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              v-if="!isMuted"
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
            />
            <path
              v-else
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M5.586 15H4a1 1 0 01-.707-1.707l1.586-1.586a1 1 0 01.707-.293h3.586a1 1 0 01.707.293l7 7a1 1 0 01-1.414 1.414l-7-7A1 1 0 019.172 13H5.586zM9 9V5a3 3 0 016 0v4M9 9v10m6-10v10"
            />
          </svg>
          {{ isMuted ? "Unmute notifications" : "Mute notifications" }}
        </button>

        <button
          v-if="conversation.type === 'group'"
          @click="$emit('leave-group')"
          class="w-full text-left px-4 py-2 text-sm text-yellow-600 hover:bg-yellow-50 rounded flex items-center transition-colors"
        >
          <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
            />
          </svg>
          Leave group
        </button>

        <button
          @click="toggleBlock"
          class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 rounded flex items-center transition-colors"
        >
          <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"
            />
          </svg>
          {{ conversation.isBlocked ? "Unblock user" : "Block user" }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from "vue";
import GroupMembers from "./GroupMembers.vue";
import MediaGallery from "./MediaGallery.vue";
import FilesList from "./FilesList.vue";
import LinksList from "./LinksList.vue";
import GroupSettings from "./GroupSettings.vue";

const tabs = [
  { label: "Members", value: "members" },
  { label: "Media", value: "media" },
  { label: "Files", value: "files" },
  { label: "Links", value: "links" },
];

defineProps({
  conversation: {
    type: Object,
    required: true,
  },
  activeTab: {
    type: String,
    default: "members",
  },
});

const emit = defineEmits([
  "update-tab",
  "add-member",
  "make-admin",
  "remove-admin",
  "remove-member",
  "leave-group",
  "update-settings",
  "trigger-search",
]);

const isMuted = ref(false);

const toggleMute = () => {
  isMuted.value = !isMuted.value;
  // Emit event to parent
  console.log("Mute toggled:", isMuted.value);
};

const toggleBlock = () => {
  console.log("Block toggled");
};
</script>
