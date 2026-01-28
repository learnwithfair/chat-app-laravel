<script setup>
import { generateAvatar } from "../../../Utils/Chat/avatarHelper";

defineProps(["members", "is_admin"]);
defineEmits(["add-member", "make-admin", "remove-admin", "remove-member"]);

const formatRole = (role) => {
  if (!role) return "";
  return role.replace(/_/g, " ").replace(/\b\w/g, (char) => char.toUpperCase());
};
</script>
<template>
  <div class="mb-6">
    <button
      v-if="is_admin"
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

    <h4 class="text-sm font-semibold text-gray-700 mb-3">Members</h4>
    <div class="space-y-2">
      <div
        v-for="member in members"
        :key="member.id"
        class="flex items-center justify-between p-2 hover:bg-gray-50 rounded group"
      >
        <div class="flex items-center">
          <img
            :src="member.avatar_path || generateAvatar(member?.name)"
            :alt="member.name"
            class="w-8 h-8 rounded-full object-cover"
          />
          <div class="ml-2">
            <p class="text-sm font-medium text-gray-900">{{ member.name }}</p>
            <p class="text-xs text-gray-500">{{ formatRole(member.role) }}</p>
          </div>
        </div>

        <!-- Member Actions -->
        <div v-if="is_admin" class="hidden group-hover:flex items-center space-x-1">
          <button
            v-if="member.role === 'member'"
            @click="$emit('make-admin', member)"
            class="p-1 text-blue-600 hover:bg-blue-50 rounded"
            title="Make Admin"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M5 13l4 4L19 7"
              />
            </svg>
          </button>
          <button
            v-if="member.role === 'admin'"
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
          <button
            v-if="member.role !== 'super_admin'"
            @click="$emit('remove-member', member)"
            class="p-1 text-red-600 hover:bg-red-50 rounded"
            title="Remove"
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
  </div>
</template>
<style scoped>
button {
  cursor: pointer;
}
</style>
