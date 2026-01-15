<template>
  <div
    class="flex-1 overflow-y-auto p-4 space-y-4"
    ref="messageContainer"
    @scroll="onScroll"
  >
    <!-- Loading indicator -->
    <div v-if="messagePagination?.loading" class="loading-indicator">
      <div class="spinner"></div>
      <span>Loading older messages...</span>
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
    />

    <!-- Typing Indicator -->
    <TypingIndicatorMessage
      v-if="typingUsers?.length"
      :typing-users="typingUsers"
      :is-group="isGroup"
    />
  </div>
</template>

<script setup>
import { ref, watch, nextTick, onMounted } from "vue";
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

watch(
  () => props.messages.length,
  (newLen, oldLen) => {
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
