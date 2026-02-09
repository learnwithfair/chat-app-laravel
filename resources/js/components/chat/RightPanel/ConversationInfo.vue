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
  "toggle-mute",
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

const showShareMenu = ref(false);
const copySuccess = ref(false);

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

/* ===== Share Functions ===== */

const toggleShareMenu = () => {
  showShareMenu.value = !showShareMenu.value;
};

const copyInviteLink = async () => {
  const textToCopy = props.conversation.inviteLink;

  try {
    // Try modern API
    if (navigator.clipboard && navigator.clipboard.writeText) {
      await navigator.clipboard.writeText(textToCopy);
    } else {
      // Fallback method
      const textArea = document.createElement("textarea");
      textArea.value = textToCopy;
      textArea.style.position = "fixed";
      textArea.style.left = "-999999px";
      document.body.appendChild(textArea);
      textArea.select();
      document.execCommand("copy");
      document.body.removeChild(textArea);
    }

    // Show success state
    copySuccess.value = true;
    setTimeout(() => {
      copySuccess.value = false;
    }, 2000);
  } catch (err) {
    console.error("Copy failed:", err);
    // Fallback to prompt
    prompt("Copy this invite link:", textToCopy);
  }
};

const shareVia = (platform) => {
  const inviteLink = encodeURIComponent(props.conversation.inviteLink);
  const text = encodeURIComponent(`Join our group: ${props.conversation.name}`);

  let shareUrl = "";

  switch (platform) {
    case "whatsapp":
      shareUrl = `https://wa.me/?text=${text}%20${inviteLink}`;
      break;
    case "telegram":
      shareUrl = `https://t.me/share/url?url=${inviteLink}&text=${text}`;
      break;
    case "facebook":
      shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${inviteLink}`;
      break;
    case "twitter":
      shareUrl = `https://twitter.com/intent/tweet?text=${text}&url=${inviteLink}`;
      break;
    case "messenger":
      shareUrl = `https://www.facebook.com/dialog/send?link=${inviteLink}&app_id=YOUR_APP_ID&redirect_uri=${inviteLink}`;
      break;
    case "email":
      shareUrl = `mailto:?subject=${encodeURIComponent(
        props.conversation.name
      )}&body=${text}%20${inviteLink}`;
      break;
  }

  if (shareUrl) {
    window.open(shareUrl, "_blank", "noopener,noreferrer");
    showShareMenu.value = false;
  }
};

