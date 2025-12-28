<template>
  <div @click="$emit('close')" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div @click.stop class="bg-white rounded-lg shadow-xl max-w-md w-full">
      <div class="p-4 border-b border-gray-200 flex items-center justify-between">
        <h3 class="text-lg font-semibold">Forward Message</h3>
        <button @click="$emit('close')" class="text-gray-500 hover:text-gray-700">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
      
      <div class="p-4 max-h-96 overflow-y-auto">
        <div
          v-for="conversation in conversations"
          :key="conversation.id"
          @click="toggleConversation(conversation.id)"
          :class="[
            'flex items-center p-3 hover:bg-gray-50 cursor-pointer rounded mb-2',
            selectedConversations.includes(conversation.id) ? 'bg-blue-50' : ''
          ]"
        >
          <input
            type="checkbox"
            :checked="selectedConversations.includes(conversation.id)"
            class="mr-3"
          />
          <img :src="conversation.avatar" :alt="conversation.name" class="w-10 h-10 rounded-full object-cover" />
          <span class="ml-3 text-sm font-medium text-gray-900">{{ conversation.name }}</span>
        </div>
      </div>
      
      <div class="p-4 border-t border-gray-200">
        <button
          @click="handleForward"
          :disabled="selectedConversations.length === 0"
          :class="[
            'w-full py-2 rounded-lg font-medium transition-colors',
            selectedConversations.length > 0
              ? 'bg-blue-500 text-white hover:bg-blue-600'
              : 'bg-gray-300 text-gray-500 cursor-not-allowed'
          ]"
        >
          Forward to {{ selectedConversations.length }} conversation(s)
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';

const props = defineProps(['conversations', 'message']);
const emit = defineEmits(['close', 'forward']);

const selectedConversations = ref([]);

const toggleConversation = (id) => {
  const index = selectedConversations.value.indexOf(id);
  if (index > -1) {
    selectedConversations.value.splice(index, 1);
  } else {
    selectedConversations.value.push(id);
  }
};

const handleForward = () => {
  emit('forward', {
    message: props.message,
    conversationIds: selectedConversations.value
  });
};
</script>