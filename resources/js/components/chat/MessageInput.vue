<script setup lang="ts">
import { ref } from "vue";
import { Button } from "@/components/ui/button";
import { LoaderCircle, SendHorizonal } from "lucide-vue-next";

const emit = defineEmits<{
  (e: "send", body: string): void;
  (e: "typing"): void;
}>();

const body = ref("");
const sending = ref(false);

function onInput() {
  emit("typing");
}

async function onSubmit(e: Event) {
  e.preventDefault();
  const value = body.value.trim();
  if (!value || sending.value) return;
  sending.value = true;
  emit("send", value);
  // Optimistic reset – your parent will push the final message via socket
  body.value = "";
  sending.value = false;
}
</script>

<template>
  <form @submit="onSubmit" class="flex gap-2 items-end">
    <textarea
      v-model="body"
      @input="onInput"
      rows="1"
      placeholder="Write a message"
      class="flex-1 resize-none rounded-xl border px-3 py-2 focus:outline-none focus:ring"
    />
    <Button type="submit" :disabled="!body.trim().length || sending" class="min-w-24">
      <LoaderCircle v-if="sending" class="h-4 w-4 animate-spin" />
      <template v-else>
        <SendHorizonal class="h-4 w-4 mr-2" />
        Send
      </template>
    </Button>
  </form>
</template>
