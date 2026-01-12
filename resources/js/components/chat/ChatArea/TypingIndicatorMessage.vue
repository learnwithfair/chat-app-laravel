<template>
  <div class="flex justify-start">
    <div class="flex items-end space-x-2 max-w-xs lg:max-w-md xl:max-w-lg">
      <!-- Avatar (for first typing user) -->
      <img
        v-if="firstUser"
        :src="firstUser.avatar"
        :alt="firstUser.name"
        class="w-8 h-8 rounded-full object-cover flex-shrink-0"
      />

      <div class="flex-1">
        <!-- Sender Name (for group chats) -->
        <p v-if="isGroup && firstUser" class="text-xs text-gray-500 mb-1 ml-1">
          {{ typingText }}
        </p>

        <!-- Typing Bubble -->
        <div class="rounded-xl px-2 py-3 bg-white text-gray-800 shadow-sm">
          <div class="flex items-center space-x-1">
            <div class="typing-dot"></div>
            <div class="typing-dot" style="animation-delay: 0.2s"></div>
            <div class="typing-dot" style="animation-delay: 0.4s"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
  typingUsers: {
    type: Array,
    required: true,
  },
  isGroup: {
    type: Boolean,
    default: false,
  },
});

const firstUser = computed(() => {
  return props.typingUsers && props.typingUsers.length > 0 ? props.typingUsers[0] : null;
});

const typingText = computed(() => {
  if (!props.typingUsers || props.typingUsers.length === 0) return "";

  if (props.typingUsers.length === 1) {
    return props.typingUsers[0].name;
  } else if (props.typingUsers.length === 2) {
    return `${props.typingUsers[0].name} and ${props.typingUsers[1].name}`;
  } else {
    return `${props.typingUsers[0].name} and ${props.typingUsers.length - 1} others`;
  }
});
</script>

<style scoped>
.typing-dot {
  width: 8px;
  height: 8px;
  background-color: #9ca3af;
  border-radius: 50%;
  animation: typing-bounce 1.4s infinite ease-in-out;
}

@keyframes typing-bounce {
  0%,
  60%,
  100% {
    transform: translateY(0);
    opacity: 0.5;
  }
  30% {
    transform: translateY(-10px);
    opacity: 1;
  }
}
</style>
