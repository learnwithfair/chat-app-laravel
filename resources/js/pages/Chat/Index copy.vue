<template>
  <div class="flex h-screen bg-gray-100 overflow-hidden">
    <!-- Sidebar - Conversation List -->
    <div :class="[
      'bg-white border-r border-gray-200 flex flex-col transition-all duration-300',
      showSidebar ? 'w-full md:w-96' : 'w-0 md:w-96',
      activeConversation && !showSidebar ? 'hidden md:flex' : 'flex'
    ]">
      <!-- Header -->
      <div class="p-4 border-b border-gray-200">
        <h1 class="text-2xl font-bold text-gray-800 mb-4">Messages</h1>

        <!-- Search Bar -->
        <div class="relative">
          <input v-model="searchQuery" type="text" placeholder="Search conversations..."
            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" />
          <svg class="absolute left-3 top-2.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor"
            viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
        </div>
      </div>

      <!-- Conversation List -->
      <div class="flex-1 overflow-y-auto">
        <div v-for="conversation in filteredConversations" :key="conversation.id"
          @click="selectConversation(conversation)" :class="[
            'flex items-center p-4 hover:bg-gray-50 cursor-pointer border-b border-gray-100 transition-colors',
            activeConversation?.id === conversation.id ? 'bg-blue-50' : ''
          ]">
          <!-- Avatar -->
          <div class="relative flex-shrink-0">
            <img :src="conversation.avatar" :alt="conversation.name" class="w-12 h-12 rounded-full object-cover" />
            <!-- Online Indicator -->
            <span v-if="conversation.isOnline"
              class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 border-2 border-white rounded-full"></span>
            <!-- Blocked Indicator -->
            <span v-if="conversation.isBlocked"
              class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 border-2 border-white rounded-full flex items-center justify-center">
              <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                  d="M13.477 14.89A6 6 0 015.11 6.524l8.367 8.368zm1.414-1.414L6.524 5.11a6 6 0 018.367 8.367zM18 10a8 8 0 11-16 0 8 8 0 0116 0z"
                  clip-rule="evenodd" />
              </svg>
            </span>
          </div>

          <!-- Conversation Info -->
          <div class="flex-1 ml-3 min-w-0">
            <div class="flex items-center justify-between mb-1">
              <h3 class="text-sm font-semibold text-gray-900 truncate">{{ conversation.name }}</h3>
              <span class="text-xs text-gray-500">{{ conversation.lastMessageTime }}</span>
            </div>
            <div class="flex items-center justify-between">
              <p class="text-sm text-gray-600 truncate">{{ conversation.lastMessage }}</p>
              <span v-if="conversation.unreadCount > 0"
                class="ml-2 bg-blue-500 text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center flex-shrink-0">
                {{ conversation.unreadCount }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Chat Area -->
    <div :class="[
      'flex-1 flex flex-col bg-gray-50 transition-all duration-300',
      !activeConversation ? 'hidden md:flex' : 'flex'
    ]">
      <template v-if="activeConversation">
        <!-- Chat Header -->
        <div class="bg-white border-b border-gray-200 p-4 flex items-center justify-between">
          <div class="flex items-center">
            <!-- Back Button (Mobile) -->
            <button @click="closeChatOnMobile" class="md:hidden mr-3 text-gray-600 hover:text-gray-800">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
              </svg>
            </button>

            <img :src="activeConversation.avatar" :alt="activeConversation.name"
              class="w-10 h-10 rounded-full object-cover" />
            <div class="ml-3">
              <h2 class="text-lg font-semibold text-gray-900">{{ activeConversation.name }}</h2>
              <p class="text-xs text-gray-500">
                {{ activeConversation.isOnline ? 'Online' : 'Offline' }}
              </p>
            </div>
          </div>

          <!-- Header Actions -->
          <div class="flex items-center space-x-2">
            <button @click="toggleRightPanel"
              class="p-2 text-gray-600 hover:bg-gray-100 rounded-full transition-colors">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Messages Area -->
        <div class="flex-1 overflow-y-auto p-4 space-y-4" ref="messageContainer">
          <div v-for="message in messages" :key="message.id" :class="[
            'flex',
            message.isMine ? 'justify-end' : 'justify-start'
          ]">
            <div :class="['max-w-xs lg:max-w-md xl:max-w-lg', message.isMine ? '' : 'flex items-end space-x-2']">
              <!-- Other user avatar -->
              <img v-if="!message.isMine" :src="message.senderAvatar" :alt="message.senderName"
                class="w-8 h-8 rounded-full object-cover flex-shrink-0" />

              <div>
                <!-- Sender Name (for group chats) -->
                <p v-if="!message.isMine && activeConversation.isGroup" class="text-xs text-gray-500 mb-1 ml-1">
                  {{ message.senderName }}
                </p>

                <!-- Reply Preview -->
                <div v-if="message.replyTo" :class="[
                  'text-xs p-2 rounded-t-lg border-l-4',
                  message.isMine ? 'bg-blue-100 border-blue-500' : 'bg-gray-200 border-gray-500'
                ]">
                  <p class="font-semibold">{{ message.replyTo.senderName }}</p>
                  <p class="text-gray-600 truncate">{{ message.replyTo.text }}</p>
                </div>

                <!-- Message Bubble -->
                <div :class="[
                  'rounded-lg p-3 shadow-sm',
                  message.isMine ? 'bg-blue-500 text-white' : 'bg-white text-gray-800',
                  message.replyTo ? 'rounded-t-none' : '',
                  message.isDeleted ? 'italic opacity-60' : ''
                ]">
                  <!-- Text Content -->
                  <p v-if="!message.isDeleted" class="text-sm break-words">{{ message.text }}</p>
                  <p v-else class="text-sm">{{ message.isMine ? 'You deleted this message' : 'This message was deleted'
                    }}</p>

                  <!-- File Attachment -->
                  <div v-if="message.file && !message.isDeleted" class="mt-2">
                    <img v-if="message.file.type === 'image'" :src="message.file.url" alt="Image"
                      class="rounded-lg max-w-full" />
                    <div v-else class="flex items-center space-x-2 p-2 bg-black bg-opacity-10 rounded">
                      <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                          d="M8 4a3 3 0 00-3 3v4a5 5 0 0010 0V7a1 1 0 112 0v4a7 7 0 11-14 0V7a5 5 0 0110 0v4a3 3 0 11-6 0V7a1 1 0 012 0v4a1 1 0 102 0V7a3 3 0 00-3-3z"
                          clip-rule="evenodd" />
                      </svg>
                      <span class="text-sm">{{ message.file.name }}</span>
                    </div>
                  </div>

                  <!-- Message Footer -->
                  <div class="flex items-center justify-between mt-1 text-xs">
                    <span :class="message.isMine ? 'text-blue-100' : 'text-gray-500'">
                      {{ message.time }}
                    </span>

                    <!-- Message Status (for sent messages) -->
                    <div v-if="message.isMine" class="flex items-center space-x-1">
                      <!-- Single tick - Sent -->
                      <svg v-if="message.status === 'sent'" class="w-4 h-4 text-blue-100" fill="currentColor"
                        viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                          d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                          clip-rule="evenodd" />
                      </svg>

                      <!-- Double tick - Delivered -->
                      <div v-else-if="message.status === 'delivered'" class="flex -space-x-1">
                        <svg class="w-4 h-4 text-blue-100" fill="currentColor" viewBox="0 0 20 20">
                          <path fill-rule="evenodd"
                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                            clip-rule="evenodd" />
                        </svg>
                        <svg class="w-4 h-4 text-blue-100" fill="currentColor" viewBox="0 0 20 20">
                          <path fill-rule="evenodd"
                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                            clip-rule="evenodd" />
                        </svg>
                      </div>

                      <!-- Double tick (colored) - Seen -->
                      <div v-else-if="message.status === 'seen'" class="flex -space-x-1">
                        <svg class="w-4 h-4 text-green-300" fill="currentColor" viewBox="0 0 20 20">
                          <path fill-rule="evenodd"
                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                            clip-rule="evenodd" />
                        </svg>
                        <svg class="w-4 h-4 text-green-300" fill="currentColor" viewBox="0 0 20 20">
                          <path fill-rule="evenodd"
                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                            clip-rule="evenodd" />
                        </svg>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Reactions -->
                <div v-if="message.reactions && message.reactions.length > 0" class="flex flex-wrap gap-1 mt-1"
                  :class="message.isMine ? 'justify-end' : ''">
                  <button v-for="reaction in message.reactions" :key="reaction.emoji"
                    @click="openReactionModal(message, reaction)"
                    class="flex items-center space-x-1 bg-white border border-gray-200 rounded-full px-2 py-1 text-xs hover:bg-gray-50 transition-colors shadow-sm">
                    <span>{{ reaction.emoji }}</span>
                    <span class="font-semibold text-gray-700">{{ reaction.count }}</span>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Message Input -->
        <div class="bg-white border-t border-gray-200 p-4">
          <div v-if="activeConversation.isBlocked" class="text-center py-4 text-gray-500">
            <svg class="w-8 h-8 mx-auto mb-2 text-red-500" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd"
                d="M13.477 14.89A6 6 0 015.11 6.524l8.367 8.368zm1.414-1.414L6.524 5.11a6 6 0 018.367 8.367zM18 10a8 8 0 11-16 0 8 8 0 0116 0z"
                clip-rule="evenodd" />
            </svg>
            <p class="font-semibold">You can't send messages to this conversation</p>
            <p class="text-sm">This user is blocked</p>
          </div>

          <div v-else class="flex items-end space-x-2">
            <!-- Attachment Button -->
            <button class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-full transition-colors">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
            </button>

            <!-- Text Input -->
            <div class="flex-1 relative">
              <textarea v-model="newMessage" @keydown.enter.prevent="sendMessage" placeholder="Type a message..."
                rows="1"
                class="w-full px-4 py-2 border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
            </div>

            <!-- Emoji Button -->
            <button class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-full transition-colors">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </button>

            <!-- Send Button -->
            <button @click="sendMessage" :disabled="!newMessage.trim()" :class="[
              'p-2 rounded-full transition-colors',
              newMessage.trim()
                ? 'bg-blue-500 text-white hover:bg-blue-600'
                : 'bg-gray-200 text-gray-400 cursor-not-allowed'
            ]">
              <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                <path
                  d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" />
              </svg>
            </button>
          </div>
        </div>
      </template>

      <!-- Empty State -->
      <div v-else class="flex-1 flex items-center justify-center">
        <div class="text-center text-gray-500">
          <svg class="w-24 h-24 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
          </svg>
          <h3 class="text-xl font-semibold mb-2">Select a conversation</h3>
          <p>Choose a conversation from the list to start messaging</p>
        </div>
      </div>
    </div>

    <!-- Right Panel (Group Info, Media, etc.) -->
    <div v-if="showRightPanel && activeConversation"
      class="hidden lg:block w-80 bg-white border-l border-gray-200 overflow-y-auto">
      <div class="p-6">
        <!-- Conversation Info -->
        <div class="text-center mb-6">
          <img :src="activeConversation.avatar" :alt="activeConversation.name"
            class="w-24 h-24 rounded-full mx-auto mb-3 object-cover" />
          <h3 class="text-xl font-semibold text-gray-900">{{ activeConversation.name }}</h3>
          <p class="text-sm text-gray-500">{{ activeConversation.isOnline ? 'Online' : 'Offline' }}</p>
        </div>

        <!-- Group Members (if group) -->
        <div v-if="activeConversation.isGroup" class="mb-6">
          <h4 class="text-sm font-semibold text-gray-700 mb-3">Members ({{ activeConversation.members?.length || 0 }})
          </h4>
          <div class="space-y-2">
            <div v-for="member in activeConversation.members" :key="member.id"
              class="flex items-center justify-between p-2 hover:bg-gray-50 rounded">
              <div class="flex items-center">
                <img :src="member.avatar" :alt="member.name" class="w-8 h-8 rounded-full object-cover" />
                <div class="ml-2">
                  <p class="text-sm font-medium text-gray-900">{{ member.name }}</p>
                  <p class="text-xs text-gray-500">{{ member.role }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="space-y-2">
          <button class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded flex items-center">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            Search in conversation
          </button>

          <button class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded flex items-center">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
            Mute notifications
          </button>

          <button class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 rounded flex items-center">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
            </svg>
            Block user
          </button>
        </div>
      </div>
    </div>

    <!-- Reaction Modal -->
    <div v-if="showReactionModal" @click="closeReactionModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div @click.stop class="bg-white rounded-lg shadow-xl max-w-md w-full max-h-96 overflow-hidden">
        <div class="p-4 border-b border-gray-200 flex items-center justify-between">
          <h3 class="text-lg font-semibold">Reactions</h3>
          <button @click="closeReactionModal" class="text-gray-500 hover:text-gray-700">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        <div class="overflow-y-auto max-h-80">
          <div v-for="user in selectedReactionUsers" :key="user.id" class="flex items-center p-4 hover:bg-gray-50">
            <img :src="user.avatar" :alt="user.name" class="w-10 h-10 rounded-full object-cover" />
            <span class="ml-3 font-medium text-gray-900">{{ user.name }}</span>
            <span class="ml-auto text-2xl">{{ user.reaction }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue';

// Static Data - Conversations
const conversations = ref([
  {
    id: 1,
    name: 'John Doe',
    avatar: 'https://i.pravatar.cc/150?img=1',
    lastMessage: 'Hey! How are you doing?',
    lastMessageTime: '2m',
    unreadCount: 2,
    isOnline: true,
    isBlocked: false,
    isGroup: false
  },
  {
    id: 2,
    name: 'Sarah Wilson',
    avatar: 'https://i.pravatar.cc/150?img=5',
    lastMessage: 'See you tomorrow! 👋',
    lastMessageTime: '1h',
    unreadCount: 0,
    isOnline: true,
    isBlocked: false,
    isGroup: false
  },
  {
    id: 3,
    name: 'Team Discussion',
    avatar: 'https://i.pravatar.cc/150?img=10',
    lastMessage: 'Alice: The meeting is at 3 PM',
    lastMessageTime: '3h',
    unreadCount: 5,
    isOnline: false,
    isBlocked: false,
    isGroup: true,
    members: [
      { id: 1, name: 'Alice Johnson', avatar: 'https://i.pravatar.cc/150?img=20', role: 'Admin' },
      { id: 2, name: 'Bob Smith', avatar: 'https://i.pravatar.cc/150?img=21', role: 'Member' },
      { id: 3, name: 'Charlie Brown', avatar: 'https://i.pravatar.cc/150?img=22', role: 'Member' },
      { id: 4, name: 'Diana Prince', avatar: 'https://i.pravatar.cc/150?img=23', role: 'Member' }
    ]
  },
  {
    id: 4,
    name: 'Mike Johnson',
    avatar: 'https://i.pravatar.cc/150?img=3',
    lastMessage: 'You cannot message this person',
    lastMessageTime: '1d',
    unreadCount: 0,
    isOnline: false,
    isBlocked: true,
    isGroup: false
  },
  {
    id: 5,
    name: 'Emily Davis',
    avatar: 'https://i.pravatar.cc/150?img=9',
    lastMessage: 'Thanks for your help!',
    lastMessageTime: '2d',
    unreadCount: 0,
    isOnline: false,
    isBlocked: false,
    isGroup: false
  },
  {
    id: 6,
    name: 'Project Alpha',
    avatar: 'https://i.pravatar.cc/150?img=15',
    lastMessage: 'Tom: Budget approved! 🎉',
    lastMessageTime: '5h',
    unreadCount: 12,
    isOnline: false,
    isBlocked: false,
    isGroup: true,
    members: [
      { id: 1, name: 'Tom Hardy', avatar: 'https://i.pravatar.cc/150?img=30', role: 'Super Admin' },
      { id: 2, name: 'Lisa Ray', avatar: 'https://i.pravatar.cc/150?img=31', role: 'Admin' },
      { id: 3, name: 'Mark Spencer', avatar: 'https://i.pravatar.cc/150?img=32', role: 'Member' }
    ]
  },
  {
    id: 7,
    name: 'David Miller',
    avatar: 'https://i.pravatar.cc/150?img=8',
    lastMessage: 'Let\'s catch up soon!',
    lastMessageTime: '3d',
    unreadCount: 0,
    isOnline: true,
    isBlocked: false,
    isGroup: false
  }
]);

// Static Data - Messages
const messages = ref([
  {
    id: 1,
    text: 'Hey! How are you?',
    isMine: false,
    time: '10:30 AM',
    status: 'seen',
    senderName: 'John Doe',
    senderAvatar: 'https://i.pravatar.cc/150?img=1',
    reactions: [
      { emoji: '👍', count: 2 },
      { emoji: '❤️', count: 1 }
    ],
    isDeleted: false,
    replyTo: null,
    file: null
  },
  {
    id: 2,
    text: 'I\'m doing great! Thanks for asking. How about you?',
    isMine: true,
    time: '10:32 AM',
    status: 'seen',
    senderName: 'You',
    senderAvatar: '',
    reactions: [],
    isDeleted: false,
    replyTo: null,
    file: null
  },
  {
    id: 3,
    text: 'Did you get my previous message about the project?',
    isMine: false,
    time: '10:35 AM',
    status: 'delivered',
    senderName: 'John Doe',
    senderAvatar: 'https://i.pravatar.cc/150?img=1',
    reactions: [],
    isDeleted: false,
    replyTo: {
      senderName: 'You',
      text: 'I\'m doing great! Thanks for asking.'
    },
    file: null
  },
  {
    id: 4,
    text: 'Yes! I saw it. Let me check that for you. Here\'s the screenshot.',
    isMine: true,
    time: '10:37 AM',
    status: 'delivered',
    senderName: 'You',
    senderAvatar: '',
    reactions: [
      { emoji: '🔥', count: 1 }
    ],
    isDeleted: false,
    replyTo: null,
    file: {
      type: 'image',
      url: 'https://picsum.photos/400/300',
      name: 'screenshot.png'
    }
  },
  {
    id: 5,
    text: 'This was an important message',
    isMine: true,
    time: '10:40 AM',
    status: 'sent',
    senderName: 'You',
    senderAvatar: '',
    reactions: [],
    isDeleted: true,
    replyTo: null,
    file: null
  },
  {
    id: 6,
    text: 'Perfect! Let me know if you need anything else.',
    isMine: false,
    time: '10:45 AM',
    status: 'delivered',
    senderName: 'John Doe',
    senderAvatar: 'https://i.pravatar.cc/150?img=1',
    reactions: [],
    isDeleted: false,
    replyTo: null,
    file: null
  },
  {
    id: 7,
    text: 'I also have this document that might help.',
    isMine: false,
    time: '10:47 AM',
    status: 'delivered',
    senderName: 'John Doe',
    senderAvatar: 'https://i.pravatar.cc/150?img=1',
    reactions: [],
    isDeleted: false,
    replyTo: null,
    file: {
      type: 'document',
      url: '#',
      name: 'project-requirements.pdf'
    }
  },
  {
    id: 8,
    text: 'Thanks! I\'ll review it and get back to you by end of day.',
    isMine: true,
    time: '10:50 AM',
    status: 'sent',
    senderName: 'You',
    senderAvatar: '',
    reactions: [
      { emoji: '👍', count: 1 }
    ],
    isDeleted: false,
    replyTo: null,
    file: null
  }
]);

// Component State
const searchQuery = ref('');
const activeConversation = ref(null);
const newMessage = ref('');
const showSidebar = ref(true);
const showRightPanel = ref(false);
const showReactionModal = ref(false);
const selectedReactionUsers = ref([]);
const messageContainer = ref(null);

// Computed Properties
const filteredConversations = computed(() => {
  if (!searchQuery.value) return conversations.value;
  
  const query = searchQuery.value.toLowerCase();
  return conversations.value.filter(conv => {
    // Search by conversation name
    if (conv.name.toLowerCase().includes(query)) {
      return true;
    }
    // Search by last message
    if (conv.lastMessage.toLowerCase().includes(query)) {
      return true;
    }
    return false;
  });
});

// Methods
const selectConversation = (conversation) => {
  activeConversation.value = conversation;
  showSidebar.value = false; // Hide sidebar on mobile when chat opens
  
  // Scroll to bottom after selecting conversation
  nextTick(() => {
    scrollToBottom();
  });
};

const closeChatOnMobile = () => {
  showSidebar.value = true;
  activeConversation.value = null;
};

const toggleRightPanel = () => {
  showRightPanel.value = !showRightPanel.value;
};

const sendMessage = () => {
  if (!newMessage.value.trim()) return;
  
  const newMsg = {
    id: Date.now(),
    text: newMessage.value,
    isMine: true,
    time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
    status: 'sent',
    senderName: 'You',
    senderAvatar: '',
    reactions: [],
    isDeleted: false,
    replyTo: null,
    file: null
  };
  
  messages.value.push(newMsg);
  newMessage.value = '';
  
  // Scroll to bottom after sending message
  nextTick(() => {
    scrollToBottom();
  });
  
  // Simulate message status updates
  setTimeout(() => {
    newMsg.status = 'delivered';
  }, 1000);
  
  setTimeout(() => {
    newMsg.status = 'seen';
  }, 2000);
};

const openReactionModal = (message, reaction) => {
  // Mock users who reacted - in real app, fetch from backend
  selectedReactionUsers.value = [
    { 
      id: 1, 
      name: 'Alice Johnson', 
      avatar: 'https://i.pravatar.cc/150?img=20', 
      reaction: reaction.emoji 
    },
    { 
      id: 2, 
      name: 'Bob Smith', 
      avatar: 'https://i.pravatar.cc/150?img=21', 
      reaction: reaction.emoji 
    }
  ];
  
  // If count is more than 2, add more users
  if (reaction.count > 2) {
    for (let i = 3; i <= reaction.count; i++) {
      selectedReactionUsers.value.push({
        id: i,
        name: `User ${i}`,
        avatar: `https://i.pravatar.cc/150?img=${20 + i}`,
        reaction: reaction.emoji
      });
    }
  }
  
  showReactionModal.value = true;
};

const closeReactionModal = () => {
  showReactionModal.value = false;
  selectedReactionUsers.value = [];
};

const scrollToBottom = () => {
  if (messageContainer.value) {
    messageContainer.value.scrollTop = messageContainer.value.scrollHeight;
  }
};

// Lifecycle Hooks
onMounted(() => {
  // Auto-select first conversation for desktop view
  if (window.innerWidth >= 768 && conversations.value.length > 0) {
    selectConversation(conversations.value[0]);
  }
  
  // Listen for window resize to handle responsive behavior
  window.addEventListener('resize', () => {
    if (window.innerWidth >= 768 && !activeConversation.value && conversations.value.length > 0) {
      selectConversation(conversations.value[0]);
    }
  });
});
</script>