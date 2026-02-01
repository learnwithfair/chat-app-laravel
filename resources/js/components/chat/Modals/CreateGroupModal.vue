<template>
  <div @click="$emit('close')" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div @click.stop class="bg-white rounded-xl shadow-2xl max-w-md w-full max-h-[90vh] flex flex-col overflow-hidden">
      <!-- Header - Fixed -->
      <div class="px-6 py-4 border-b flex items-center justify-between flex-shrink-0">
        <h3 class="text-lg font-semibold text-gray-800">Create New Group</h3>
        <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 cursor-pointer">
          ✕
        </button>
      </div>

      <!-- Body - Scrollable -->
      <div class="p-6 space-y-5 overflow-y-auto flex-1 scrollbar-custom">
        <!-- Group name -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1"> Group Name </label>
          <input v-model="formData.name" type="text" placeholder="e.g. Project Team"
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none" />
        </div>

        <!-- Description -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Description <span class="text-gray-400">(optional)</span>
          </label>
          <textarea v-model="formData.description" rows="3" placeholder="What is this group about?"
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none resize-none"></textarea>
        </div>

        <!-- Group type -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Group Privacy
          </label>
          <div class="flex gap-6">
            <label class="flex items-center gap-2 cursor-pointer">
              <input type="radio" v-model="formData.type" value="private" class="cursor-pointer" />
              <span class="text-sm">Private</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
              <input type="radio" v-model="formData.type" value="public" class="cursor-pointer" />
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
          <input v-model="search" type="text" placeholder="Search members..."
            class="w-full px-3 py-2 mb-3 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none" />

          <!-- User list -->
          <div class="max-h-48 overflow-y-auto border rounded-lg divide-y scrollbar-custom">
            <div v-for="user in filteredUsers" :key="user.id" @click="toggleUser(user.id)"
              class="flex items-center px-4 py-3 cursor-pointer hover:bg-gray-50 transition-colors">
              <input type="checkbox" class="mr-3 cursor-pointer" :checked="selectedMembers.includes(user.id)"
                @click.stop />

              <img :src="user.avatar" class="w-9 h-9 rounded-full object-cover" alt="" />

              <span class="ml-3 text-sm font-medium text-gray-800">
                {{ user.name }}
              </span>
            </div>

            <p v-if="filteredUsers.length === 0" class="text-center text-sm text-gray-400 py-4">
              No users found
            </p>
          </div>

          <!-- Load more -->
          <button v-if="pagination?.hasMore" @click="$emit('load-more')"
            class="w-full text-sm py-2 text-blue-500 hover:underline mt-2">
            Load more
          </button>
        </div>
      </div>

      <!-- Footer - Fixed -->
      <div class="px-6 py-4 border-t flex-shrink-0 bg-gray-50">
        <button @click="handleCreate" :disabled="!formData.name.trim() || selectedMembers.length === 0" :class="[
          'w-full py-2.5 rounded-lg font-medium transition',
          formData.name.trim() && selectedMembers.length
            ? 'bg-blue-500 text-white hover:bg-blue-600'
            : 'bg-gray-300 text-gray-500 cursor-not-allowed',
        ]">
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

const emit = defineEmits(["close", "create", "load-more"]);

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

<style scoped>
/* Custom thin scrollbar that appears only on hover */
.scrollbar-custom {
  scrollbar-width: thin;
  scrollbar-color: transparent transparent;
}

.scrollbar-custom:hover {
  scrollbar-color: rgba(156, 163, 175, 0.5) transparent;
}

/* For WebKit browsers (Chrome, Safari, Edge) */
.scrollbar-custom::-webkit-scrollbar {
  width: 6px;
}

.scrollbar-custom::-webkit-scrollbar-track {
  background: transparent;
}

.scrollbar-custom::-webkit-scrollbar-thumb {
  background-color: transparent;
  border-radius: 10px;
  transition: background-color 0.3s;
}

.scrollbar-custom:hover::-webkit-scrollbar-thumb {
  background-color: rgba(156, 163, 175, 0.5);
}

.scrollbar-custom:hover::-webkit-scrollbar-thumb:hover {
  background-color: rgba(107, 114, 128, 0.7);
}
</style>