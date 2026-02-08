<script setup>
import { ref, computed, watch } from "vue";

import GroupMembers from "./GroupMembers.vue";
import GroupSettings from "./GroupSettings.vue";
import AboutTab from "./AboutTab.vue";

const props = defineProps({
  conversation: { type: Object, required: true },
  activeTab: { type: String, default: "members" },
  groupMembers: { type: Array, default: () => [] },
  groupMembersPagination: { type: Object, default: () => ({}) },
  loadMoreGroupMembers: { type: Function, required: true },
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
  "toggle-mute", // This will now open the mute modal
  "delete-conversation",
  "delete-group",
  "update-avatar",
  "update-description",
  "update-name",
  "open-media-library",
]);

/* ================= STATE ================= */

const isMuted = ref(props.conversation.isMuted || false);
const avatarInput = ref(null);

const isEditingName = ref(false);
const editedName = ref(props.conversation.name);

/* ================= COMPUTED ================= */

const isGroup = computed(() => props.conversation.type === "group");
const avatar = computed(() => props.conversation.avatar);

// Block status computed properties
const isBlockedByMe = computed(() => props.conversation.blockedByMe || false);
const isBlockedByThem = computed(() => props.conversation.blockedByThem || false);
const isAnyBlocked = computed(
  () => props.conversation.isBlocked || isBlockedByMe.value || isBlockedByThem.value
);

const blockStatusText = computed(() => {
  if (isBlockedByMe.value) return "You blocked this user";
  if (isBlockedByThem.value) return "You are blocked.";
  if (props.conversation.isBlocked) return "This conversation is blocked";
  return null;
});

const canEditDescription = computed(() => {
  if (!isGroup.value) return false;

  const role = props.conversation.role;
  const settings = props.conversation.settings;

  if (role === "super_admin" || role === "admin") return true;
  return settings?.allow_members_to_change_group_info || false;
});

const canAddMembers = computed(() => {
  if (!isGroup.value) return false;

  const role = props.conversation.role;
  const settings = props.conversation.settings;

  if (role === "super_admin" || role === "admin") return true;
  return settings?.allow_members_to_add_remove_participants || false;
});

const canRemoveMembers = computed(() => {
  if (!isGroup.value) return false;

  const role = props.conversation.role;
  const settings = props.conversation.settings;

  if (role === "super_admin" || role === "admin") return true;
  return settings?.allow_members_to_add_remove_participants || false;
});

const canManageAdmins = computed(() => {
  return isGroup.value && props.conversation.role === "super_admin";
});

const canChangeAvatar = computed(() => {
  if (!isGroup.value) return false;

  const role = props.conversation.role;
  const settings = props.conversation.settings;

  if (role === "super_admin" || role === "admin") return true;
  return settings?.allow_members_to_change_group_info || false;
});

// Only show tabs for group chats
const tabs = computed(() => {
  if (isGroup.value) {
    return [
      { label: "Members", value: "members" },
      { label: "About", value: "about" },
    ];
  }
  return [];
});

/* ================= METHODS ================= */

const changeTab = (tab) => emit("update-tab", tab);

const triggerAvatarUpload = () => avatarInput.value?.click();

const handleAvatarChange = (e) => {
  const file = e.target.files?.[0];
  if (file) emit("update-avatar", file);
};

// UPDATED: toggleMute now opens modal instead of direct toggle
const toggleMute = () => {
  emit("toggle-mute");
};

const toggleBlock = () => {
  const action = isBlockedByMe.value ? "unblock" : "block";
  const confirmMessage = isBlockedByMe.value
    ? "Are you sure you want to unblock this user?"
    : "Are you sure you want to block this user? You won't be able to send or receive messages.";

  if (confirm(confirmMessage)) {
    emit("toggle-block", props.conversation.id);
  }
};

const leaveGroup = () => {
  emit("leave-group");
};

const deleteConversation = () => {
  emit("delete-conversation", props.conversation.id);
};

const deleteGroup = () => {
  emit("delete-group", props.conversation.id);
};

/* ===== Group Name Edit ===== */

const startEditingName = () => {
  isEditingName.value = true;
  editedName.value = props.conversation.name;
};

const cancelEditingName = () => {
  isEditingName.value = false;
  editedName.value = props.conversation.name;
};

const saveGroupName = () => {
  if (!editedName.value.trim()) return;

  emit("update-name", editedName.value.trim());
  isEditingName.value = false;
};

/* ===== Media Library ===== */

const openMediaLibrary = () => {
  emit("open-media-library");
};

/* ================= WATCHERS ================= */

watch(
  () => props.conversation.name,
  (val) => {
    editedName.value = val;
  }
);

watch(
  () => props.conversation.isMuted,
  (val) => {
    isMuted.value = val || false;
  }
);
</script>

