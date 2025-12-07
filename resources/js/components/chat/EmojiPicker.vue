<script setup lang="ts">
import EmojiPicker from "vue3-emoji-picker";
import "vue3-emoji-picker/css";

const emit = defineEmits<{ (e: "pick", ch: string): void }>();

// This handler emits the emoji character
function handleSelect(emoji: any) {
  emit("pick", emoji.native || emoji.emoji || emoji.i);
}
</script>

<template>
  <div class="p-2">
    <!-- Use @select for latest vue3-emoji-picker -->
    <EmojiPicker @select="handleSelect" />
  </div>
</template>
