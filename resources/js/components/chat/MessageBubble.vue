<script setup lang="ts">
type Sender = { id: number; name: string };
type Attachment = {
  url: string;
  type?: "image" | "video" | "file" | null;
  mime?: string | null;
  original_name?: string | null;
};
type Msg = {
  id: number;
  body: string;
  sender: Sender;
  created_at?: string;
  attachments?: Attachment[];
};
const props = defineProps<{ m: Msg; authId: number }>();

function timeOf(d?: string) {
  if (!d) return "";
  const t = new Date(d);
  return t.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" });
}
</script>

<template>
  <div
    class="flex mb-1"
    :class="m.sender.id === authId ? 'justify-end' : 'justify-start'"
  >
    <div
      class="relative max-w-[75%] px-3 py-2 rounded-2xl shadow border text-sm whitespace-pre-wrap break-words"
      :class="
        m.sender.id === authId
          ? 'bg-emerald-50 border-emerald-100'
          : 'bg-white border-gray-200'
      "
    >
      <div
        v-if="m.sender.id !== authId"
        class="text-[11px] text-emerald-700 font-medium mb-0.5"
      >
        {{ m.sender.name }}
      </div>

      <div v-if="m.attachments?.length" class="space-y-2 mb-1">
        <div v-for="(att, i) in m.attachments" :key="i">
          <img v-if="att.type === 'image'" :src="att.url" class="rounded-lg max-w-full" />
          <video
            v-else-if="att.type === 'video'"
            :src="att.url"
            controls
            class="rounded-lg max-w-full"
          ></video>
          <a v-else :href="att.url" target="_blank" class="text-blue-600 underline">{{
            att.original_name || "Download file"
          }}</a>
        </div>
      </div>

      <div v-if="m.body">{{ m.body }}</div>
      <div class="text-[10px] text-gray-500 mt-1 text-right">
        {{ timeOf(m.created_at) }}
      </div>
      <span
        class="absolute -bottom-1 w-3 h-3 rotate-45 border"
        :class="
          m.sender.id === authId
            ? 'right-1 bg-emerald-50 border-emerald-100'
            : 'left-1 bg-white border-gray-200'
        "
      />
    </div>
  </div>
</template>
