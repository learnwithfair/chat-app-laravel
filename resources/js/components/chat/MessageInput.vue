<script setup lang="ts">
import { ref, nextTick } from "vue";
import EmojiPicker from "./EmojiPicker.vue";
import { Button } from "@/components/ui/button";
import { Paperclip, Smile, SendHorizonal, X } from "lucide-vue-next";

const emit = defineEmits<{
  (e: "send", payload: { body: string; files: File[] }): void;
  (e: "typing"): void;
}>();

const body = ref("");
const files = ref<File[]>([]);
const showEmoji = ref(false);
const textareaRef = ref<HTMLTextAreaElement | null>(null);

// Insert emoji at cursor position
function onPickEmoji(ch: string) {
  const textarea = textareaRef.value;
  if (!textarea) return;

  const start = textarea.selectionStart;
  const end = textarea.selectionEnd;
  const text = body.value;

  body.value = text.slice(0, start) + ch + text.slice(end);

  nextTick(() => {
    textarea.focus();
    const pos = start + ch.length;
    textarea.setSelectionRange(pos, pos);
  });
}

// Trigger typing event
function onInput() {
  emit("typing");
}

// Handle Enter key to send
function onKey(e: KeyboardEvent) {
  if (e.key === "Enter" && !e.shiftKey) {
    e.preventDefault();
    submit();
  }
}

// Send message
function submit() {
  if (!body.value.trim() && files.value.length === 0) return;
  emit("send", { body: body.value.trim(), files: files.value });
  body.value = "";
  files.value = [];
  showEmoji.value = false;
}

// File selection
function onChoose(e: Event) {
  const t = e.target as HTMLInputElement;
  if (!t.files) return;
  files.value = [...files.value, ...Array.from(t.files)];
  t.value = "";
}

// Remove file
function removeFile(idx: number) {
  files.value.splice(idx, 1);
}
</script>

<template>
  <div class="flex flex-col gap-2">
    <!-- Files preview -->
    <div v-if="files.length" class="flex gap-2 flex-wrap">
      <div
        v-for="(f, i) in files"
        :key="i"
        class="relative border rounded-lg p-2 text-xs"
      >
        <div class="font-medium max-w-[180px] truncate">{{ f.name }}</div>
        <button
          type="button"
          class="absolute -top-2 -right-2 bg-white border rounded-full p-1 hover:bg-gray-50"
          @click="removeFile(i)"
          aria-label="Remove"
        >
          <X class="h-3 w-3" />
        </button>
      </div>
    </div>

    <!-- Input row -->
    <div class="flex items-center gap-2 relative">
      <!-- Emoji button -->
      <div class="relative">
        <button
          class="p-2 rounded hover:bg-gray-100"
          type="button"
          aria-label="Emoji"
          @click="showEmoji = !showEmoji"
        >
          <Smile class="h-5 w-5" />
        </button>

        <!-- Emoji picker -->
        <div v-if="showEmoji" class="absolute bottom-10 left-0 z-10 shadow-lg rounded">
          <EmojiPicker @pick="onPickEmoji" />
        </div>
      </div>

      <!-- File attachment -->
      <label class="p-2 rounded hover:bg-gray-100 cursor-pointer" aria-label="Attach">
        <Paperclip class="h-5 w-5" />
        <input
          type="file"
          class="hidden"
          multiple
          @change="onChoose"
          accept="image/*,video/*,.pdf,.doc,.docx,.xls,.xlsx,.zip"
        />
      </label>

      <!-- Message textarea -->
      <textarea
        ref="textareaRef"
        v-model="body"
        @input="onInput"
        @keydown="onKey"
        rows="1"
        placeholder="Type a message"
        class="flex-1 resize-none rounded-xl border px-3 py-2 focus:outline-none focus:ring max-h-40"
      />

      <!-- Send button -->
      <Button
        type="button"
        @click="submit"
        :disabled="!body.trim() && files.length === 0"
        class="min-w-24"
      >
        <SendHorizonal class="h-4 w-4 mr-2" /> Send
      </Button>
    </div>
  </div>
</template>
