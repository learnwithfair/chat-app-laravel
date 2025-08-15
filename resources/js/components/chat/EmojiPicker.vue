<script setup lang="ts">
const emit = defineEmits<{ (e: "pick", ch: string): void }>();
const common = [
  "😀",
  "😁",
  "😂",
  "🤣",
  "😊",
  "😍",
  "😘",
  "😎",
  "🤔",
  "😮",
  "😢",
  "😡",
  "👍",
  "🙏",
  "👏",
  "👌",
  "🔥",
  "🎉",
  "✨",
  "❤️",
  "💯",
];
</script>

<template>
  <div class="grid grid-cols-8 gap-1 p-2 bg-white border rounded shadow">
    <button
      v-for="e in common"
      :key="e"
      type="button"
      class="h-8 w-8 flex items-center justify-center rounded hover:bg-gray-100"
      @click="emit('pick', e)"
    >
      {{ e }}
    </button>
  </div>
</template>
