<script setup lang="ts">
import { onMounted, onUnmounted, ref, watch, computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import axios from "axios";

import MessageList from "./MessageList.vue";
import MessageInput from "./MessageInput.vue";
import { Avatar, AvatarFallback } from "@/components/ui/avatar";
import { Phone, Video, MoreVertical } from "lucide-vue-next";

type Sender = { id: number; name: string };
type Attachment = {
  url: string;
  type?: "image" | "video" | "file" | null;
  mime?: string | null;
  original_name?: string | null;
};
type Message = {
  id: number;
  body: string;
  sender: Sender;
  created_at?: string;
  attachments?: Attachment[];
};
type Paginator<T> = { data: T[] };
type ConversationLite = { id: number; name: string | null; is_group: boolean };

// Minimal surface for a presence channel (Echo doesn't expose a `leave()` here)
type PresenceChannel = {
  here(cb: (users: any[]) => void): PresenceChannel;
  joining(cb: (user: any) => void): PresenceChannel;
  leaving(cb: (user: any) => void): PresenceChannel;
  listen(event: string, cb: (payload: any) => void): PresenceChannel;
  listenForWhisper(event: string, cb: (payload: any) => void): PresenceChannel;
  whisper(event: string, payload: any): void;
};

const props = defineProps<{ conversation: ConversationLite | null }>();

const page = usePage();
const authUser = (page.props as any).auth.user as { id: number; name: string };

const messages = ref<Message[]>([]);
const typingUsers = ref<Map<number, number>>(new Map());

let presence: PresenceChannel | null = null;
const currentChannel = ref<string | null>(null);

const hasConv = computed(() => !!props.conversation?.id);

function initials(name?: string | null) {
  return (name || "C")
    .split(" ")
    .map((s) => s[0])
    .join("")
    .slice(0, 2)
    .toUpperCase();
}

async function loadMessages(id: number) {
  const { data } = await axios.get<Paginator<Message>>(`/chat/${id}/messages`);
  messages.value = [...data.data].reverse();
}

async function markReadAll(id: number) {
  await axios.post(`/chat/${id}/read-all`);
}

function join(id: number) {
  const channelName = `presence.chat.${id}`;
  currentChannel.value = channelName;

  presence = (window as any).Echo.join(channelName) as PresenceChannel;
  presence
    .here(() => {})
    .joining(() => {})
    .leaving((user: { id: number }) => {
      typingUsers.value.delete(user.id);
    })
    .listen("MessageCreated", (payload: Message) => {
      messages.value.push(payload);
      scrollToBottom();
      if (payload.sender.id !== authUser.id) markReadAll(id);
    })
    .listenForWhisper("typing", ({ userId }: { userId: number }) => {
      if (userId !== authUser.id) {
        typingUsers.value.set(userId, Date.now());
        setTimeout(() => typingUsers.value.delete(userId), 3000);
      }
    });
}

function leave() {
  if (currentChannel.value) {
    (window as any).Echo.leave(currentChannel.value);
  }
  currentChannel.value = null;
  presence = null;
  typingUsers.value.clear();
}

function scrollToBottom() {
  requestAnimationFrame(() => {
    const el = document.getElementById("chat-scroll");
    if (el) el.scrollTop = el.scrollHeight;
  });
}

async function send(payload: { body: string; files: File[] }) {
  if (!props.conversation?.id) return;

  const fd = new FormData();
  if (payload.body) fd.append("body", payload.body);
  payload.files.forEach((f) => fd.append("attachments[]", f));

  const { data } = await axios.post(`/chat/${props.conversation.id}/messages`, fd, {
    headers: { "Content-Type": "multipart/form-data" },
  });

  // Sender-side immediate append; others receive via broadcast
  messages.value.push(data.message);
  scrollToBottom();
  await markReadAll(props.conversation.id);
}

function whisperTyping() {
  if (!presence) return;
  presence.whisper("typing", { userId: authUser.id });
}

// When selected conversation changes (from the left list)
watch(
  () => props.conversation?.id,
  async (id, old) => {
    if (old) leave();
    messages.value = [];
    if (!id) return;
    await loadMessages(id);
    join(id);
    await markReadAll(id);
    scrollToBottom();
  }
);

onMounted(() => {
  if (props.conversation?.id) {
    const id = props.conversation.id;
    loadMessages(id).then(() => {
      join(id);
      markReadAll(id);
      scrollToBottom();
    });
  }
});

onUnmounted(leave);
</script>

<template>
  <section class="flex flex-col h-[calc(100vh-0rem)]">
    <div
      v-if="!hasConv"
      class="flex-1 flex items-center justify-center text-sm text-gray-500"
    >
      Select a conversation or start a new one.
    </div>

    <template v-else>
      <!-- Header -->
      <header class="h-16 border-b px-4 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <Avatar class="h-9 w-9">
            <AvatarFallback>{{ initials(props.conversation?.name) }}</AvatarFallback>
          </Avatar>
          <div>
            <div class="font-semibold leading-tight">
              {{ props.conversation?.name || "Conversation" }}
            </div>
            <div class="text-[11px] text-gray-500">
              {{ typingUsers.size ? "Typing…" : "Online" }}
            </div>
          </div>
        </div>
        <div class="flex items-center gap-2 text-gray-600">
          <button class="p-2 rounded hover:bg-gray-100" aria-label="Voice call">
            <Phone class="h-4 w-4" />
          </button>
          <button class="p-2 rounded hover:bg-gray-100" aria-label="Video call">
            <Video class="h-4 w-4" />
          </button>
          <button class="p-2 rounded hover:bg-gray-100" aria-label="More">
            <MoreVertical class="h-4 w-4" />
          </button>
        </div>
      </header>

      <!-- Messages -->
      <main id="chat-scroll" class="flex-1 overflow-y-auto p-4 bg-[rgb(240,242,245)]">
        <MessageList :items="messages" :auth-id="authUser.id" />
      </main>

      <!-- Composer (text + emoji + media) -->
      <footer class="border-t p-3 bg-white">
        <MessageInput @typing="whisperTyping" @send="send" />
      </footer>
    </template>
  </section>
</template>
