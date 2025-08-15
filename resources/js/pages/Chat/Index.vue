<script setup lang="ts">
import { Head } from "@inertiajs/vue3";
import ChatLayout from "@/components/chat/ChatLayout.vue";
import ConversationList from "@/components/chat/ConversationList.vue";
import NewChatModal from "@/components/chat/NewChatModal.vue";
// import { Button } from "@/components/ui/button";

type Member = { id: number; name: string };
type Conversation = {
  id: number;
  name?: string | null;
  is_group: boolean;
  last_message?: string | null;
  members: Member[];
};

const props = defineProps<{
  conversations: Conversation[];
}>();
</script>

<template>
  <ChatLayout>
    <Head title="Messages" />
    <div class="h-full grid grid-cols-12">
      <aside class="col-span-4 border-r flex flex-col">
        <div class="px-4 py-3 flex items-center justify-between">
          <h1 class="text-xl font-semibold">Messages</h1>
          <NewChatModal />
        </div>
        <div class="flex-1 overflow-y-auto">
          <ConversationList
            v-if="props.conversations.length"
            :items="props.conversations"
          />
          <div
            v-else
            class="h-full flex items-center justify-center p-6 text-center text-sm text-gray-500"
          >
            <div>
              <p class="mb-3">No conversations yet.</p>
              <NewChatModal />
            </div>
          </div>
        </div>
      </aside>

      <section class="col-span-8 flex items-center justify-center">
        <div class="text-center text-sm text-gray-500">
          Select a conversation from the left.
        </div>
      </section>
    </div>
  </ChatLayout>
</template>
