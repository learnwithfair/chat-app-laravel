<template>
  <div
    :class="[
      'bg-white border-r border-gray-200 flex flex-col transition-all duration-300 w-full md:w-96',
      activeConversation ? 'hidden md:flex' : 'flex'
    ]"
  >
    <!-- Header -->
    <div class="p-4 border-b border-gray-200">
      <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-bold text-gray-800">Messages</h1>
        <button
          @click="$emit('create-group')"
          class="p-2 text-blue-500 hover:bg-blue-50 rounded-full transition-colors"
          title="Create Group"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
          </svg>
        </button>
      </div>
      
      <!-- Tabs -->
      <div class="flex space-x-1 mb-4 bg-gray-100 rounded-lg p-1">
        <button
          v-for="tab in tabs"
          :key="tab.value"
          @click="$emit('update-tab', tab.value)"
          :class="[
            'flex-1 px-4 py-2 text-sm font-medium rounded-md transition-colors',
            activeTab === tab.value
              ? 'bg-white text-blue-600 shadow-sm'
              : 'text-gray-600 hover:text-gray-800'
          ]"
        >
          {{ tab.label }}
        </button>
      </div>

      <!-- Search Bar -->
      <SearchBar
        :model-value="searchQuery"
        @update:model-value="$emit('update-search', $event)"
      />
    </div>

    <!-- Online Users -->
    <OnlineUsers
      v-if="onlineUsers.length > 0"
      :users="onlineUsers"
      @start-chat="$emit('start-chat', $event)"
    />

    <!-- Conversation List -->
    <div class="flex-1 overflow-y-auto">
      <ConversationItem
        v-for="conversation in conversations"
        :key="conversation.id"
        :conversation="conversation"
        :is-active="activeConversation?.id === conversation.id"
        @select="$emit('select', conversation)"
      />
    </div>
  </div>
</template>

<script setup>
import ConversationItem from './ConversationItem.vue';
import OnlineUsers from './OnlineUsers.vue';
import SearchBar from './SearchBar.vue';

const tabs = [
  { label: 'All', value: 'all' },
  { label: 'Groups', value: 'group' },
  { label: 'Personal', value: 'private' }
];

defineProps({
  conversations: Array,
  activeConversation: Object,
  activeTab: String,
  searchQuery: String,
  onlineUsers: Array
});

defineEmits(['select', 'update-tab', 'update-search', 'create-group', 'start-chat']);
</script>