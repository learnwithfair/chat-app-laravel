<script setup>
import { generateAvatar } from "../../../Utils/Chat/avatarHelper";

defineProps({
  conversation: {
    type: Object,
    required: true,
  },
  isActive: {
    type: Boolean,
    default: false,
  },
});

defineEmits(["select"]);
</script>

<template>
  <div
    @click="$emit('select')"
    :class="[
      'flex items-center p-4 hover:bg-gray-50 cursor-pointer border-b border-gray-100 transition-colors',
      isActive ? 'bg-blue-50' : '',
    ]"
  >
    <!-- Avatar -->
    <div class="relative flex-shrink-0">
      <!-- Group Avatar (Messenger style – 2 members) -->
      <div
        v-if="conversation.type === 'group'"
        class="relative w-12 h-12"
      >
        <img
          v-for="(member, idx) in conversation.members?.slice(0, 2)"
          :key="member.id"
          :src="member.avatar_path || generateAvatar(member?.name)"
          :alt="member.name"
          :class="[
            'absolute w-8 h-8 rounded-full object-cover border-2 border-white',
            idx === 0 ? 'left-0 top-2 z-20' : '',
            idx === 1 ? 'right-0 top-5 z-10' : '',
          ]"
        />
      </div>

      <!-- Single Avatar -->
      <img
        v-else
        :src="conversation.avatar || conversation.members?.[0]?.avatar"
        :alt="conversation.name"
        class="w-12 h-12 rounded-full object-cover"
      />

      <!-- Online Indicator -->
      <span
        v-if="conversation.isOnline && conversation.type === 'private'"
        class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 border-2 border-white rounded-full"
      ></span>

      <!-- Blocked Indicator -->
      <span
        v-if="conversation.isBlocked"
        class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 border-2 border-white rounded-full flex items-center justify-center"
      >
        <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
          <path
            fill-rule="evenodd"
            d="M13.477 14.89A6 6 0 015.11 6.524l8.367 8.368zm1.414-1.414L6.524 5.11a6 6 0 018.367 8.367zM18 10a8 8 0 11-16 0 8 8 0 0116 0z"
            clip-rule="evenodd"
          />
        </svg>
      </span>
    </div>

    <!-- Conversation Info -->
    <div class="flex-1 ml-3 min-w-0">
      <div class="flex items-center justify-between mb-1">
        <h3 class="text-sm font-semibold text-gray-900 truncate">
          {{ conversation.name }}
        </h3>
        <span class="text-xs text-gray-500">{{ conversation.lastMessageTime }}</span>
      </div>
      <div class="flex items-center justify-between">
        <p class="text-sm text-gray-600 truncate">{{ conversation.lastMessage }}</p>
        <span
          v-if="conversation.unreadCount > 0"
          class="ml-2 bg-blue-500 text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center flex-shrink-0"
        >
          {{ conversation.unreadCount }}
        </span>
      </div>
    </div>
  </div>
</template>
