<template>
  <div
    :class="['flex', message.isMine ? 'justify-end' : 'justify-start']"
    :id="`message-${message.id}`"
  >
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
          @click.stop="goToRepliedMessage"
          :class="[
            'text-xs p-2 rounded-t-lg border-l-4 cursor-pointer hover:opacity-80',
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
          @click="emit('show-details')"
          :class="[
            'rounded-lg p-3 shadow-sm cursor-pointer relative group transition-all',
            message.isMine ? 'bg-blue-500 text-white' : 'bg-white text-gray-800',
            message.replyTo ? 'rounded-t-none' : '',
            message.isDeleted ? 'italic opacity-60' : '',
            isHighlighted ? 'ring-4 ring-yellow-400 ring-opacity-50' : '',
          ]"
        >
          <!-- Message Actions (hover) -->
          <MessageActions
            v-if="!message.isDeleted"
            :message="message"
            :is-mine="message.isMine"
            @reply="emit('reply', $event)"
            @edit="emit('edit', $event)"
            @forward="emit('forward', $event)"
            @delete="emit('delete', $event)"
          />

          <!-- Edited Badge -->
          <span
            v-if="message.isEdited && !message.isDeleted"
            class="text-xs opacity-70 mr-2"
            >(edited)</span
          >

          <!-- Text Content with Search Highlighting -->
          <p
            v-if="!message.isDeleted"
            class="text-sm break-words"
            v-html="highlightedText"
          ></p>
          <p v-else class="text-sm">
            {{ message.isMine ? "You deleted this message" : "This message was deleted" }}
          </p>

          <!-- File Attachment -->
          <div v-if="message.file && !message.isDeleted" class="mt-2">
            <!-- Image -->
            <img
              v-if="message.file.type === 'image'"
              :src="message.file.url"
              alt="Image"
              class="rounded-lg max-w-full"
            />

            <!-- Audio -->
            <div
              v-else-if="message.file.type === 'audio'"
              class="flex items-center space-x-3 p-2 rounded-lg bg-black bg-opacity-10"
            >
              <button
                @click="toggleAudioPlayback"
                class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center hover:bg-black hover:bg-opacity-10 transition-colors"
                :class="message.isMine ? 'bg-blue-400' : 'bg-gray-300'"
              >
                <svg
                  v-if="!isPlaying"
                  class="w-5 h-5"
                  :class="message.isMine ? 'text-white' : 'text-gray-700'"
                  fill="currentColor"
                  viewBox="0 0 20 20"
                >
                  <path
                    d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z"
                  />
                </svg>
                <svg
                  v-else
                  class="w-5 h-5"
                  :class="message.isMine ? 'text-white' : 'text-gray-700'"
                  fill="currentColor"
                  viewBox="0 0 20 20"
                >
                  <path
                    fill-rule="evenodd"
                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM7 8a1 1 0 012 0v4a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v4a1 1 0 102 0V8a1 1 0 00-1-1z"
                    clip-rule="evenodd"
                  />
                </svg>
              </button>

              <div class="flex-1">
                <div class="h-1 bg-black bg-opacity-20 rounded-full overflow-hidden">
                  <div
                    class="h-full transition-all duration-300"
                    :class="message.isMine ? 'bg-white' : 'bg-blue-500'"
                    :style="{ width: audioProgress + '%' }"
                  ></div>
                </div>
                <div class="flex justify-between mt-1">
                  <span
                    class="text-xs"
                    :class="message.isMine ? 'text-blue-100' : 'text-gray-600'"
                  >
                    {{ formatAudioTime(currentAudioTime) }}
                  </span>
                  <span
                    class="text-xs"
                    :class="message.isMine ? 'text-blue-100' : 'text-gray-600'"
                  >
                    {{ formatAudioTime(message.file.duration || 0) }}
                  </span>
                </div>
              </div>

              <audio
                ref="audioPlayer"
                :src="message.file.url"
                @timeupdate="updateAudioProgress"
                @ended="audioEnded"
                @loadedmetadata="audioLoaded"
              ></audio>
            </div>

            <!-- Document -->
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

        <!-- Reactions -->
        <MessageReactions
          v-if="!message.isDeleted"
          :reactions="message.reactions || []"
          :align-right="message.isMine"
          :message-id="message.id"
          @add-reaction="emit('add-reaction', $event)"
        />

        <!-- Seen By -->
        <div
          v-if="message.isMine && message.seenBy && message.seenBy.length > 0"
          class="flex justify-end mt-0 mb-0"
          style="margin-top: -25px"
        >
          <button
            @click.stop="emit('show-seen-by')"
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
import { computed, ref, onBeforeUnmount } from "vue";

import MessageActions from "./MessageActions.vue";
import MessageStatus from "./MessageStatus.vue";
import MessageReactions from "./MessageReactions.vue";

const props = defineProps({
  message: { type: Object, required: true },
  isGroup: { type: Boolean, default: false },
  searchQuery: { type: String, default: "" },
  isHighlighted: { type: Boolean, default: false },
});

const emit = defineEmits([
  "show-details",
  "reply",
  "edit",
  "forward",
  "delete",
  "show-seen-by",
  "add-reaction",
  "scroll-to-message",
]);

// Reply navigation
const goToRepliedMessage = () => {
  if (!props.message.replyTo?.id) return;
  emit("scroll-to-message", props.message.replyTo.id);
};

// Audio
const audioPlayer = ref(null);
const isPlaying = ref(false);
const currentAudioTime = ref(0);
const audioProgress = ref(0);

const toggleAudioPlayback = () => {
  if (!audioPlayer.value) return;
  if (isPlaying.value) {
    audioPlayer.value.pause();
    isPlaying.value = false;
  } else {
    audioPlayer.value.play();
    isPlaying.value = true;
  }
};

const updateAudioProgress = () => {
  if (!audioPlayer.value) return;
  currentAudioTime.value = audioPlayer.value.currentTime;
  const duration = audioPlayer.value.duration || 1;
  audioProgress.value = (currentAudioTime.value / duration) * 100;
};

const audioEnded = () => {
  isPlaying.value = false;
  audioProgress.value = 0;
  currentAudioTime.value = 0;
};

const audioLoaded = () => {
  if (audioPlayer.value && props.message.file) {
    props.message.file.duration = Math.floor(audioPlayer.value.duration);
  }
};

const formatAudioTime = (seconds) => {
  const mins = Math.floor(seconds / 60);
  const secs = Math.floor(seconds % 60);
  return `${mins}:${secs.toString().padStart(2, "0")}`;
};

onBeforeUnmount(() => {
  if (audioPlayer.value) audioPlayer.value.pause();
});

// Search highlight
const escapeRegex = (string) => string.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");

const highlightedText = computed(() => {
  if (!props.searchQuery || props.message.isDeleted) return props.message.text;
  const regex = new RegExp(`(${escapeRegex(props.searchQuery)})`, "gi");
  return props.message.text.replace(
    regex,
    '<mark class="bg-yellow-300 text-gray-900 rounded px-1">$1</mark>'
  );
});
</script>

<style scoped>
.ring-4 {
  animation: highlight-pulse 1s ease-in-out;
}

@keyframes highlight-pulse {
  0%,
  100% {
    opacity: 1;
  }
  50% {
    opacity: 0.7;
  }
}
</style>
