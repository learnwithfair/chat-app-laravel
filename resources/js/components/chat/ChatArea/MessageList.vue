<template>
  <div class="flex-1 overflow-y-auto p-4 space-y-4" ref="messageContainer">
    <MessageItem
      v-for="message in messages"
      :key="message.id"
      :message="message"
      :is-group="isGroup"
      @reply="$emit('reply', message)"
      @edit="$emit('edit', message)"
      @forward="$emit('forward', message)"
      @delete="$emit('delete', message)"
      @show-reactions="(reaction) => $emit('show-reactions', { message, reaction })"
      @show-details="$emit('show-details', message)"
      @show-seen-by="$emit('show-seen-by', message)"
    />
  </div>
</template>

<script setup>
import { ref } from 'vue';
import MessageItem from './MessageItem.vue';

defineProps(['messages', 'isGroup']);
defineEmits(['reply', 'edit', 'forward', 'delete', 'show-reactions', 'show-details', 'show-seen-by']);

const messageContainer = ref(null);
</script>
