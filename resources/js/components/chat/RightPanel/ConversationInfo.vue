<script setup>
import { ref, computed, watch } from "vue";

import GroupMembers from "./GroupMembers.vue";
import MediaGallery from "./MediaGallery.vue";
import FilesList from "./FilesList.vue";
import LinksList from "./LinksList.vue";
import GroupSettings from "./GroupSettings.vue";
import AboutTab from "./AboutTab.vue";

const props = defineProps({
  conversation: { type: Object, required: true },
  activeTab: { type: String, default: "members" },
  groupMembers: { type: Array, default: () => [] },
  groupMembersPagination: { type: Object, default: () => ({}) },
  loadMoreGroupMembers: { type: Function, required: true },
  conversationMedia: { type: Array, default: () => [] },
  conversationFiles: { type: Array, default: () => [] },
  conversationLinks: { type: Array, default: () => [] },
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
  "toggle-block",
  "toggle-mute",
  "delete-conversation",
  "update-avatar",
  "update-description",
]);

const isMuted = ref(props.conversation.isMuted || false);
const avatarInput = ref(null);

const isGroup = computed(() => props.conversation.type === "group");

const avatar = computed(() => props.conversation.avatar);

// Check if current user can add members based on settings and role
const canAddMembers = computed(() => {
  if (!isGroup.value) return false;

  const settings = props.conversation.settings;
  const role = props.conversation.role;

  // Super admin and admin can always add members
  if (role === "super_admin" || role === "admin") return true;

  // Members can add only if settings allow
  return settings?.allow_members_to_add_remove_participants || false;
});

// Check if current user can remove members
const canRemoveMembers = computed(() => {
  if (!isGroup.value) return false;

  const settings = props.conversation.settings;
  const role = props.conversation.role;

  // Super admin and admin can always remove members
  if (role === "super_admin" || role === "admin") return true;

  // Members can remove only if settings allow
  return settings?.allow_members_to_add_remove_participants || false;
});

// Check if current user can manage admins
const canManageAdmins = computed(() => {
  if (!isGroup.value) return false;

  // Only super admin can manage admins
  return props.conversation.role === "super_admin";
});

// Check if current user can change avatar
const canChangeAvatar = computed(() => {
  if (!isGroup.value) return false;

  const settings = props.conversation.settings;
  const role = props.conversation.role;

  // Super admin and admin can always change avatar
  if (role === "super_admin" || role === "admin") return true;

  // Members can change only if settings allow
  return settings?.allow_members_to_change_group_info || false;
});

// Check if current user can edit description
const canEditDescription = computed(() => {
  if (!isGroup.value) return false;

  const settings = props.conversation.settings;
  const role = props.conversation.role;

  // Super admin and admin can always edit
  if (role === "super_admin" || role === "admin") return true;

  // Members can edit only if settings allow
  return settings?.allow_members_to_change_group_info || false;
});

const tabs = [
  { label: "Members", value: "members", groupOnly: true },
  { label: "Media", value: "media" },
  { label: "Files", value: "files" },
  { label: "Links", value: "links" },
  { label: "About", value: "about", groupOnly: true },
];

const visibleTabs = computed(() => tabs.filter((tab) => !tab.groupOnly || isGroup.value));

const changeTab = (tab) => emit("update-tab", tab);

const toggleMute = () => {
  isMuted.value = !isMuted.value;
  emit("toggle-mute", isMuted.value);
};

const toggleBlock = () => {
  emit("toggle-block", props.conversation.id);
};

const deleteConversation = () => {
  if (
    confirm(
      `Are you sure you want to delete this ${isGroup.value ? "group" : "conversation"}?`
    )
  ) {
    emit("delete-conversation", props.conversation.id);
  }
};

const leaveGroup = () => {
  if (confirm("Are you sure you want to leave this group?")) {
    emit("leave-group");
  }
};

const triggerAvatarUpload = () => {
  avatarInput.value?.click();
};

const handleAvatarChange = (event) => {
  const file = event.target.files?.[0];
  if (file) {
    emit("update-avatar", file);
  }
};

