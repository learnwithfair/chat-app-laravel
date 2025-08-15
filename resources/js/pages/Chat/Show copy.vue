<script setup lang="ts">
import { Head, usePage } from "@inertiajs/vue3";
import { onMounted, onUnmounted, ref } from "vue";
import ChatLayout from "@/components/chat/ChatLayout.vue";
import MessageList from "@/components/chat/MessageList.vue";
import MessageInput from "@/components/chat/MessageInput.vue";
import axios from "axios";

// Define types for the presence channel
interface PresenceChannel {
  here: (callback: (users: any[]) => void) => PresenceChannel;
  joining: (callback: (user: any) => void) => PresenceChannel;
  leaving: (callback: (user: { id: number }) => void) => PresenceChannel;
  listen: (event: string, callback: (payload: any) => void) => PresenceChannel;
  listenForWhisper: (event: string, callback: (payload: any) => void) => PresenceChannel;
  whisper: (event: string, data: any) => void;
  leave: () => void;
}

type Conversation = {
  id: number;
  name?: string | null;
  is_group: boolean;
};

type Sender = { id: number; name: string };

type Message = {
  id: number;
  body: string;
  sender: Sender;
  conversation_id: number;
  created_at?: string;
};

type Paginator<T> = {
  data: T[];
};

const props = defineProps<{
  conversation: Conversation;
  initialMessages: Paginator<Message>;
}>();

const page = usePage();
const authUser = (page.props as any).auth.user as { id: number; name: string };

const messages = ref<Message[]>([...props.initialMessages.data].reverse());
const typingUsers = ref<Map<number, number>>(new Map());

// Type the presence variable properly
let presence: PresenceChannel | null = null;

function joinChannel() {
  const channelName = `presence.chat.${props.conversation.id}`;

  // Cast the Echo join result to our PresenceChannel interface
  presence = ((window as any).Echo.join(channelName) as PresenceChannel)
    .here(() => {})
    .joining(() => {})
    .leaving((user: { id: number }) => {
      typingUsers.value.delete(user.id);
    })
    .listen("MessageCreated", (payload: Message) => {
      messages.value.push(payload);
      scrollToBottom();
    })
    .listenForWhisper("typing", ({ userId }: { userId: number }) => {
      if (userId !== authUser.id) {
        typingUsers.value.set(userId, Date.now());
        setTimeout(() => typingUsers.value.delete(userId), 3000);
      }
    });
}

function whisperTyping(): void {
  if (!presence) return;
  presence.whisper("typing", { userId: authUser.id });
}

function scrollToBottom() {
  requestAnimationFrame(() => {
    const el = document.getElementById("chat-scroll");
    if (el) el.scrollTop = el.scrollHeight;
  });
}

async function send(body: string) {
  const { data } = await axios.post(`/chat/${props.conversation.id}/messages`, { body });
  // sender-side immediate append
  messages.value.push(data.message);
  scrollToBottom();
}

onMounted(() => {
  joinChannel();
  scrollToBottom();
});

onUnmounted(() => {
  if (presence) presence.leave();
});
</script>

<template>
  <ChatLayout>
    <Head :title="props.conversation.name || 'Conversation'" />
    <div class="h-full grid grid-cols-12">
      <section class="col-span-12 flex flex-col h-[calc(100vh-4rem)]">
        <header class="h-16 border-b flex items-center px-4 justify-between">
          <div>
            <h2 class="font-semibold">{{ props.conversation.name || "Conversation" }}</h2>
            <p class="text-xs text-gray-500">
              {{ typingUsers.size ? "Typing…" : "Online" }}
            </p>
          </div>
        </header>
        <main id="chat-scroll" class="flex-1 overflow-y-auto p-4 bg-gray-50">
          <MessageList :items="messages" :auth-id="authUser.id" />
        </main>
        <footer class="border-t p-3">
          <MessageInput @typing="whisperTyping" @send="send" />
        </footer>
      </section>
    </div>
  </ChatLayout>
</template>
