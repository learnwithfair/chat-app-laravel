<script setup lang="ts">
import { ref } from "vue";
import { Head } from "@inertiajs/vue3";
import ChatLayout from "@/components/chat/ChatLayout.vue";
import ConversationList from "@/components/chat/ConversationList.vue";
import NewChatModal from "@/components/chat/NewChatModal.vue";
import ChatPane from "@/components/chat/ChatPane.vue";

type ConversationItem = {
  id: number;
  name: string | null;
  is_group: boolean;
  last_message?: string | null;
  last_at?: string | null;
  unread: number;
};

const props = defineProps<{ conversations: ConversationItem[] }>();

// local, mutable copy of the list
const list = ref<ConversationItem[]>([...props.conversations]);
const selected = ref<ConversationItem | null>(list.value[0] ?? null);

function openConversation(id: number) {
  const item = list.value.find((c) => c.id === id) || null;
  selected.value = item;
}

function insertOrSelect(conv: ConversationItem) {
  const existingIdx = list.value.findIndex((c) => c.id === conv.id);
  if (existingIdx >= 0) {
    list.value.splice(existingIdx, 1);
  }
  list.value.unshift(conv);
  selected.value = conv;
}
</script>

<template>
  <ChatLayout>
    <Head title="Messages" />
    <div class="h-full grid grid-cols-[380px_1fr]">
      <aside class="border-r flex flex-col">
        <div class="px-4 py-3 flex items-center justify-between">
          <h1 class="text-xl font-semibold">Chats</h1>
          <NewChatModal @created="insertOrSelect" />
        </div>
        <ConversationList
          :items="list"
          :active-id="selected?.id ?? null"
          @select="openConversation"
        />
      </aside>

      <!-- Pass both id and name to the pane -->
      <ChatPane :conversation="selected" />
    </div>
  </ChatLayout>
</template>
