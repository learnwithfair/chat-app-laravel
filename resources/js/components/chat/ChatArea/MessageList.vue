<script setup>
import { ref, watch, nextTick, onMounted, onUnmounted, computed } from "vue";
import MessageItem from "./MessageItem.vue";
import TypingIndicatorMessage from "./TypingIndicatorMessage.vue";

const props = defineProps({
  conversation: { type: Object, required: true },
  messages: { type: Array, required: true },
  messagePagination: Object,
  isGroup: { type: Boolean, default: false },
  searchQuery: { type: String, default: "" },
  highlightedMessageId: [String, Number],
  typingUsers: Array,
});

const emit = defineEmits([
  "reply",
  "edit",
  "forward",
  "delete",
  "show-reactions",
  "show-details",
  "show-seen-by",
  "add-reaction",
  "loadMore",
  "toggle-pin",
  "messages-visible",
]);

/* ================= COMPUTED ================= */

const isGroup = computed(() => props.conversation.type === "group");
const isGroupChat = computed(() => props.isGroup);
const avatar = computed(() => props.conversation.avatar);

const soundEnabled = ref(true);
const messageContainer = ref(null);
const isLoadingMore = ref(false);
const isLoadingForScroll = ref(false);

const messageContainerLeft = ref(0);
const messageContainerWidth = ref(0);

const updateContainerPosition = () => {
  if (messageContainer.value) {
    const rect = messageContainer.value.getBoundingClientRect();
    messageContainerLeft.value = rect.left;
    messageContainerWidth.value = rect.width;
  }
};

// get last seen message for each user
const getLastSeenMessageForEachUser = computed(() => {
  const lastSeenMap = new Map();

  // Reverse loop
  for (let i = props.messages.length - 1; i >= 0; i--) {
    const msg = props.messages[i];

    // check for logged in user
    if (msg.isMine && msg.seenBy && msg.seenBy.length > 0) {
      msg.seenBy.forEach((user) => {
        if (!lastSeenMap.has(user.id)) {
          lastSeenMap.set(user.id, msg.id);
        }
      });
    }
  }

  return lastSeenMap;
});

// get users who last seen this message
const getUsersForMessage = (message) => {
  if (!message.isMine || !message.seenBy || message.seenBy.length === 0) {
    return [];
  }
  const lastSeenMap = getLastSeenMessageForEachUser.value;

  return message.seenBy.filter((user) => {
    return lastSeenMap.get(user.id) === message.id;
  });
};

/* ================= AUTO-SEEN OBSERVER ================= */

let messageObserver = null;

const setupMessageObserver = () => {
  // Cleanup existing observer
  if (messageObserver) {
    messageObserver.disconnect();
  }

  // Only setup if we have an active conversation and container
  if (!props.conversation || !messageContainer.value) {
    return;
  }

  // Create Intersection Observer
  messageObserver = new IntersectionObserver(
    (entries) => {
      const visibleMessageIds = [];

      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          const messageId = parseInt(entry.target.dataset.messageId);
          const isMine = entry.target.dataset.isMine === "true";

          // Only track messages that are NOT mine
          if (!isMine) {
            visibleMessageIds.push(messageId);
          }
        }
      });

      // Emit visible message IDs to parent
      if (visibleMessageIds.length > 0) {
        emit("messages-visible", visibleMessageIds);
      }
    },
    {
      root: messageContainer.value,
      threshold: 0.5, // Message must be 50% visible
      rootMargin: "0px",
    }
  );

  // Observe all message elements
  nextTick(() => {
    const messageElements = messageContainer.value?.querySelectorAll("[data-message-id]");
    messageElements?.forEach((el) => {
      messageObserver.observe(el);
    });
  });
};

onMounted(() => {
  updateContainerPosition();
  window.addEventListener("resize", updateContainerPosition);
  scrollToBottom();

  // Setup auto-seen observer after a short delay
  setTimeout(() => {
    setupMessageObserver();
  }, 500);
});

onUnmounted(() => {
  window.removeEventListener("resize", updateContainerPosition);

  // Cleanup message observer
  if (messageObserver) {
    messageObserver.disconnect();
    messageObserver = null;
  }
});

const onScroll = (e) => {
  const { scrollTop, scrollHeight } = e.target;

  if (
    scrollTop < 100 &&
    props.messagePagination?.hasMore &&
    !props.messagePagination?.loading &&
    !isLoadingMore.value
  ) {
    isLoadingMore.value = true;
    const oldHeight = scrollHeight;

    emit("loadMore");

    nextTick(() => {
      const newHeight = e.target.scrollHeight;
      e.target.scrollTop = newHeight - oldHeight + scrollTop;
    });
  }
};

const pendingScrollTo = ref(null);
const MAX_LOADS = 20;

/**
 * Scroll to a specific message by ID
 * Uses DOM element ID instead of component refs
 */
const scrollToMessage = async (messageId) => {
  pendingScrollTo.value = { id: messageId, tries: 0 };
  isLoadingForScroll.value = true;

  await nextTick();
  await new Promise((r) => setTimeout(r, 500));

  tryScroll();
};

