<template>
  <div
    @click="$emit('close')"
    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
  >
    <div
      @click.stop
      class="bg-white rounded-lg shadow-xl max-w-md w-full max-h-[80vh] overflow-hidden"
    >
      <!-- Header -->
      <div class="p-4 border-b flex items-center justify-between">
        <h3 class="text-lg font-semibold">Add Members</h3>
        <button @click="$emit('close')" class="text-gray-500 hover:text-gray-700">
          ✕
        </button>
      </div>

      <!-- Search -->
      <div class="p-4 border-b">
        <input
          v-model="search"
          type="text"
          placeholder="Search members..."
          class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
        />
      </div>

      <!-- User list -->
      <div class="p-4 max-h-64 overflow-y-auto border-b">
        <div
          v-for="user in filteredUsers"
          :key="user.id"
          @click="toggleUser(user.id)"
          :class="[
            'flex items-center p-3 rounded cursor-pointer transition',
            selectedUsers.includes(user.id) ? 'bg-blue-50' : 'hover:bg-gray-50',
          ]"
        >
          <input
            type="checkbox"
            class="mr-3"
            :checked="selectedUsers.includes(user.id)"
            @click.stop
          />

          <img :src="user.avatar" class="w-10 h-10 rounded-full object-cover" />

          <span class="ml-3 text-sm font-medium">
            {{ user.name }}
          </span>
        </div>

        <p
          v-if="filteredUsers.length === 0"
          class="text-center text-sm text-gray-500 py-6"
        >
          No users found
        </p>
      </div>

      <!-- Footer -->
      <div class="p-4">
        <button
          @click="handleAdd"
          :disabled="selectedUsers.length === 0"
          :class="[
            'w-full py-2 rounded-lg font-medium transition',
            selectedUsers.length
              ? 'bg-blue-500 text-white hover:bg-blue-600'
              : 'bg-gray-300 text-gray-500 cursor-not-allowed',
          ]"
        >
          Add Selected ({{ selectedUsers.length }})
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from "vue";

const props = defineProps({
  availableUsers: Array,
});

const emit = defineEmits(["close", "add-multiple"]);

const search = ref("");
const selectedUsers = ref([]);

// Filter logic for users based on search input
const filteredUsers = computed(() => {
  if (!search.value) return props.availableUsers;

  return props.availableUsers.filter((user) =>
    user.name.toLowerCase().includes(search.value.toLowerCase())
  );
});

const toggleUser = (userId) => {
  const index = selectedUsers.value.indexOf(userId);
  if (index > -1) {
    selectedUsers.value.splice(index, 1);
  } else {
    selectedUsers.value.push(userId);
  }
};

const handleAdd = () => {
  emit("add-multiple", selectedUsers.value);
  selectedUsers.value = [];
};
</script>
