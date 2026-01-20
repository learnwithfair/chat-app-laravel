<template>
  <div
    @click="$emit('close')"
    class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
  >
    <div
      @click.stop
      class="bg-white rounded-xl shadow-2xl max-w-md w-full max-h-[85vh] overflow-hidden"
    >
      <!-- Header -->
      <div class="px-6 py-4 border-b flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-800">Create New Group</h3>
        <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 cursor-pointer">
          ✕
        </button>
      </div>

      <!-- Body -->
      <div class="p-6 space-y-5 overflow-y-auto">
        <!-- Group name -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1"> Group Name </label>
          <input
            v-model="formData.name"
            type="text"
            placeholder="e.g. Project Team"
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <!-- Description -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Description <span class="text-gray-400">(optional)</span>
          </label>
          <textarea
            v-model="formData.description"
            rows="3"
            placeholder="What is this group about?"
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
          ></textarea>
        </div>

        <!-- Group type -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Group Privacy
          </label>
          <div class="flex gap-6">
            <label class="flex items-center gap-2 cursor-pointer">
              <input type="radio" v-model="formData.type" value="private" />
              <span class="text-sm">Private</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
              <input type="radio" v-model="formData.type" value="public" />
              <span class="text-sm">Public</span>
            </label>
          </div>
        </div>

        <!-- Members -->
        <div>
          <div class="flex items-center justify-between mb-2">
            <label class="text-sm font-medium text-gray-700"> Add Members </label>
            <span class="text-xs text-gray-500">
              {{ selectedMembers.length }} selected
            </span>
          </div>

          <!-- Search -->
          <input
            v-model="search"
            type="text"
            placeholder="Search members..."
            class="w-full px-3 py-2 mb-3 border rounded-lg focus:ring-2 focus:ring-blue-500"
          />

          <!-- User list -->
          <div class="max-h-48 overflow-y-auto border rounded-lg divide-y">
            <div
              v-for="user in filteredUsers"
              :key="user.id"
              @click="toggleUser(user.id)"
              class="flex items-center px-4 py-3 cursor-pointer hover:bg-gray-50"
            >
              <input
                type="checkbox"
                class="mr-3"
                :checked="selectedMembers.includes(user.id)"
                @click.stop
              />

              <img :src="user.avatar" class="w-9 h-9 rounded-full object-cover" />

              <span class="ml-3 text-sm font-medium text-gray-800">
                {{ user.name }}
              </span>
            </div>

            <p
              v-if="filteredUsers.length === 0"
              class="text-center text-sm text-gray-400 py-4"
            >
              No users found
            </p>
          </div>

          <!-- Load more -->
          <button
            v-if="pagination?.hasMore"
            @click="$emit('load-more')"
            class="w-full text-sm py-2 text-blue-500 hover:underline"
          >
            Load more
          </button>
        </div>

        <!-- Create button -->
        <button
          @click="handleCreate"
          :disabled="!formData.name.trim() || selectedMembers.length === 0"
          :class="[
            'w-full py-2.5 rounded-lg font-medium transition',
            formData.name.trim() && selectedMembers.length
              ? 'bg-blue-500 text-white hover:bg-blue-600'
              : 'bg-gray-300 text-gray-500 cursor-not-allowed',
          ]"
        >
          Create Group
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from "vue";

const props = defineProps({
  availableUsers: Array,
  pagination: Object,
});

const emit = defineEmits(["close", "create"]);

const formData = ref({
  name: "",
  description: "",
  type: "private",
});

const search = ref("");
const selectedMembers = ref([]);

// Search filter
const filteredUsers = computed(() => {
  if (!search.value) return props.availableUsers;

  return props.availableUsers.filter((user) =>
    user.name.toLowerCase().includes(search.value.toLowerCase())
  );
});

const toggleUser = (userId) => {
  const index = selectedMembers.value.indexOf(userId);
  index > -1
    ? selectedMembers.value.splice(index, 1)
    : selectedMembers.value.push(userId);
};

const handleCreate = () => {
  emit("create", {
    ...formData.value,
    members: selectedMembers.value,
  });
};
</script>
