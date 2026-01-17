<template>
  <!-- Messages container -->
  <div
    class="relative flex-1 overflow-y-auto p-4 space-y-4"
    ref="messageContainer"
    @scroll="onScroll"
  >
    <!-- Spinner shown when loading older messages (prepend) -->
    <div v-if="isLoadingMore" class="flex justify-center mb-2">
      <div
        class="spinner w-8 h-8 border-4 border-blue-400 border-t-transparent rounded-full animate-spin"
      ></div>
    </div>

    <!-- Messages -->
    <MessageItem
      v-for="message in messages"
      :key="message.id"
      :ref="(el) => setMessageRef(message.id, el)"
      :message="message"
      :is-group="isGroup"
      :search-query="searchQuery"
      :is-highlighted="highlightedMessageId === message.id"
      @reply="emit('reply', message)"
      @edit="emit('edit', message)"
      @forward="emit('forward', message)"
      @delete="emit('delete', message)"
      @show-reactions="(reaction) => emit('show-reactions', { message, reaction })"
      @show-details="emit('show-details', message)"
      @show-seen-by="emit('show-seen-by', message)"
      @add-reaction="emit('add-reaction', $event)"
      @scroll-to-message="scrollToMessage"
    />

    <!-- Typing Indicator -->
    <TypingIndicatorMessage
      v-if="typingUsers?.length"
      :typing-users="typingUsers"
      :is-group="isGroup"
    />
    <!-- Spinner shown when auto-scrolling to a reply -->
    <div
      v-if="isLoadingForScroll"
      class="fixed bottom-40 z-50"
      :style="{ left: `${messageContainerLeft}px`, width: `${messageContainerWidth}px` }"
    >
      <div class="flex justify-center">
        <div
          class="spinner w-10 h-10 border-4 border-blue-400 border-t-transparent rounded-full animate-spin shadow-lg"
        ></div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, nextTick, onMounted, onUnmounted } from "vue";
import MessageItem from "./MessageItem.vue";
import TypingIndicatorMessage from "./TypingIndicatorMessage.vue";

const props = defineProps({
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
]);

const messageContainer = ref(null);
const messageRefs = ref({});
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

onMounted(() => {
  updateContainerPosition();
  window.addEventListener("resize", updateContainerPosition);
});

// Optional: Clean up on unmount
onUnmounted(() => {
  window.removeEventListener("resize", updateContainerPosition);
});

const onScroll = (e) => {
  const { scrollTop, scrollHeight } = e.target;

  // console.log(props.messagePagination);
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

const scrollToMessage = async (messageId) => {
  pendingScrollTo.value = { id: messageId, tries: 0 };

  // Show the floating spinner at bottom of container (not top)
  isLoadingForScroll.value = true;

  // Wait for Vue to render spinner
  await nextTick();
  await new Promise((r) => setTimeout(r, 500));

  // Start recursive scroll
  tryScroll();
};

const tryScroll = async () => {
  if (!pendingScrollTo.value) return;
  const { id, tries } = pendingScrollTo.value;

  // 1️ Check if message exists in DATA
  const exists = props.messages.some((m) => m.id === id);

  if (exists) {
    await nextTick();
    const el = document.getElementById(`message-${id}`);
    if (el) {
      el.scrollIntoView({ behavior: "smooth", block: "center" });
      pendingScrollTo.value = null;
      isLoadingForScroll.value = false;
      return;
    }
  }

  // 2️ Stop if too many attempts or no more pages
  if (
    tries >= MAX_LOADS ||
    !props.messagePagination?.hasMore ||
    props.messagePagination?.loading
  ) {
    pendingScrollTo.value = null;
    isLoadingForScroll.value = false;
    return;
  }

  // 3️ Load next page and wait for messages to arrive
  pendingScrollTo.value.tries++;
  isLoadingForScroll.value = true;

  // Emit loadMore and wait until messages length changes
  await new Promise((resolve) => {
    const stop = watch(
      () => props.messages.length,
      () => {
        stop(); // stop watching
        resolve();
      }
    );
    emit("loadMore");
  });

  // 4️ Try scroll again after new messages rendered
  await nextTick();
  tryScroll();
};

watch(
  () => props.messages.length,
  async (newLen, oldLen) => {
    // 1️ If a pending scroll is active, try again
    if (pendingScrollTo.value) {
      await tryScroll();
      return; // skip auto scroll to bottom
    }

    // 2️ Otherwise, auto-scroll to bottom if new messages arrive
    if (newLen > oldLen && !isLoadingMore.value) {
      nextTick(scrollToBottom);
    }

    isLoadingMore.value = false;
  }
);

const setMessageRef = (id, el) => {
  if (el) messageRefs.value[id] = el;
};

watch(
  () => props.highlightedMessageId,
  (id) => {
    const el = messageRefs.value[id]?.$el;
    if (el) {
      nextTick(() => el.scrollIntoView({ behavior: "smooth", block: "center" }));
    }
  }
);

onMounted(scrollToBottom);

function scrollToBottom() {
  if (messageContainer.value) {
    messageContainer.value.scrollTop = messageContainer.value.scrollHeight;
  }
}

defineExpose({ scrollToBottom });
</script>

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
