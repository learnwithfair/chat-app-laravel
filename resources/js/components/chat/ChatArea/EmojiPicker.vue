<script setup>
import EmojiPicker from "vue3-emoji-picker";
import "vue3-emoji-picker/css";

const emit = defineEmits(["pick"]);

function handleSelect(emoji) {
  emit("pick", emoji.native || emoji.emoji || emoji.i);
}
</script>

<template>
  <div class="absolute bottom-12 left-0 z-50">
    <EmojiPicker @select="handleSelect" />
  </div>
</template>
