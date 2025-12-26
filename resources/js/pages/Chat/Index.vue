<template>
  <div class="chat-index">
    <h1>Conversations</h1>
    <ul>
      <li v-for="conversation in conversations" :key="conversation.id">
        <inertia-link :href="route('web.chat.messages', conversation.id)">
          {{ conversation.name }}
        </inertia-link>
        <span v-if="conversation.unread_count > 0">({{ conversation.unread_count }})</span>
      </li>
    </ul>
  </div>
</template>

<script setup>
import { usePage } from '@inertiajs/inertia-vue3'
const { props } = usePage()
const conversations = props.value.conversations ?? []
</script>

<style scoped>
.chat-index { padding: 20px; }
</style>
