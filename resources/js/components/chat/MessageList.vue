<script setup lang="ts">
import MessageBubble from "./MessageBubble.vue";

type Sender = { id: number; name: string };
type Message = { id: number; body: string; sender: Sender; created_at?: string };

const props = defineProps<{ items: Message[]; authId: number }>();

function dayKey(iso?: string) {
  if (!iso) return "";
  const d = new Date(iso);
  return d.toLocaleDateString();
}
</script>

<template>
  <div class="space-y-2">
    <template v-for="(m, i) in items" :key="m.id">
      <div
        v-if="i === 0 || dayKey(items[i - 1].created_at) !== dayKey(m.created_at)"
        class="text-center my-3"
      >
        <span
          class="inline-block text-[11px] px-3 py-1 rounded-full bg-gray-100 text-gray-600"
        >
          {{ dayKey(m.created_at) }}
        </span>
      </div>
      <MessageBubble :m="m" :auth-id="authId" />
    </template>
  </div>
</template>
