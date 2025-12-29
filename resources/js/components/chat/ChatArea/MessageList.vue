<template>
  <div class="flex-1 overflow-y-auto p-4 space-y-4" ref="messageContainer">
    <MessageItem
      v-for="message in messages"
      :key="message.id"
      :ref="(el) => setMessageRef(message.id, el)"
      :message="message"
      :is-group="isGroup"
      :search-query="searchQuery"
      :is-highlighted="highlightedMessageId === message.id"
      @reply="$emit('reply', message)"
      @edit="$emit('edit', message)"
      @forward="$emit('forward', message)"
      @delete="$emit('delete', message)"
      @show-reactions="(reaction) => $emit('show-reactions', { message, reaction })"
      @show-details="$emit('show-details', message)"
      @show-seen-by="$emit('show-seen-by', message)"
      @add-reaction="$emit('add-reaction', $event)"
    />
  </div>
</template>

<script setup>
import { ref, watch, nextTick, onMounted } from "vue";
import MessageItem from "./MessageItem.vue";

const props = defineProps({
  messages: {
    type: Array,
    required: true,
  },
  isGroup: {
    type: Boolean,
    default: false,
  },
  searchQuery: {
    type: String,
    default: "",
  },
  highlightedMessageId: {
    type: [String, Number],
    default: null,
  },
});

defineEmits([
  "reply",
  "edit",
  "forward",
  "delete",
  "show-reactions",
  "show-details",
  "show-seen-by",
  "add-reaction",
]);

const messageContainer = ref(null);
const messageRefs = ref({});

const setMessageRef = (messageId, el) => {
  if (el) {
    messageRefs.value[messageId] = el;
  }
};

// Watch for highlighted message changes and scroll to it
watch(
  () => props.highlightedMessageId,
  (newId) => {
    if (newId && messageRefs.value[newId]) {
      nextTick(() => {
        const messageElement = messageRefs.value[newId].$el;
        if (messageElement) {
          messageElement.scrollIntoView({
            behavior: "smooth",
            block: "center",
          });
        }
      });
    }
  }
);

// Scroll to bottom on mount
onMounted(() => {
  scrollToBottom();
});

const scrollToBottom = () => {
  if (messageContainer.value) {
    messageContainer.value.scrollTop = messageContainer.value.scrollHeight;
  }
};

// Expose method for parent to trigger scroll
defineExpose({
  scrollToBottom,
});
</script>