<template>
  <aside class="hidden lg:flex lg:flex-col w-80 bg-white border-l border-gray-200 h-full">
    <div class="p-6 overflow-y-auto flex-1 scrollbar-custom">
      <!-- ================= Avatar ================= -->
      <div class="text-center mb-4">
        <div class="relative inline-block">
          <img :src="avatar" :alt="conversation.name"
            class="w-24 h-24 rounded-full mx-auto object-cover ring-2 ring-gray-100" />

          <!-- Avatar change button (only for groups) -->
          <button v-if="isGroup && canChangeAvatar" @click="triggerAvatarUpload"
            class="absolute bottom-0 right-0 bg-blue-500 text-white p-2 rounded-full hover:bg-blue-600 transition-colors shadow-lg"
            title="Change group avatar">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
          </button>

          <input ref="avatarInput" type="file" accept="image/*" class="hidden" @change="handleAvatarChange" />
        </div>
      </div>

      <!-- ================= Name ================= -->
      <div class="relative text-center mb-2">
        <!-- Edit icon (only for groups) -->
        <button v-if="isGroup && canEditDescription && !isEditingName" @click="startEditingName"
          class="absolute right-0 top-0 text-blue-600 hover:text-blue-700 transition-colors" title="Edit group name">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
          </svg>
        </button>

        <!-- View Mode -->
        <h3 v-if="!isEditingName" class="text-xl font-semibold text-gray-900 truncate px-6">
          {{ conversation.name }}
        </h3>

        <!-- Edit Mode (only for groups) -->
        <div v-else class="space-y-2 px-6">
          <input v-model="editedName" type="text" maxlength="100"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none text-sm text-center"
            placeholder="Enter group name..." />

          <div class="flex justify-center gap-2">
            <button @click="cancelEditingName"
              class="px-3 py-1 text-sm text-gray-600 bg-gray-200 hover:bg-gray-300 rounded transition-colors">
              Cancel
            </button>
            <button @click="saveGroupName" :disabled="!editedName.trim()"
              class="px-3 py-1 text-sm bg-blue-500 text-white rounded hover:bg-blue-600 disabled:bg-gray-300 disabled:cursor-not-allowed transition-colors">
              Save
            </button>
          </div>
        </div>
      </div>

      <!-- ================= Status/Members Count ================= -->
      <p class="text-sm text-center mb-6">
        <template v-if="isGroup">
          <span class="text-gray-500">
            {{ conversation.members?.length || 0 }} members
          </span>
        </template>
        <template v-else>
          <!-- Block Status (for private chats) -->
          <span v-if="blockStatusText" class="text-red-600 font-medium flex items-center justify-center gap-1">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd"
                d="M13.477 14.89A6 6 0 015.11 6.524l8.367 8.368zm1.414-1.414L6.524 5.11a6 6 0 018.367 8.367zM18 10a8 8 0 11-16 0 8 8 0 0116 0z"
                clip-rule="evenodd" />
            </svg>
            {{ blockStatusText }}
          </span>
          <!-- Online Status (if not blocked) -->
          <span v-else :class="conversation.isOnline ? 'text-green-600' : 'text-gray-400'">
            {{
              conversation.isOnline
                ? "Online"
                : conversation.receiver.last_seen
                  ? 'Offline • ' + conversation.receiver.last_seen
                  : 'Offline'
            }}

          </span>
        </template>
      </p>

      <!-- ================= Tabs (Only for Groups) ================= -->
      <div v-if="isGroup && tabs.length > 0" class="flex border-b border-gray-200 mb-4">
        <button v-for="tab in tabs" :key="tab.value" @click="changeTab(tab.value)" :class="[
          'flex-1 py-2 text-sm font-medium border-b-2 transition-colors',
          activeTab === tab.value
            ? 'border-blue-500 text-blue-600'
            : 'border-transparent text-gray-500 hover:text-gray-700',
        ]">
          {{ tab.label }}
        </button>
      </div>

      <!-- ================= Tab Content (Only for Groups) ================= -->

      <GroupMembers v-if="isGroup && activeTab === 'members'" :members="groupMembers"
        :pagination="groupMembersPagination" :can-add-members="canAddMembers" :can-remove-members="canRemoveMembers"
        :can-manage-admins="canManageAdmins" :user-role="conversation.role" @load-more="loadMoreGroupMembers"
        @add-member="$emit('add-member')" @make-admin="$emit('make-admin', $event)"
        @remove-admin="$emit('remove-admin', $event)" @remove-member="$emit('remove-member', $event)" />

      <AboutTab v-if="isGroup && activeTab === 'about'" :description="conversation.settings?.description || ''"
        :created-by="conversation.createdBy" :created-at="conversation.createdAt" :can-edit="canEditDescription"
        @update-description="$emit('update-description', $event)" />

      <GroupSettings v-if="
        isGroup &&
        activeTab === 'members' &&
        (conversation.role === 'super_admin' || conversation.role === 'admin')
      " :settings="conversation.settings" @update="$emit('update-settings', $event)" />

      <!-- ================= Actions ================= -->
      <div class="space-y-2 mt-6">
        <!-- Search -->
        <button @click="$emit('trigger-search')"
          class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded flex items-center transition-colors">
          <svg class="w-5 h-5 mr-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          Search in conversation
        </button>

        <!-- Media & Links -->
        <button @click="openMediaLibrary"
          class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded flex items-center transition-colors">
          <svg class="w-5 h-5 mr-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
          Media & Links
        </button>

        <!-- Mute - UPDATED to show current status -->
        <button @click="toggleMute"
          class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded flex items-center transition-colors">
          <svg class="w-5 h-5 mr-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path v-if="!isMuted" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M5.586 15H4a1 1 0 01-.707-1.707l1.586-1.586a1 1 0 01.707-.293h3.586a1 1 0 01.707.293l7 7a1 1 0 01-1.414 1.414l-7-7A1 1 0 019.172 13H5.586zM9 9V5a3 3 0 016 0v4M9 9v10m6-10v10" />
          </svg>
          <span class="flex-1">
            {{ isMuted ? "Unmute notifications" : "Mute notifications" }}
          </span>
          <!-- Show muted status indicator -->
          <span v-if="isMuted" class="text-xs text-yellow-600 font-medium">
            Muted
          </span>
        </button>

        <!-- Leave Group (only for groups) -->
        <button v-if="isGroup" @click="leaveGroup"
          class="w-full text-left px-4 py-2 text-sm rounded flex items-center transition-colors text-yellow-600 hover:bg-yellow-50">
          <svg class="w-5 h-5 mr-3 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
          </svg>
          Leave group
        </button>

        <!-- Block/Unblock (only for private chats) -->
        <button v-if="!isGroup && !isBlockedByThem" @click="toggleBlock" :class="[
          'w-full text-left px-4 py-2 text-sm rounded flex items-center transition-colors',
          isBlockedByMe
            ? 'text-green-600 hover:bg-green-50'
            : 'text-red-600 hover:bg-red-50',
        ]">
          <svg class="w-5 h-5 mr-3" :class="isBlockedByMe ? 'text-green-600' : 'text-red-600'" fill="none"
            stroke="currentColor" viewBox="0 0 24 24">
            <path v-if="!isBlockedByMe" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
            <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          {{ isBlockedByMe ? "Unblock user" : "Block user" }}
        </button>

        <!-- Blocked by them indicator (read-only) -->
        <div v-if="!isGroup && isBlockedByThem"
          class="w-full text-left px-4 py-2 text-sm rounded flex items-center text-gray-500 bg-gray-50 cursor-not-allowed">
          <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
          </svg>
          Your are blocked.
        </div>

        <!-- Delete Conversation -->
        <button @click="deleteConversation"
          class="w-full text-left px-4 py-2 text-sm rounded flex items-center transition-colors text-red-600 hover:bg-red-50">
          <svg class="w-5 h-5 mr-3 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
          </svg>
          Delete {{ isGroup ? "group" : "conversation" }}
        </button>

        <!-- Delete Group (only for super admin) -->
        <button v-if="isGroup && conversation.role === 'super_admin'" @click="deleteGroup"
          class="w-full text-left px-4 py-2 text-sm rounded flex items-center transition-colors text-red-600 hover:bg-red-50">
          <svg class="w-5 h-5 mr-3 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
          </svg>
          Delete group permanently
        </button>
      </div>
    </div>
  </aside>
</template>

<style scoped>
button {
  cursor: pointer;
}

/* Custom thin scrollbar that appears only on hover */
.scrollbar-custom {
  scrollbar-width: thin;
  scrollbar-color: transparent transparent;
}

.scrollbar-custom:hover {
  scrollbar-color: rgba(156, 163, 175, 0.5) transparent;
}

/* For WebKit browsers (Chrome, Safari, Edge) */
.scrollbar-custom::-webkit-scrollbar {
  width: 6px;
}

.scrollbar-custom::-webkit-scrollbar-track {
  background: transparent;
}

.scrollbar-custom::-webkit-scrollbar-thumb {
  background-color: transparent;
  border-radius: 10px;
  transition: background-color 0.3s;
}

.scrollbar-custom:hover::-webkit-scrollbar-thumb {
  background-color: rgba(156, 163, 175, 0.5);
}

.scrollbar-custom:hover::-webkit-scrollbar-thumb:hover {
  background-color: rgba(107, 114, 128, 0.7);
}
</style>