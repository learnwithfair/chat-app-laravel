<template>
  <div class="chat-messages">
    <h1>{{ conversation.name }}</h1>

    <div class="messages">
      <div v-for="message in messages" :key="message.id" class="message">
        <strong>{{ message.sender.name }}:</strong>
        <span>{{ message.message }}</span>
      </div>
    </div>

    <form @submit.prevent="sendMessage">
      <input v-model="newMessage" type="text" placeholder="Type a message..." />
      <button type="submit">Send</button>
    </form>
  </div>
</template>

<script setup>
import { ref } from "vue";
import { Inertia } from "@inertiajs/inertia";
import { usePage } from "@inertiajs/inertia-vue3";

const { props } = usePage();
const conversation = props.value.conversation;
const messages = ref(props.value.messages.data ?? []);
const newMessage = ref("");

const sendMessage = () => {
  if (!newMessage.value.trim()) return;
  Inertia.post(
    route("web.chat.messages.store"),
    {
      conversation_id: conversation.id,
      message: newMessage.value,
    },
    {
      onSuccess: (page) => {
        messages.value.push(page.props.message);
        newMessage.value = "";
      },
    }
  );
};
</script>

<style scoped>
.chat-messages {
  padding: 20px;
}
.messages {
  margin-bottom: 20px;
}
.message {
  margin-bottom: 5px;
}
</style>
