<template>
  <div @click="$emit('close')" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div @click.stop class="bg-white rounded-lg shadow-xl max-w-md w-full max-h-[80vh] overflow-hidden">
      <div class="p-4 border-b border-gray-200 flex items-center justify-between">
        <h3 class="text-lg font-semibold">Create New Group</h3>
        <button @click="$emit('close')" class="text-gray-500 hover:text-gray-700">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
      
      <div class="p-6 overflow-y-auto">
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">Group Name</label>
          <input
            v-model="formData.name"
            type="text"
            placeholder="Enter group name"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">Description (Optional)</label>
          <textarea
            v-model="formData.description"
            placeholder="Enter group description"
            rows="3"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
          ></textarea>
        </div>

        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">Group Type</label>
          <div class="flex space-x-4">
            <label class="flex items-center">
              <input v-model="formData.type" type="radio" value="private" class="mr-2" />
              <span class="text-sm">Private</span>
            </label>
            <label class="flex items-center">
              <input v-model="formData.type" type="radio" value="public" class="mr-2" />
              <span class="text-sm">Public</span>
            </label>
          </div>
        </div>

        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">Add Members</label>
          <div class="max-h-48 overflow-y-auto border border-gray-300 rounded-lg">
            <div
              v-for="user in availableUsers"
              :key="user.id"
              @click="toggleUser(user.id)"
              :class="[
                'flex items-center p-3 hover:bg-gray-50 cursor-pointer',
                selectedMembers.includes(user.id) ? 'bg-blue-50' : ''
              ]"
            >
              <input type="checkbox" :checked="selectedMembers.includes(user.id)" class="mr-3" />
              <img :src="user.avatar" :alt="user.name" class="w-8 h-8 rounded-full object-cover" />
              <span class="ml-3 text-sm font-medium text-gray-900">{{ user.name }}</span>
            </div>
          </div>
        </div>

        <button
          @click="handleCreate"
          :disabled="!formData.name.trim() || selectedMembers.length === 0"
          :class="[
            'w-full py-2 rounded-lg font-medium transition-colors',
            formData.name.trim() && selectedMembers.length > 0
              ? 'bg-blue-500 text-white hover:bg-blue-600'
              : 'bg-gray-300 text-gray-500 cursor-not-allowed'
          ]"
        >
          Create Group
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';

const props = defineProps(['availableUsers']);
const emit = defineEmits(['close', 'create']);

const formData = ref({
  name: '',
  description: '',
  type: 'private'
});

const selectedMembers = ref([]);

const toggleUser = (userId) => {
  const index = selectedMembers.value.indexOf(userId);
  if (index > -1) {
    selectedMembers.value.splice(index, 1);
  } else {
    selectedMembers.value.push(userId);
  }
};

const handleCreate = () => {
  emit('create', {
    name: formData.value.name,
    description: formData.value.description,
    type: formData.value.type,
    members: selectedMembers.value
  });
};
</script>