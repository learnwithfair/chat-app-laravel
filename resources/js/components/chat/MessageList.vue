<script setup lang="ts">
type Sender = { id: number; name: string };
type Message = {
  id: number;
  body: string;
  conversation_id?: number;
  sender: Sender;
  created_at?: string;
};

const props = defineProps<{
  items: Message[];
  authId: number;
}>();
console.log(props);
</script>

<template>
  <div class="space-y-2">
    <div
      v-for="m in items"
      :key="m.id"
      class="flex"
      :class="m.sender.id === authId ? 'justify-end' : 'justify-start'"
    >
      <div
        class="max-w-[70%] rounded-2xl px-4 py-2 shadow border"
        :class="
          m.sender.id === authId
            ? 'bg-white border-gray-200'
            : 'bg-gray-100 border-gray-200'
        "
      >
        <div class="text-xs text-gray-500 mb-1" v-if="m.sender.id !== authId">
          {{ m.sender.name }}
        </div>
        <div class="text-sm whitespace-pre-wrap break-words">{{ m.body }}</div>
      </div>
    </div>
  </div>
</template>