// Close share menu when clicking outside
const handleClickOutside = (event) => {
  const shareButton = event.target.closest(".share-button");
  const shareMenu = event.target.closest(".share-menu");

  if (!shareButton && !shareMenu && showShareMenu.value) {
    showShareMenu.value = false;
  }
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

// Add click listener for closing share menu
watch(showShareMenu, (isOpen) => {
  if (isOpen) {
    document.addEventListener("click", handleClickOutside);
  } else {
    document.removeEventListener("click", handleClickOutside);
  }
});
</script>

<template>
  <aside class="hidden lg:flex lg:flex-col w-80 bg-white border-l border-gray-200 h-full">
    <div class="p-6 overflow-y-auto flex-1 scrollbar-custom">
      <!-- ================= Avatar ================= -->
      <div class="text-center mb-4">
        <div class="relative inline-block">
          <img
            :src="avatar"
            :alt="conversation.name"
            class="w-24 h-24 rounded-full mx-auto object-cover ring-2 ring-gray-100"
          />

          <!-- Avatar change button (only for groups) -->
          <button
            v-if="isGroup && canChangeAvatar"
            @click="triggerAvatarUpload"
            class="absolute bottom-0 right-0 bg-blue-500 text-white p-2 rounded-full hover:bg-blue-600 transition-colors shadow-lg"
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

          <input
            ref="avatarInput"
            type="file"
            accept="image/*"
            class="hidden"
            @change="handleAvatarChange"
          />
        </div>
      </div>

      <!-- ================= Name ================= -->
      <div class="relative text-center mb-2">
        <!-- Action buttons -->
        <div class="absolute right-0 top-0 flex items-center gap-2">
          <!-- Share icon with dropdown -->
          <div class="relative">
            <button
              @click="toggleShareMenu"
              class="share-button p-1.5 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all duration-200"
              :title="copySuccess ? 'Copied!' : 'Share invite link'"
            >
              <!-- Check icon when copied -->
              <svg
                v-if="copySuccess"
                class="w-4 h-4 text-green-600"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M5 13l4 4L19 7"
                />
              </svg>

              <!-- Share icon normally -->
              <svg
                v-else
                class="w-4 h-4"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"
                />
              </svg>
            </button>

            <!-- Share Menu Dropdown -->
            <transition
              enter-active-class="transition ease-out duration-100"
              enter-from-class="transform opacity-0 scale-95"
              enter-to-class="transform opacity-100 scale-100"
              leave-active-class="transition ease-in duration-75"
              leave-from-class="transform opacity-100 scale-100"
              leave-to-class="transform opacity-0 scale-95"
            >
              <div
                v-show="showShareMenu"
                class="share-menu absolute right-0 top-full mt-2 w-56 bg-white rounded-lg shadow-lg border border-gray-200 py-2 z-50"
              >
                <!-- Copy Link -->
                <button
                  @click="copyInviteLink"
                  class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-3 transition-colors"
                >
                  <svg
                    class="w-5 h-5 text-gray-600"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"
                    />
                  </svg>
                  <span>Copy link</span>
                </button>

                <div class="border-t border-gray-100 my-1"></div>

                <!-- WhatsApp -->
                <button
                  @click="shareVia('whatsapp')"
                  class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-3 transition-colors"
                >
                  <svg
                    class="w-5 h-5 text-green-600"
                    fill="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"
                    />
                  </svg>
                  <span>WhatsApp</span>
                </button>

                <!-- Telegram -->
                <button
                  @click="shareVia('telegram')"
                  class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-3 transition-colors"
                >
                  <svg
                    class="w-5 h-5 text-blue-500"
                    fill="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"
                    />
                  </svg>
                  <span>Telegram</span>
                </button>

                <!-- Facebook -->
                <button
                  @click="shareVia('facebook')"
                  class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-3 transition-colors"
                >
                  <svg
                    class="w-5 h-5 text-blue-700"
                    fill="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"
                    />
                  </svg>
                  <span>Facebook</span>
                </button>

                <!-- Twitter -->
                <button
                  @click="shareVia('twitter')"
                  class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-3 transition-colors"
                >
                  <svg
                    class="w-5 h-5 text-sky-500"
                    fill="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"
                    />
                  </svg>
                  <span>Twitter</span>
                </button>

                <!-- Email -->
                <button
                  @click="shareVia('email')"
                  class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-3 transition-colors"
                >
                  <svg
                    class="w-5 h-5 text-gray-600"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                    />
                  </svg>
                  <span>Email</span>
                </button>
              </div>
            </transition>
          </div>

          <!-- Edit icon (only for groups) -->
          <button
            v-if="isGroup && canEditDescription && !isEditingName"
            @click="startEditingName"
            class="p-1.5 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all duration-200"
            title="Edit group name"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
              />
            </svg>
          </button>
        </div>

        <!-- View Mode -->
        <h3
          v-if="!isEditingName"
          class="text-xl font-semibold text-gray-900 truncate px-12"
        >
          {{ conversation.name }}
        </h3>

        <!-- Edit Mode (only for groups) -->
        <div v-else class="space-y-2 px-12">
          <input
            v-model="editedName"
            type="text"
            maxlength="100"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none text-sm text-center"
            placeholder="Enter group name..."
          />

          <div class="flex justify-center gap-2">
            <button
              @click="cancelEditingName"
              class="px-3 py-1 text-sm text-gray-600 bg-gray-200 hover:bg-gray-300 rounded transition-colors"
            >
              Cancel
            </button>
            <button
              @click="saveGroupName"
              :disabled="!editedName.trim()"
              class="px-3 py-1 text-sm bg-blue-500 text-white rounded hover:bg-blue-600 disabled:bg-gray-300 disabled:cursor-not-allowed transition-colors"
            >
              Save
            </button>
          </div>
        </div>
      </div>

      <!-- Rest of your template remains the same -->
      <!-- ================= Status/Members Count ================= -->
      <p class="text-sm text-center mb-6">
        <template v-if="isGroup">
          <span class="text-gray-500">
            {{ conversation.members?.length || 0 }} members
          </span>
        </template>
        <template v-else>
          <!-- Block Status (for private chats) -->
          <span
            v-if="blockStatusText"
            class="text-red-600 font-medium flex items-center justify-center gap-1"
          >
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
              <path
                fill-rule="evenodd"
                d="M13.477 14.89A6 6 0 015.11 6.524l8.367 8.368zm1.414-1.414L6.524 5.11a6 6 0 018.367 8.367zM18 10a8 8 0 11-16 0 8 8 0 0116 0z"
                clip-rule="evenodd"
              />
            </svg>
            {{ blockStatusText }}
          </span>
          <!-- Online Status (if not blocked) -->
          <span
            v-else
            :class="conversation.isOnline ? 'text-green-600' : 'text-gray-400'"
          >
            {{
              conversation.isOnline
                ? "Online"
                : conversation.receiver.last_seen
                ? "Offline • " + conversation.receiver.last_seen
                : "Offline"
            }}
          </span>
        </template>
      </p>

      <!-- ================= Tabs (Only for Groups) ================= -->
      <div v-if="isGroup && tabs.length > 0" class="flex border-b border-gray-200 mb-4">
        <button
          v-for="tab in tabs"
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

      <!-- ================= Tab Content (Only for Groups) ================= -->

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

      <AboutTab
        v-if="isGroup && activeTab === 'about'"
        :description="conversation.settings?.description || ''"
        :created-by="conversation.createdBy"
        :created-at="conversation.createdAt"
        :can-edit="canEditDescription"
        @update-description="$emit('update-description', $event)"
      />

      <GroupSettings
        v-if="
          isGroup &&
          activeTab === 'members' &&
          (conversation.role === 'super_admin' || conversation.role === 'admin')
        "
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

        <!-- Media & Links -->
        <button
          @click="openMediaLibrary"
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
              d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
            />
          </svg>
          Media & Links
        </button>

        <!-- Mute - UPDATED to show current status -->
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
          <span class="flex-1">
            {{ isMuted ? "Unmute notifications" : "Mute notifications" }}
          </span>
          <!-- Show muted status indicator -->
          <span v-if="isMuted" class="text-xs text-yellow-600 font-medium"> Muted </span>
        </button>

        <!-- Leave Group (only for groups) -->
        <button
          v-if="isGroup"
          @click="leaveGroup"
          class="w-full text-left px-4 py-2 text-sm rounded flex items-center transition-colors text-yellow-600 hover:bg-yellow-50"
        >
          <svg
            class="w-5 h-5 mr-3 text-yellow-600"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
            />
          </svg>
          Leave group
        </button>

        <!-- Block/Unblock (only for private chats) -->
        <button
          v-if="!isGroup && !isBlockedByThem"
          @click="toggleBlock"
          :class="[
            'w-full text-left px-4 py-2 text-sm rounded flex items-center transition-colors',
            isBlockedByMe
              ? 'text-green-600 hover:bg-green-50'
              : 'text-red-600 hover:bg-red-50',
          ]"
        >
          <svg
            class="w-5 h-5 mr-3"
            :class="isBlockedByMe ? 'text-green-600' : 'text-red-600'"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              v-if="!isBlockedByMe"
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"
            />
            <path
              v-else
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
            />
          </svg>
          {{ isBlockedByMe ? "Unblock user" : "Block user" }}
        </button>

        <!-- Blocked by them indicator (read-only) -->
        <div
          v-if="!isGroup && isBlockedByThem"
          class="w-full text-left px-4 py-2 text-sm rounded flex items-center text-gray-500 bg-gray-50 cursor-not-allowed"
        >
          <svg
            class="w-5 h-5 mr-3 text-gray-400"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"
            />
          </svg>
          Your are blocked.
        </div>

        <!-- Delete Conversation -->
        <button
          @click="deleteConversation"
          class="w-full text-left px-4 py-2 text-sm rounded flex items-center transition-colors text-red-600 hover:bg-red-50"
        >
          <svg
            class="w-5 h-5 mr-3 text-red-600"
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

        <!-- Delete Group (only for super admin) -->
        <button
          v-if="isGroup && conversation.role === 'super_admin'"
          @click="deleteGroup"
          class="w-full text-left px-4 py-2 text-sm rounded flex items-center transition-colors text-red-600 hover:bg-red-50"
        >
          <svg
            class="w-5 h-5 mr-3 text-red-600"
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