const tryScroll = async () => {
  if (!pendingScrollTo.value) return;
  const { id, tries } = pendingScrollTo.value;

  const exists = props.messages.some((m) => m.id === id);

  if (exists) {
    await nextTick();

    //  FIX: Use document.getElementById to get the actual DOM element
    const el = document.getElementById(`message-${id}`);

    if (el) {
      el.scrollIntoView({ behavior: "smooth", block: "center" });
      pendingScrollTo.value = null;
      isLoadingForScroll.value = false;
      return;
    }
  }

  if (
    tries >= MAX_LOADS ||
    !props.messagePagination?.hasMore ||
    props.messagePagination?.loading
  ) {
    pendingScrollTo.value = null;
    isLoadingForScroll.value = false;
    return;
  }

  pendingScrollTo.value.tries++;
  isLoadingForScroll.value = true;

  await new Promise((resolve) => {
    const stop = watch(
      () => props.messages.length,
      () => {
        stop();
        resolve();
      }
    );
    emit("loadMore");
  });

  await nextTick();
  tryScroll();
};

watch(
  () => props.messages.length,
  async (newLen, oldLen) => {
    if (pendingScrollTo.value) {
      await tryScroll();
      return;
    }

    if (newLen > oldLen && !isLoadingMore.value) {
      nextTick(() => {
        scrollToBottom();
        // Re-setup observer when new messages arrive
        setupMessageObserver();
      });
    }

    isLoadingMore.value = false;
  }
);

// Watch for highlighted message changes and scroll to it
watch(
  () => props.highlightedMessageId,
  (id) => {
    if (id) {
      nextTick(() => {
        const el = document.getElementById(`message-${id}`);
        if (el) {
          el.scrollIntoView({ behavior: "smooth", block: "center" });
        }
      });
    }
  }
);

// Re-setup observer when conversation changes
watch(
  () => props.conversation?.id,
  () => {
    nextTick(() => {
      setupMessageObserver();
    });
  }
);

function scrollToBottom() {
  if (!messageContainer.value) return;

  requestAnimationFrame(() => {
    if (messageContainer.value) {
      messageContainer.value.scrollTop = messageContainer.value.scrollHeight;
    }
  });
}

defineExpose({ scrollToBottom, scrollToMessage, messageContainer });
</script>
<template>
  <div
    class="flex flex-col h-full overflow-y-auto"
    ref="messageContainer"
    @scroll="onScroll"
  >
    <!-- ================= Header ================= -->
    <div class="flex flex-col items-center text-center py-4 shrink-0">
      <!-- Avatar -->
      <div class="relative mb-2">
        <img
          :src="avatar"
          :alt="conversation.name"
          class="w-28 h-28 rounded-full object-cover ring-2 ring-gray-100"
        />
      </div>

      <!-- Name -->
      <h3 class="text-lg font-semibold text-gray-900 truncate max-w-[260px]">
        {{ conversation.name }}
      </h3>

      <!-- Status -->
      <p class="text-sm">
        <template v-if="isGroup">
          <span class="text-gray-500">
            {{ conversation.members?.length || 0 }} members
          </span>
        </template>
        <template v-else>
          <span :class="conversation.isOnline ? 'text-green-600' : 'text-gray-400'">
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
      <div class="border-b bg-gray-400 mt-2 w-75 justify-center"></div>
    </div>

    <!-- ================= Scrollable Messages ================= -->
    <div class="relative flex-1 p-4 space-y-4">
      <!-- Empty State -->
      <div
        v-if="!messages.length && !isLoadingMore"
        class="h-full flex items-center justify-center"
      >
        <div class="text-center text-gray-500">
          <svg
            class="w-24 h-24 mx-auto mb-4 text-gray-300"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"
            />
          </svg>

          <h3 class="text-xl font-semibold mb-1">No messages found</h3>
          <p class="text-sm">Start the conversation by sending a message</p>
        </div>
      </div>

      <!-- Load Older Spinner -->
      <div v-if="isLoadingMore" class="flex justify-center my-3">
        <div class="spinner"></div>
      </div>

      <!-- Messages -->
      <MessageItem
        v-for="message in messages"
        :key="message.id"
        :id="`message-${message.id}`"
        :data-message-id="message.id"
        :data-is-mine="message.isMine"
        :message="message"
        :is-group="isGroup"
        :search-query="searchQuery"
        :is-highlighted="highlightedMessageId === message.id"
        :users-who-last-seen-here="getUsersForMessage(message)"
        @reply="emit('reply', message)"
        @edit="emit('edit', message)"
        @forward="emit('forward', message)"
        @delete="emit('delete', message)"
        @show-reactions="emit('show-reactions', $event)"
        @show-details="emit('show-details', message)"
        @show-seen-by="emit('show-seen-by', message)"
        @add-reaction="emit('add-reaction', $event)"
        @scroll-to-message="scrollToMessage"
        @toggle-pin="emit('toggle-pin', message)"
      />

      <!-- Typing Indicator -->
      <TypingIndicatorMessage
        v-if="typingUsers.length > 0"
        :typing-users="typingUsers"
        :is-group="isGroupChat"
        :enable-sound="soundEnabled"
      />

      <!-- Floating Scroll Spinner -->
      <div
        v-if="isLoadingForScroll"
        class="fixed bottom-36 z-50"
        :style="{
          left: `${messageContainerLeft}px`,
          width: `${messageContainerWidth}px`,
        }"
      >
        <div class="flex justify-center">
          <div class="spinner w-10 h-10"></div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.spinner {
  width: 32px;
  height: 32px;
  border: 4px solid #3b82f6;
  border-top-color: transparent;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% {
    transform: rotate(0deg);
  }

  100% {
    transform: rotate(360deg);
  }
}
</style>
