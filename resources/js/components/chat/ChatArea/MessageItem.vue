<template>
  <div :class="['flex', message.isMine ? 'justify-end' : 'justify-start']">
    <div
      :class="[
        'max-w-xs lg:max-w-md xl:max-w-lg',
        !message.isMine && 'flex items-end space-x-2',
      ]"
    >
      <!-- Other user avatar -->
      <img
        v-if="!message.isMine"
        :src="message.senderAvatar"
        :alt="message.senderName"
        class="w-8 h-8 rounded-full object-cover flex-shrink-0"
      />

      <div class="flex-1">
        <!-- Sender Name (for group chats) -->
        <p v-if="!message.isMine && isGroup" class="text-xs text-gray-500 mb-1 ml-1">
          {{ message.senderName }}
        </p>

        <!-- Reply Preview -->
        <div
          v-if="message.replyTo"
          :class="[
            'text-xs p-2 rounded-t-lg border-l-4',
            message.isMine
              ? 'bg-blue-100 border-blue-500'
              : 'bg-gray-200 border-gray-500',
          ]"
        >
          <p class="font-semibold">{{ message.replyTo.senderName }}</p>
          <p class="text-gray-600 truncate">{{ message.replyTo.text }}</p>
        </div>

        <!-- Message Bubble -->
        <div
          @click="$emit('show-details')"
          :class="[
            'rounded-lg p-3 shadow-sm cursor-pointer relative group',
            message.isMine ? 'bg-blue-500 text-white' : 'bg-white text-gray-800',
            message.replyTo ? 'rounded-t-none' : '',
            message.isDeleted ? 'italic opacity-60' : '',
          ]"
        >
          <!-- Message Actions (hover) -->
          <MessageActions
            v-if="!message.isDeleted"
            :message="message"
            :is-mine="message.isMine"
            @reply="$emit('reply')"
            @edit="$emit('edit')"
            @forward="$emit('forward')"
            @delete="$emit('delete')"
          />

          <!-- Edited Badge -->
          <span
            v-if="message.isEdited && !message.isDeleted"
            class="text-xs opacity-70 mr-2"
            >(edited)</span
          >

          <!-- Text Content -->
          <p v-if="!message.isDeleted" class="text-sm break-words">{{ message.text }}</p>
          <p v-else class="text-sm">
            {{ message.isMine ? "You deleted this message" : "This message was deleted" }}
          </p>

          <!-- File Attachment -->
          <div v-if="message.file && !message.isDeleted" class="mt-2">
            <img
              v-if="message.file.type === 'image'"
              :src="message.file.url"
              alt="Image"
              class="rounded-lg max-w-full"
            />
            <div
              v-else
              class="flex items-center space-x-2 p-2 bg-black bg-opacity-10 rounded"
            >
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path
                  fill-rule="evenodd"
                  d="M8 4a3 3 0 00-3 3v4a5 5 0 0010 0V7a1 1 0 112 0v4a7 7 0 11-14 0V7a5 5 0 0110 0v4a3 3 0 11-6 0V7a1 1 0 012 0v4a1 1 0 102 0V7a3 3 0 00-3-3z"
                  clip-rule="evenodd"
                />
              </svg>
              <span class="text-sm">{{ message.file.name }}</span>
            </div>
          </div>

          <!-- Time & Status Inside Card -->
          <div class="flex items-center justify-between mt-2">
            <span
              :class="['text-xs', message.isMine ? 'text-blue-100' : 'text-gray-500']"
            >
              {{ message.time }}
            </span>

            <MessageStatus
              v-if="message.isMine && !message.isDeleted"
              :status="message.status"
              :is-mine="message.isMine"
            />
          </div>
        </div>
        <!-- Reactions (Messenger Style) -->
        <MessageReactions
          v-if="!message.isDeleted"
          :reactions="message.reactions || []"
          :align-right="message.isMine"
          :message-id="message.id"
          @add-reaction="handleAddReaction"
        />
        <!-- Seen By Avatars (Below message, right aligned) -->
        <div
          v-if="message.isMine && message.seenBy && message.seenBy.length > 0"
          class="flex justify-end"
          style="margin-top: -20px;"
        >
          <button
            @click.stop="$emit('show-seen-by')"
            class="flex -space-x-2 hover:opacity-80 transition-opacity"
          >
            <img
              v-for="user in message.seenBy.slice(0, 3)"
              :key="user.id"
              :src="user.avatar"
              :alt="user.name"
              class="w-5 h-5 rounded-full border-2 border-white object-cover"
              :title="user.name"
            />
            <span v-if="message.seenBy.length > 3" class="text-xs text-gray-500 ml-1">
              +{{ message.seenBy.length - 3 }}
            </span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import MessageActions from "./MessageActions.vue";
import MessageStatus from "./MessageStatus.vue";
import MessageReactions from "./MessageReactions.vue";

const props = defineProps({
  message: {
    type: Object,
    required: true,
  },
  isGroup: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits([
  "show-details",
  "reply",
  "edit",
  "forward",
  "delete",
  "show-seen-by",
  "add-reaction",
]);

const handleAddReaction = (data) => {
  emit("add-reaction", data);
};
</script>
