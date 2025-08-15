<script setup lang="ts">
import { ref, watch, computed } from "vue";
import axios from "axios";
import { router } from "@inertiajs/vue3";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
  DialogFooter,
} from "@/components/ui/dialog";

type User = { id: number; name: string; email: string };

const open = ref(false);
const query = ref("");
const results = ref<User[]>([]);
const selected = ref<User | null>(null);
const loading = ref(false);
const searching = ref(false);
let timer: number | undefined;

const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

const validEmail = computed(() => emailRegex.test(query.value.trim()));
const canStart = computed(() => (!!selected.value || validEmail.value) && !loading.value);

watch(query, (val) => {
  selected.value = null; // typing clears selection
  results.value = [];
  if (!val || val.trim().length < 2) return;
  clearTimeout(timer);
  timer = window.setTimeout(async () => {
    try {
      searching.value = true;
      const { data } = await axios.get<User[]>("/chat-users", { params: { q: val } });
      results.value = data;
    } finally {
      searching.value = false;
    }
  }, 250);
});

function choose(user: User) {
  selected.value = user;
}

async function startChat() {
  if (!canStart.value) return;
  loading.value = true;
  try {
    if (selected.value) {
      const { data } = await axios.post<{ id: number }>("/chat", {
        user_id: selected.value.id,
      });
      router.visit(`/chat/${data.id}`);
      open.value = false;
      return;
    }
    if (validEmail.value) {
      const email = query.value.trim();
      const { data } = await axios.post<{ id: number }>("/chat", { email });
      router.visit(`/chat/${data.id}`);
      open.value = false;
      return;
    }
  } catch (e: any) {
    // Optional: show a simple inline error state
    alert(e?.response?.data?.message ?? "Unable to start chat");
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <Dialog v-model:open="open">
    <DialogTrigger as-child>
      <Button variant="default">New message</Button>
    </DialogTrigger>

    <DialogContent class="sm:max-w-lg">
      <DialogHeader>
        <DialogTitle>Start a new chat</DialogTitle>
      </DialogHeader>

      <div class="space-y-3">
        <Input
          v-model="query"
          placeholder="Search by name or type a full email"
          @keyup.enter="startChat"
        />

        <div class="max-h-64 overflow-y-auto rounded border">
          <template v-if="searching">
            <div class="px-3 py-6 text-sm text-gray-500 text-center">Searching…</div>
          </template>
          <template v-else-if="results.length">
            <ul class="divide-y">
              <li
                v-for="u in results"
                :key="u.id"
                @click="choose(u)"
                class="px-3 py-2 cursor-pointer hover:bg-gray-50"
                :class="selected?.id === u.id ? 'bg-gray-100' : ''"
              >
                <div class="font-medium">{{ u.name }}</div>
                <div class="text-xs text-gray-500">{{ u.email }}</div>
              </li>
            </ul>
          </template>
          <template v-else>
            <div class="px-3 py-6 text-sm text-gray-500 text-center">
              Type to search users.
              <template v-if="validEmail">
                <br />Press Enter or click Start to chat with this email.
              </template>
            </div>
          </template>
        </div>
      </div>

      <DialogFooter>
        <Button :disabled="!canStart" @click="startChat">
          {{ loading ? "Creating…" : "Start chat" }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