watch(
  () => props.conversation.isMuted,
  (newVal) => {
    isMuted.value = newVal || false;
  }
);

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

<template>
  <aside class="hidden lg:flex lg:flex-col w-80 bg-white border-l border-gray-200 h-full">
    <div class="p-6 overflow-y-auto flex-1">
      <!-- ================= Conversation Info ================= -->
      <div class="text-center mb-6">
        <div class="relative inline-block">
          <img
            :src="avatar"
            :alt="conversation.name"
            class="w-24 h-24 rounded-full mx-auto mb-3 object-cover ring-2 ring-gray-100"
          />

          <!-- Avatar change button for groups -->
          <button
            v-if="isGroup && canChangeAvatar"
            @click="triggerAvatarUpload"
            class="absolute bottom-3 right-0 bg-blue-500 text-white p-2 rounded-full hover:bg-blue-600 transition-colors shadow-lg"
            title="Change group avatar"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"
              />
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"
              />
            </svg>
          </button>

          <!-- Hidden file input -->
          <input
            ref="avatarInput"
            type="file"
            accept="image/*"
            class="hidden"
            @change="handleAvatarChange"
          />
        </div>

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
      <div class="flex border-b border-gray-200 mb-4 overflow-x-auto">
        <button
          v-for="tab in visibleTabs"
          :key="tab.value"
          @click="changeTab(tab.value)"
          :class="[
            'flex-1 py-2 px-2 text-xs sm:text-sm font-medium border-b-2 transition-colors whitespace-nowrap',
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
        :members="groupMembers"
        :pagination="groupMembersPagination"
        :can-add-members="canAddMembers"
        :can-remove-members="canRemoveMembers"
        :can-manage-admins="canManageAdmins"
        :user-role="conversation.role"
        @load-more="loadMoreGroupMembers"
        @add-member="$emit('add-member')"
        @make-admin="$emit('make-admin', $event)"
        @remove-admin="$emit('remove-admin', $event)"
        @remove-member="$emit('remove-member', $event)"
      />

      <MediaGallery
        v-else-if="activeTab === 'media'"
        :media="conversationMedia"
        :conversation-id="conversation.id"
      />

      <FilesList
        v-else-if="activeTab === 'files'"
        :files="conversationFiles"
        :conversation-id="conversation.id"
      />

      <LinksList
        v-else-if="activeTab === 'links'"
        :links="conversationLinks"
        :conversation-id="conversation.id"
      />

      <AboutTab
        v-else-if="isGroup && activeTab === 'about'"
        :description="conversation.settings?.description || ''"
        :can-edit="canEditDescription"
        @update-description="$emit('update-description', $event)"
      />

      <!-- ================= Group Settings ================= -->
      <GroupSettings
        v-if="isGroup && activeTab === 'members' && conversation.role === 'super_admin'"
        :settings="conversation.settings"
        @update="$emit('update-settings', $event)"
      />

      <!-- ================= Actions ================= -->
      <div class="space-y-2 mt-6">
        <!-- Search -->
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

        <!-- Mute -->
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

        <!-- Delete Conversation -->
        <button
          @click="deleteConversation"
          class="w-full text-left px-4 py-2 text-sm rounded flex items-center transition-colors text-red-600 hover:bg-red-50"
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
              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
            />
          </svg>
          Delete {{ isGroup ? "group" : "conversation" }}
        </button>

        <!-- Leave Group -->
        <button
          v-if="isGroup"
          @click="leaveGroup"
          class="w-full text-left px-4 py-2 text-sm rounded flex items-center transition-colors text-yellow-600 hover:bg-yellow-50"
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
              d="M17 16l4-4m0 0l-4-4m4 4H7
                 m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7
                 a3 3 0 013-3h4a3 3 0 013 3v1"
            />
          </svg>
          Leave group
        </button>

        <!-- Block (Private only) -->
        <button
          v-else
          @click="toggleBlock"
          class="w-full text-left px-4 py-2 text-sm rounded flex items-center transition-colors text-red-600 hover:bg-red-50"
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

<style></style>
