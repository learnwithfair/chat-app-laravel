<script setup>
import { generateAvatar } from "../../../Utils/Chat/avatarHelper";
import PendingMembers from "./PendingMembers.vue";

const props = defineProps({
  members: { type: Array, default: () => [] },
  pagination: { type: Object, default: () => ({}) },
  canAddMembers: { type: Boolean, default: false },
  canRemoveMembers: { type: Boolean, default: false },
  canManageAdmins: { type: Boolean, default: false },
  userRole: { type: String, default: "member" },

  pendingMembers: { type: Array, default: () => [] },
  showPendingApprovals: { type: Boolean, default: false },
});

const emit = defineEmits([
  "load-more",
  "add-member",
  "make-admin",
  "remove-admin",
  "remove-member",
  "approve-member",
  "reject-member",
]);

const formatRole = (role) => {
  if (!role) return "";
  return role.replace(/_/g, " ").replace(/\b\w/g, (char) => char.toUpperCase());
};

const canModifyMember = (member) => {
  // Super admin cannot be modified
  if (member.role === "super_admin") return false;

  // Super admin can modify anyone (except other super admins)
  if (props.userRole === "super_admin") return true;

  // Admin can only modify members, not other admins
  if (props.userRole === "admin" && member.role === "member") return true;

  return false;
};

const canPromoteToAdmin = (member) => {
  return props.canManageAdmins && member.role === "member";
};

const canDemoteAdmin = (member) => {
  return props.canManageAdmins && member.role === "admin";
};
</script>

<template>
  <div class="mb-6">
    <button
      v-if="canAddMembers"
      @click="$emit('add-member')"
      class="w-full mb-4 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors flex items-center justify-center space-x-2"
    >
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M12 4v16m8-8H4"
        />
      </svg>
      <span>Add Member</span>
    </button>

    <h4 class="text-sm font-semibold text-gray-700 mb-3">
      Members ({{ members.length }})
    </h4>

    <!-- <PendingMembers
      v-if="showPendingApprovals && pendingMembers.length > 0"
      :members="pendingMembers"
      @approve="$emit('approve-member', $event)"
      @reject="$emit('reject-member', $event)"
    /> -->

    <div class="space-y-2">
      <div
        v-for="member in members"
        :key="member.id"
        class="flex items-center justify-between p-2 hover:bg-gray-50 rounded group"
      >
        <div class="flex items-center flex-1 min-w-0">
          <img
            :src="member.avatar_path || generateAvatar(member?.name)"
            :alt="member.name"
            class="w-8 h-8 rounded-full object-cover flex-shrink-0"
          />
          <div class="ml-2 min-w-0 flex-1">
            <p class="text-sm font-medium text-gray-900 truncate">{{ member.name }}</p>
            <p class="text-xs text-gray-500">{{ formatRole(member.role) }}</p>
          </div>
        </div>

        <!-- Member Actions -->
        <div
          v-if="canModifyMember(member)"
          class="hidden group-hover:flex items-center space-x-1 flex-shrink-0 ml-2"
        >
          <!-- Make Admin Button -->
          <button
            v-if="canPromoteToAdmin(member)"
            @click="$emit('make-admin', member)"
            class="p-1 text-blue-600 hover:bg-blue-50 rounded"
            title="Make Admin"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
              />
            </svg>
          </button>

          <!-- Remove Admin Button -->
          <button
            v-if="canDemoteAdmin(member)"
            @click="$emit('remove-admin', member)"
            class="p-1 text-yellow-600 hover:bg-yellow-50 rounded"
            title="Remove Admin"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M18 12H6"
              />
            </svg>
          </button>

          <!-- Remove Member Button -->
          <button
            v-if="canRemoveMembers"
            @click="$emit('remove-member', member)"
            class="p-1 text-red-600 hover:bg-red-50 rounded"
            title="Remove Member"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
              />
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Load More Button -->
    <button
      v-if="pagination.hasMore"
      @click="$emit('load-more')"
      :disabled="pagination.loading"
      class="w-full mt-4 px-4 py-2 text-sm text-blue-600 hover:bg-blue-50 rounded transition-colors disabled:opacity-50"
    >
      {{ pagination.loading ? "Loading..." : "Load More" }}
    </button>
  </div>
</template>

<style scoped>
button {
  cursor: pointer;
}

button:disabled {
  cursor: not-allowed;
}
</style>
