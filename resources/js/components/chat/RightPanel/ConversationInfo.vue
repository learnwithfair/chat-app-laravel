<template>
  <aside class="hidden lg:block w-80 bg-white border-l border-gray-200 overflow-y-auto">
    <div class="p-6">
      <!-- ================= Conversation Info ================= -->
      <div class="text-center mb-6">
        <img
          :src="avatar"
          :alt="conversation.name"
          class="w-24 h-24 rounded-full mx-auto mb-3 object-cover ring-2 ring-gray-100"
        />

        <h3 class="text-xl font-semibold text-gray-900 truncate">
          {{ conversation.name }}
        </h3>

        <p class="text-sm text-gray-500">
          <template v-if="isGroup">
            {{ conversation.members?.length || 0 }} members
          </template>
          <template v-else>
            <span :class="conversation.isOnline ? 'text-green-600' : 'text-gray-400'">
              {{ conversation.isOnline ? "Online" : "Offline" }}
            </span>
          </template>
        </p>
      </div>

      <!-- ================= Tabs ================= -->
      <div class="flex border-b border-gray-200 mb-4">
        <button
          v-for="tab in visibleTabs"
          :key="tab.value"
          @click="changeTab(tab.value)"
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

      <!-- ================= Tab Content ================= -->
      <GroupMembers
        v-if="isGroup && activeTab === 'members'"
        :members="conversation.members"
        @add-member="$emit('add-member')"
        @make-admin="$emit('make-admin', $event)"
        @remove-admin="$emit('remove-admin', $event)"
        @remove-member="$emit('remove-member', $event)"
      />

      <MediaGallery v-else-if="activeTab === 'media'" />
      <FilesList v-else-if="activeTab === 'files'" />
      <LinksList v-else-if="activeTab === 'links'" />

      <!-- ================= Group Settings ================= -->
      <GroupSettings
        v-if="isGroup && activeTab === 'members'"
        :settings="conversation.settings"
        @update="$emit('update-settings', $event)"
      />

      <!-- ================= Actions ================= -->
      <div class="space-y-2 mt-6">
        <!-- Search -->
        <button @click="$emit('trigger-search')" class="w-full text-left px-4 py-2 text-sm text-gray-700
         hover:bg-gray-100 rounded flex items-center transition-colors">
          <svg class="w-5 h-5 mr-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
            />
          </svg>
          Search in conversation
        </button>

        <!-- Mute -->
        <button @click="toggleMute" class="w-full text-left px-4 py-2 text-sm text-gray-700
         hover:bg-gray-100 rounded flex items-center transition-colors">
          <svg class="w-5 h-5 mr-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              v-if="!isMuted"
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11
                 a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341
                 C7.67 6.165 6 8.388 6 11v3.159
                 c0 .538-.214 1.055-.595 1.436L4 17h5
                 m6 0v1a3 3 0 11-6 0v-1m6 0H9"
            />
            <path
              v-else
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M5.586 15H4a1 1 0 01-.707-1.707l1.586-1.586
                 a1 1 0 01.707-.293h3.586a1 1 0 01.707.293l7 7
                 a1 1 0 01-1.414 1.414l-7-7
                 A1 1 0 019.172 13H5.586zM9 9V5a3 3 0 016 0v4
                 M9 9v10m6-10v10"
            />
          </svg>
          {{ isMuted ? "Unmute notifications" : "Mute notifications" }}
        </button>

        <!-- Leave Group -->
        <button
          v-if="isGroup"
          @click="$emit('leave-group')"
          class="w-full text-left px-4 py-2 text-sm rounded flex items-center transition-colors text-yellow-600 hover:bg-yellow-50"
        >
          <svg class="w-5 h-5 mr-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M17 16l4-4m0 0l-4-4m4 4H7
                 m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7
                 a3 3 0 013-3h4a3 3 0 013 3v1"
            />
          </svg>
          Leave group
        </button>

        <!-- Block -->
        <button @click="toggleBlock" class="w-full text-left px-4 py-2 text-sm rounded flex items-center transition-colors text-red-600 hover:bg-red-50">
          <svg class="w-5 h-5 mr-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M18.364 18.364A9 9 0 005.636 5.636
                 m12.728 12.728A9 9 0 015.636 5.636
                 m12.728 12.728L5.636 5.636"
            />
          </svg>
          {{ conversation.isBlocked ? "Unblock user" : "Block user" }}
        </button>
      </div>
    </div>
  </aside>
</template>

<script setup>
import { ref, computed, watch } from "vue";

import GroupMembers from "./GroupMembers.vue";
import MediaGallery from "./MediaGallery.vue";
import FilesList from "./FilesList.vue";
import LinksList from "./LinksList.vue";
import GroupSettings from "./GroupSettings.vue";

const props = defineProps({
  conversation: { type: Object, required: true },
  activeTab: { type: String, default: "members" },
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

const isGroup = computed(() => props.conversation.type === "group");

const avatar = computed(
  () =>
    props.conversation.avatar ||
    props.conversation.members?.[0]?.avatar ||
    "/images/avatar-placeholder.png"
);

const tabs = [
  { label: "Members", value: "members", groupOnly: true },
  { label: "Media", value: "media" },
  { label: "Files", value: "files" },
  { label: "Links", value: "links" },
];

const visibleTabs = computed(() => tabs.filter((tab) => !tab.groupOnly || isGroup.value));

const changeTab = (tab) => emit("update-tab", tab);

const toggleMute = () => (isMuted.value = !isMuted.value);
const toggleBlock = () => console.log("Block toggled");

watch(
  () => isGroup.value,
  (group) => {
    if (!group && props.activeTab === "members") {
      emit("update-tab", "media");
    }
  },
  { immediate: true }
);
</script>

<style>

</style>
