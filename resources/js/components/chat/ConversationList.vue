<script setup lang="ts">
import { ref, computed } from "vue";
import { Input } from "@/components/ui/input";
import ConversationItem from "./ConversationItem.vue";

type Item = {
  id: number;
  name: string | null;
  is_group: boolean;
  last_message?: string | null;
  last_at?: string | null;
  unread: number;
};

const props = defineProps<{ items: Item[]; activeId?: number | null }>();
const emit = defineEmits<{ (e: "select", id: number): void }>();

const q = ref("");
const filtered = computed(() => {
  const v = q.value.trim().toLowerCase();
  if (!v) return props.items;
  return props.items.filter(
    (i) =>
      (i.name || "").toLowerCase().includes(v) ||
      (i.last_message || "").toLowerCase().includes(v)
  );
});
</script>

<template>
  <div class="flex flex-col h-full">
    <div class="p-3">
      <Input v-model="q" placeholder="Search or start new chat" />
    </div>
    <div class="flex-1 overflow-y-auto px-2 space-y-1">
      <ConversationItem
        v-for="c in filtered"
        :key="c.id"
        :item="c"
        :active="c.id === activeId"
        @select="emit('select', $event)"
      />
      <div v-if="filtered.length === 0" class="text-center text-sm text-gray-500 py-6">
        No chats found.
      </div>
    </div>
  </div>
</template>
