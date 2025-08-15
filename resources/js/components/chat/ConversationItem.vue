<script setup lang="ts">
import { computed } from "vue";
import { Avatar, AvatarFallback } from "@/components/ui/avatar";
import Badge from "@/components/ui/badge.vue";

type Item = {
  id: number;
  name: string | null;
  is_group: boolean;
  last_message?: string | null;
  last_at?: string | null;
  unread: number;
};

const props = defineProps<{ item: Item; active?: boolean }>();
const emit = defineEmits<{ (e: "select", id: number): void }>();

const initials = computed(() =>
  (props.item.name || "C")
    .split(" ")
    .map((s) => s[0])
    .join("")
    .slice(0, 2)
    .toUpperCase()
);
const when = computed(() => {
  if (!props.item.last_at) return "";
  const d = new Date(props.item.last_at);
  return d.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" });
});
</script>

<template>
  <button type="button" @click="emit('select', item.id)" class="w-full text-left">
    <div
      class="flex items-center gap-3 px-3 py-2 rounded-lg"
      :class="active ? 'bg-gray-100' : 'hover:bg-gray-50'"
    >
      <Avatar class="h-10 w-10">
        <AvatarFallback>{{ initials }}</AvatarFallback>
      </Avatar>

      <div class="min-w-0 flex-1">
        <div class="flex items-center justify-between">
          <div class="font-medium truncate">{{ item.name || "Conversation" }}</div>
          <div class="text-xs text-gray-500">{{ when }}</div>
        </div>
        <div class="flex items-center justify-between gap-2">
          <div class="text-xs text-gray-600 truncate">
            {{ item.last_message || "No messages yet" }}
          </div>
          <Badge v-if="item.unread > 0">{{ item.unread }}</Badge>
        </div>
      </div>
    </div>
  </button>
</template>
