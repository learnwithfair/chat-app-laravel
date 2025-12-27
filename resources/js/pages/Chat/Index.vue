<script setup>
import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";
import ChatSidebar from "./Components/ChatSidebar.vue";
import ChatWindow from "./Components/ChatWindow.vue";
import ChatInfo from "./Components/ChatInfo.vue";

const props = defineProps({
  conversations: {
    type: Object,
    required: true,
  },
  activeConversation: {
    type: Object,
    default: null,
  },
  messages: {
    type: Object,
    default: null,
  },
});

const showInfo = ref(false);
const searchQuery = ref("");

const conversationList = computed(() => 
  props.conversations.data?.map(item => item.data) ?? []
);

const selectConversation = (conversationId) => {
  router.visit(`/chat/${conversationId}`, {
    preserveState: true,
    preserveScroll: true,
  });
};

const toggleInfo = () => {
  showInfo.value = !showInfo.value;
};
</script>

<template>
  <div class="chat-container">
    <!-- Sidebar -->
    <ChatSidebar
      :conversations="conversationList"
      :active-conversation-id="activeConversation?.id"
      :pagination="conversations"
      @select="selectConversation"
      v-model:search="searchQuery"
    />

    <!-- Chat Window -->
    <ChatWindow
      v-if="activeConversation"
      :conversation="activeConversation"
      :messages="messages"
      @toggle-info="toggleInfo"
    />
    <div v-else class="no-chat-selected">
      <div class="no-chat-content">
        <svg viewBox="0 0 303 172" width="360" height="240">
          <path fill="#DFE5E7" d="M151.5 0C67.9 0 0 67.9 0 151.5S67.9 303 151.5 303 303 235.1 303 151.5 235.1 0 151.5 0zm0 280C80.4 280 23 222.6 23 151.5S80.4 23 151.5 23 280 80.4 280 151.5 222.6 280 151.5 280z"/>
          <circle fill="#DFE5E7" cx="151.5" cy="151.5" r="94"/>
        </svg>
        <h2>WhatsApp Web</h2>
        <p>Send and receive messages without keeping your phone online.</p>
      </div>
    </div>

    <!-- Info Panel -->
    <ChatInfo
      v-if="activeConversation && showInfo"
      :conversation="activeConversation"
      @close="toggleInfo"
    />
  </div>
</template>

<style scoped>
.chat-container {
  display: flex;
  height: 100vh;
  background: #111b21;
  overflow: hidden;
}

.no-chat-selected {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #222e35;
  border-bottom: 6px solid #008069;
}

.no-chat-content {
  text-align: center;
  color: #8696a0;
  padding: 40px;
}

.no-chat-content svg {
  margin-bottom: 30px;
  opacity: 0.3;
}

.no-chat-content h2 {
  font-size: 32px;
  font-weight: 300;
  margin-bottom: 20px;
  color: #e9edef;
}

.no-chat-content p {
  font-size: 14px;
  line-height: 20px;
}
</style>