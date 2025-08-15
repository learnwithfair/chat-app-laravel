<script setup lang="ts">
import { Link } from "@inertiajs/vue3";

type Member = { id: number; name: string };
type Item = {
  id: number;
  name?: string | null;
  is_group: boolean;
  last_message?: string | null;
  members: Member[];
};

const props = defineProps<{
  items: Item[];
}>();

console.log(props);
</script>

<template>
  <ul class="divide-y">
    <li v-for="c in items" :key="c.id">
      <Link
        :href="`/chat/${c.id}`"
        class="flex items-start gap-3 px-4 py-3 hover:bg-gray-50"
      >
        <div class="flex-1">
          <div class="flex items-center justify-between">
            <h3 class="font-medium truncate">
              {{ c.name || "Conversation" }}
            </h3>
          </div>
          <p class="text-sm text-gray-600 line-clamp-1">
            {{ c.last_message ?? "No messages yet" }}
          </p>
        </div>
      </Link>
    </li>
  </ul>
</template>
