<script setup>
import { ref, watch, defineProps } from "vue";
import { Link } from "@inertiajs/inertia-vue3";

const props = defineProps({
  conversations: Object,
});

const conversations = ref([...props.conversations]);

console.log(conversations);
</script>

<template>
  <div class="chat-index">
    <h1 >Conversations</h1>
    <ul v-if="conversations.length">
      <li v-for="conversation in conversations" :key="conversation.id">
        <Link >
          {{ conversation.name }}
        </Link>

        <span v-if="conversation.unread_count > 0">
          ({{ conversation.unread_count }})
        </span>
      </li>
    </ul>

    <p v-else>No conversations found.</p>
  </div>
</template>
