<template>
  <div class="flex h-screen bg-gray-100 overflow-hidden">
    <!-- Sidebar - Conversation List -->
    <div
      :class="[
        'bg-white border-r border-gray-200 flex flex-col transition-all duration-300',
        showSidebar ? 'w-full md:w-96' : 'w-0 md:w-96',
        activeConversation && !showSidebar ? 'hidden md:flex' : 'flex'
      ]"
    >
      <!-- Header -->
      <div class="p-4 border-b border-gray-200">
        <div class="flex items-center justify-between mb-4">
          <h1 class="text-2xl font-bold text-gray-800">Messages</h1>
          <button
            @click="showCreateGroupModal = true"
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
            v-for="tab in conversationTabs"
            :key="tab.value"
            @click="activeTab = tab.value"
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
        <div class="relative">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search conversations..."
            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
          <svg class="absolute left-3 top-2.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
        </div>
      </div>

      <!-- Online Users Section -->
      <div v-if="onlineUsers.length > 0" class="p-4 border-b border-gray-100">
        <h3 class="text-xs font-semibold text-gray-500 uppercase mb-2">Online Now</h3>
        <div class="flex space-x-3 overflow-x-auto pb-2">
          <div
            v-for="user in onlineUsers"
            :key="user.id"
            @click="startPrivateChat(user)"
            class="flex flex-col items-center cursor-pointer flex-shrink-0"
          >
            <div class="relative">
              <img :src="user.avatar" :alt="user.name" class="w-12 h-12 rounded-full object-cover border-2 border-green-500" />
              <span class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 border-2 border-white rounded-full"></span>
            </div>
            <span class="text-xs text-gray-700 mt-1 max-w-[60px] truncate">{{ user.name }}</span>
          </div>
        </div>
      </div>

      <!-- Conversation List -->
      <div class="flex-1 overflow-y-auto">
        <div
          v-for="conversation in filteredConversations"
          :key="conversation.id"
          @click="selectConversation(conversation)"
          :class="[
            'flex items-center p-4 hover:bg-gray-50 cursor-pointer border-b border-gray-100 transition-colors',
            activeConversation?.id === conversation.id ? 'bg-blue-50' : ''
          ]"
        >
          <!-- Avatar -->
          <div class="relative flex-shrink-0">
            <!-- Group Avatar (3 members combined) -->
            <div v-if="conversation.type === 'group' && !conversation.avatar" class="relative w-12 h-12">
              <img
                v-for="(member, idx) in conversation.members?.slice(0, 3)"
                :key="member.id"
                :src="member.avatar"
                :alt="member.name"
                :class="[
                  'absolute w-7 h-7 rounded-full object-cover border-2 border-white',
                  idx === 0 ? 'top-0 left-0 z-30' : '',
                  idx === 1 ? 'top-0 right-0 z-20' : '',
                  idx === 2 ? 'bottom-0 left-3 z-10' : ''
                ]"
              />
            </div>
            
            <!-- Single Avatar -->
            <img
              v-else
              :src="conversation.avatar || conversation.members?.[0]?.avatar"
              :alt="conversation.name"
              class="w-12 h-12 rounded-full object-cover"
            />
            
            <!-- Online Indicator -->
            <span
              v-if="conversation.isOnline && conversation.type === 'private'"
              class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 border-2 border-white rounded-full"
            ></span>
            
            <!-- Blocked Indicator -->
            <span
              v-if="conversation.isBlocked"
              class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 border-2 border-white rounded-full flex items-center justify-center"
            >
              <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M13.477 14.89A6 6 0 015.11 6.524l8.367 8.368zm1.414-1.414L6.524 5.11a6 6 0 018.367 8.367zM18 10a8 8 0 11-16 0 8 8 0 0116 0z" clip-rule="evenodd" />
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
              <span
                v-if="conversation.unreadCount > 0"
                class="ml-2 bg-blue-500 text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center flex-shrink-0"
              >
                {{ conversation.unreadCount }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Chat Area -->
    <div
      :class="[
        'flex-1 flex flex-col bg-gray-50 transition-all duration-300',
        !activeConversation ? 'hidden md:flex' : 'flex'
      ]"
    >
      <template v-if="activeConversation">
        <!-- Chat Header -->
        <div class="bg-white border-b border-gray-200 p-4 flex items-center justify-between">
          <div class="flex items-center">
            <!-- Back Button (Mobile) -->
            <button
              @click="closeChatOnMobile"
              class="md:hidden mr-3 text-gray-600 hover:text-gray-800"
            >
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
              </svg>
            </button>

            <img
              :src="activeConversation.avatar || activeConversation.members?.[0]?.avatar"
              :alt="activeConversation.name"
              class="w-10 h-10 rounded-full object-cover"
            />
            <div class="ml-3">
              <h2 class="text-lg font-semibold text-gray-900">{{ activeConversation.name }}</h2>
              <p class="text-xs text-gray-500">
                <template v-if="activeConversation.type === 'group'">
                  {{ activeConversation.members?.length }} members
                </template>
                <template v-else>
                  {{ activeConversation.isOnline ? 'Online' : 'Offline' }}
                </template>
              </p>
            </div>
          </div>

          <!-- Header Actions -->
          <div class="flex items-center space-x-2">
            <button
              @click="toggleRightPanel"
              class="p-2 text-gray-600 hover:bg-gray-100 rounded-full transition-colors"
            >
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Messages Area -->
        <div class="flex-1 overflow-y-auto p-4 space-y-4" ref="messageContainer">
          <div
            v-for="message in messages"
            :key="message.id"
            :class="[
              'flex',
              message.isMine ? 'justify-end' : 'justify-start'
            ]"
          >
            <div :class="['max-w-xs lg:max-w-md xl:max-w-lg', message.isMine ? '' : 'flex items-end space-x-2']">
              <!-- Other user avatar -->
              <img
                v-if="!message.isMine"
                :src="message.senderAvatar"
                :alt="message.senderName"
                class="w-8 h-8 rounded-full object-cover flex-shrink-0"
              />

              <div class="flex-1">
                <!-- Sender Name (for group chats) -->
                <p v-if="!message.isMine && activeConversation.type === 'group'" class="text-xs text-gray-500 mb-1 ml-1">
                  {{ message.senderName }}
                </p>

                <!-- Reply Preview -->
                <div
                  v-if="message.replyTo"
                  :class="[
                    'text-xs p-2 rounded-t-lg border-l-4',
                    message.isMine ? 'bg-blue-100 border-blue-500' : 'bg-gray-200 border-gray-500'
                  ]"
                >
                  <p class="font-semibold">{{ message.replyTo.senderName }}</p>
                  <p class="text-gray-600 truncate">{{ message.replyTo.text }}</p>
                </div>

                <!-- Message Bubble -->
                <div
                  @click="openMessageDetails(message)"
                  @contextmenu.prevent="openMessageMenu($event, message)"
                  :class="[
                    'rounded-lg p-3 shadow-sm cursor-pointer relative group',
                    message.isMine ? 'bg-blue-500 text-white' : 'bg-white text-gray-800',
                    message.replyTo ? 'rounded-t-none' : '',
                    message.isDeleted ? 'italic opacity-60' : ''
                  ]"
                >
                  <!-- Message Actions (hover) -->
                  <div
                    v-if="!message.isDeleted"
                    :class="[
                      'absolute top-0 hidden group-hover:flex items-center space-x-1 p-1 bg-white rounded-lg shadow-lg border border-gray-200',
                      message.isMine ? '-left-24' : '-right-24'
                    ]"
                  >
                    <button
                      @click.stop="replyToMessage(message)"
                      class="p-1 hover:bg-gray-100 rounded"
                      title="Reply"
                    >
                      <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                      </svg>
                    </button>
                    <button
                      v-if="message.isMine"
                      @click.stop="editMessage(message)"
                      class="p-1 hover:bg-gray-100 rounded"
                      title="Edit"
                    >
                      <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                      </svg>
                    </button>
                    <button
                      @click.stop="showDeleteMenu(message)"
                      class="p-1 hover:bg-gray-100 rounded"
                      title="Delete"
                    >
                      <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                    </button>
                  </div>

                  <!-- Edited Badge -->
                  <span v-if="message.isEdited && !message.isDeleted" class="text-xs opacity-70 mr-2">(edited)</span>

                  <!-- Text Content -->
                  <p v-if="!message.isDeleted" class="text-sm break-words">{{ message.text }}</p>
                  <p v-else class="text-sm">{{ message.isMine ? 'You deleted this message' : 'This message was deleted' }}</p>

                  <!-- File Attachment -->
                  <div v-if="message.file && !message.isDeleted" class="mt-2">
                    <img
                      v-if="message.file.type === 'image'"
                      :src="message.file.url"
                      alt="Image"
                      class="rounded-lg max-w-full"
                    />
                    <div v-else class="flex items-center space-x-2 p-2 bg-black bg-opacity-10 rounded">
                      <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8 4a3 3 0 00-3 3v4a5 5 0 0010 0V7a1 1 0 112 0v4a7 7 0 11-14 0V7a5 5 0 0110 0v4a3 3 0 11-6 0V7a1 1 0 012 0v4a1 1 0 102 0V7a3 3 0 00-3-3z" clip-rule="evenodd" />
                      </svg>
                      <span class="text-sm">{{ message.file.name }}</span>
                    </div>
                  </div>
                </div>

                <!-- Message Footer: Time, Status, Seen By -->
                <div class="flex items-center justify-between mt-1 px-1">
                  <span class="text-xs text-gray-500">{{ message.time }}</span>
                  
                  <div class="flex items-center space-x-2">
                    <!-- Message Status (for sent messages) -->
                    <div v-if="message.isMine && !message.isDeleted" class="flex items-center space-x-1">
                      <!-- Single tick - Sent -->
                      <svg v-if="message.status === 'sent'" class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                      </svg>
                      
                      <!-- Double tick - Delivered -->
                      <div v-else-if="message.status === 'delivered'" class="flex -space-x-1">
                        <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                          <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                        <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                          <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                      </div>
                      
                      <!-- Double tick (colored) - Seen -->
                      <div v-else-if="message.status === 'seen'" class="flex -space-x-1">
                        <svg class="w-4 h-4 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                          <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                        <svg class="w-4 h-4 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                          <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                      </div>
                    </div>

                    <!-- Seen By Avatars (Group Messages) -->
                    <button
                      v-if="message.isMine && message.seenBy && message.seenBy.length > 0"
                      @click.stop="showSeenByModal(message)"
                      class="flex -space-x-2 hover:opacity-80 transition-opacity"
                    >
                      <img
                        v-for="user in message.seenBy.slice(0, 3)"
                        :key="user.id"
                        :src="user.avatar"
                        :alt="user.name"
                        class="w-5 h-5 rounded-full border-2 border-white object-cover"
                        :title="user.name"
                      />
                      <span v-if="message.seenBy.length > 3" class="text-xs text-gray-500 ml-1">
                        +{{ message.seenBy.length - 3 }}
                      </span>
                    </button>
                  </div>
                </div>

                <!-- Reactions -->
                <div
                  v-if="message.reactions && message.reactions.length > 0"
                  class="flex flex-wrap gap-1 mt-1"
                  :class="message.isMine ? 'justify-end' : ''"
                >
                  <button
                    v-for="reaction in message.reactions"
                    :key="reaction.emoji"
                    @click.stop="openReactionModal(message, reaction)"
                    class="flex items-center space-x-1 bg-white border border-gray-200 rounded-full px-2 py-1 text-xs hover:bg-gray-50 transition-colors shadow-sm"
                  >
                    <span>{{ reaction.emoji }}</span>
                    <span class="font-semibold text-gray-700">{{ reaction.count }}</span>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Reply Preview -->
        <div v-if="replyingTo" class="bg-blue-50 border-t border-blue-200 p-3 flex items-center justify-between">
          <div class="flex items-center space-x-3 flex-1 min-w-0">
            <div class="w-1 h-12 bg-blue-500 rounded-full"></div>
            <div class="flex-1 min-w-0">
              <p class="text-xs font-semibold text-blue-700">Replying to {{ replyingTo.senderName }}</p>
              <p class="text-sm text-gray-700 truncate">{{ replyingTo.text }}</p>
            </div>
          </div>
          <button @click="cancelReply" class="text-gray-500 hover:text-gray-700">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Edit Preview -->
        <div v-if="editingMessage" class="bg-yellow-50 border-t border-yellow-200 p-3 flex items-center justify-between">
          <div class="flex items-center space-x-3 flex-1 min-w-0">
            <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
            <div class="flex-1 min-w-0">
              <p class="text-xs font-semibold text-yellow-700">Editing message</p>
              <p class="text-sm text-gray-700 truncate">{{ editingMessage.text }}</p>
            </div>
          </div>
          <button @click="cancelEdit" class="text-gray-500 hover:text-gray-700">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Message Input -->
        <div class="bg-white border-t border-gray-200 p-4">
          <div
            v-if="activeConversation.isBlocked"
            class="text-center py-4 text-gray-500"
          >
            <svg class="w-8 h-8 mx-auto mb-2 text-red-500" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M13.477 14.89A6 6 0 015.11 6.524l8.367 8.368zm1.414-1.414L6.524 5.11a6 6 0 018.367 8.367zM18 10a8 8 0 11-16 0 8 8 0 0116 0z" clip-rule="evenodd" />
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
              <textarea
                v-model="newMessage"
                @keydown.enter.prevent="handleSendMessage"
                placeholder="Type a message..."
                rows="1"
                class="w-full px-4 py-2 border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"
              ></textarea>
            </div>

            <!-- Emoji Button -->
            <button class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-full transition-colors">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </button>

            <!-- Send Button -->
            <button
              @click="handleSendMessage"
              :disabled="!newMessage.trim()"
              :class="[
                'p-2 rounded-full transition-colors',
                newMessage.trim()
                  ? 'bg-blue-500 text-white hover:bg-blue-600'
                  : 'bg-gray-200 text-gray-400 cursor-not-allowed'
              ]"
            >
              <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" />
              </svg>
            </button>
          </div>
        </div>
      </template>

      <!-- Empty State -->
      <div v-else class="flex-1 flex items-center justify-center">
        <div class="text-center text-gray-500">
          <svg class="w-24 h-24 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
          </svg>
          <h3 class="text-xl font-semibold mb-2">Select a conversation</h3>
          <p>Choose a conversation from the list to start messaging</p>
        </div>
      </div>
    </div>

    <!-- Right Panel (Group Info, Media, etc.) -->
    <div
      v-if="showRightPanel && activeConversation"
      class="hidden lg:block w-80 bg-white border-l border-gray-200 overflow-y-auto"
    >
      <div class="p-6">
        <!-- Conversation Info -->
        <div class="text-center mb-6">
          <img
            :src="activeConversation.avatar || activeConversation.members?.[0]?.avatar"
            :alt="activeConversation.name"
            class="w-24 h-24 rounded-full mx-auto mb-3 object-cover"
          />
          <h3 class="text-xl font-semibold text-gray-900">{{ activeConversation.name }}</h3>
          <p class="text-sm text-gray-500">
            <template v-if="activeConversation.type === 'group'">
              {{ activeConversation.members?.length }} members
            </template>
            <template v-else>
              {{ activeConversation.isOnline ? 'Online' : 'Offline' }}
            </template>
          </p>
        </div>

        <!-- Tabs for Media/Files/Links -->
        <div class="flex border-b border-gray-200 mb-4">
          <button
            v-for="tab in rightPanelTabs"
            :key="tab.value"
            @click="activeRightTab = tab.value"
            :class="[
              'flex-1 py-2 text-sm font-medium border-b-2 transition-colors',
              activeRightTab === tab.value
                ? 'border-blue-500 text-blue-600'
                : 'border-transparent text-gray-500 hover:text-gray-700'
            ]"
          >
            {{ tab.label }}
          </button>
        </div>

        <!-- Tab Content -->
        <div v-if="activeRightTab === 'members' && activeConversation.type === 'group'" class="mb-6">
          <!-- Add Member Button -->
          <button
            @click="showAddMemberModal = true"
            class="w-full mb-4 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors flex items-center justify-center space-x-2"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            <span>Add Member</span>
          </button>

          <h4 class="text-sm font-semibold text-gray-700 mb-3">Members</h4>
          <div class="space-y-2">
            <div
              v-for="member in activeConversation.members"
              :key="member.id"
              class="flex items-center justify-between p-2 hover:bg-gray-50 rounded group"
            >
              <div class="flex items-center">
                <img :src="member.avatar" :alt="member.name" class="w-8 h-8 rounded-full object-cover" />
                <div class="ml-2">
                  <p class="text-sm font-medium text-gray-900">{{ member.name }}</p>
                  <p class="text-xs text-gray-500">{{ member.role }}</p>
                </div>
              </div>
              
              <!-- Member Actions -->
              <div class="hidden group-hover:flex items-center space-x-1">
                <button
                  v-if="member.role === 'Member'"
                  @click="makeAdmin(member)"
                  class="p-1 text-blue-600 hover:bg-blue-50 rounded"
                  title="Make Admin"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                  </svg>
                </button>
                <button
                  v-if="member.role === 'Admin'"
                  @click="removeAdmin(member)"
                  class="p-1 text-yellow-600 hover:bg-yellow-50 rounded"
                  title="Remove Admin"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 12H6" />
                  </svg>
                </button>
                <button
                  v-if="member.role !== 'Super Admin'"
                  @click="removeMember(member)"
                  class="p-1 text-red-600 hover:bg-red-50 rounded"
                  title="Remove"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Media Tab -->
        <div v-if="activeRightTab === 'media'" class="grid grid-cols-3 gap-2 mb-6">
          <div v-for="i in 9" :key="i" class="aspect-square bg-gray-200 rounded-lg overflow-hidden">
            <img :src="`https://picsum.photos/200/200?random=${i}`" class="w-full h-full object-cover" />
          </div>
        </div>

        <!-- Files Tab -->
        <div v-if="activeRightTab === 'files'" class="space-y-2 mb-6">
          <div v-for="i in 5" :key="i" class="flex items-center p-2 hover:bg-gray-50 rounded">
            <svg class="w-8 h-8 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M8 4a3 3 0 00-3 3v4a5 5 0 0010 0V7a1 1 0 112 0v4a7 7 0 11-14 0V7a5 5 0 0110 0v4a3 3 0 11-6 0V7a1 1 0 012 0v4a1 1 0 102 0V7a3 3 0 00-3-3z" clip-rule="evenodd" />
            </svg>
            <div class="ml-3 flex-1">
              <p class="text-sm font-medium text-gray-900">Document {{ i }}.pdf</p>
              <p class="text-xs text-gray-500">2.5 MB</p>
            </div>
          </div>
        </div>

        <!-- Links Tab -->
        <div v-if="activeRightTab === 'links'" class="space-y-2 mb-6">
          <div v-for="i in 3" :key="i" class="p-2 hover:bg-gray-50 rounded">
            <p class="text-sm text-blue-600 hover:underline cursor-pointer">https://example.com/link{{ i }}</p>
            <p class="text-xs text-gray-500 mt-1">Shared 2 days ago</p>
          </div>
        </div>

        <!-- Actions -->
        <div class="space-y-2">
          <button class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded flex items-center">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            Search in conversation
          </button>
          
          <button class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded flex items-center">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
            Mute notifications
          </button>

          <button
            v-if="activeConversation.type === 'group'"
            @click="leaveGroup"
            class="w-full text-left px-4 py-2 text-sm text-yellow-600 hover:bg-yellow-50 rounded flex items-center"
          >
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
            Leave group
          </button>
          
          <button class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 rounded flex items-center">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
            </svg>
            Block user
          </button>
        </div>
      </div>
    </div>

    <!-- Create Group Modal -->
    <div
      v-if="showCreateGroupModal"
      @click="showCreateGroupModal = false"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
    >
      <div
        @click.stop
        class="bg-white rounded-lg shadow-xl max-w-md w-full max-h-[80vh] overflow-hidden"
      >
        <div class="p-4 border-b border-gray-200 flex items-center justify-between">
          <h3 class="text-lg font-semibold">Create New Group</h3>
          <button @click="showCreateGroupModal = false" class="text-gray-500 hover:text-gray-700">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        <div class="p-6 overflow-y-auto">
          <!-- Group Name -->
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Group Name</label>
            <input
              v-model="newGroupName"
              type="text"
              placeholder="Enter group name"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <!-- Group Description -->
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Description (Optional)</label>
            <textarea
              v-model="newGroupDescription"
              placeholder="Enter group description"
              rows="3"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            ></textarea>
          </div>

          <!-- Group Type -->
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Group Type</label>
            <div class="flex space-x-4">
              <label class="flex items-center">
                <input
                  v-model="newGroupType"
                  type="radio"
                  value="private"
                  class="mr-2"
                />
                <span class="text-sm">Private</span>
              </label>
              <label class="flex items-center">
                <input
                  v-model="newGroupType"
                  type="radio"
                  value="public"
                  class="mr-2"
                />
                <span class="text-sm">Public</span>
              </label>
            </div>
          </div>

          <!-- Select Members -->
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Add Members</label>
            <div class="max-h-48 overflow-y-auto border border-gray-300 rounded-lg">
              <div
                v-for="user in availableUsers"
                :key="user.id"
                @click="toggleUserSelection(user)"
                :class="[
                  'flex items-center p-3 hover:bg-gray-50 cursor-pointer',
                  selectedUsers.includes(user.id) ? 'bg-blue-50' : ''
                ]"
              >
                <input
                  type="checkbox"
                  :checked="selectedUsers.includes(user.id)"
                  class="mr-3"
                />
                <img :src="user.avatar" :alt="user.name" class="w-8 h-8 rounded-full object-cover" />
                <span class="ml-3 text-sm font-medium text-gray-900">{{ user.name }}</span>
              </div>
            </div>
          </div>

          <!-- Create Button -->
          <button
            @click="createGroup"
            :disabled="!newGroupName.trim() || selectedUsers.length === 0"
            :class="[
              'w-full py-2 rounded-lg font-medium transition-colors',
              newGroupName.trim() && selectedUsers.length > 0
                ? 'bg-blue-500 text-white hover:bg-blue-600'
                : 'bg-gray-300 text-gray-500 cursor-not-allowed'
            ]"
          >
            Create Group
          </button>
        </div>
      </div>
    </div>

    <!-- Add Member Modal -->
    <div
      v-if="showAddMemberModal"
      @click="showAddMemberModal = false"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
    >
      <div
        @click.stop
        class="bg-white rounded-lg shadow-xl max-w-md w-full"
      >
        <div class="p-4 border-b border-gray-200 flex items-center justify-between">
          <h3 class="text-lg font-semibold">Add Members</h3>
          <button @click="showAddMemberModal = false" class="text-gray-500 hover:text-gray-700">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        <div class="p-6 max-h-96 overflow-y-auto">
          <div
            v-for="user in availableUsers"
            :key="user.id"
            @click="addMemberToGroup(user)"
            class="flex items-center p-3 hover:bg-gray-50 cursor-pointer rounded"
          >
            <img :src="user.avatar" :alt="user.name" class="w-10 h-10 rounded-full object-cover" />
            <span class="ml-3 text-sm font-medium text-gray-900">{{ user.name }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Seen By Modal -->
    <div
      v-if="showSeenByModalVisible"
      @click="showSeenByModalVisible = false"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
    >
      <div
        @click.stop
        class="bg-white rounded-lg shadow-xl max-w-md w-full max-h-96 overflow-hidden"
      >
        <div class="p-4 border-b border-gray-200 flex items-center justify-between">
          <h3 class="text-lg font-semibold">Seen By</h3>
          <button @click="showSeenByModalVisible = false" class="text-gray-500 hover:text-gray-700">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        <div class="overflow-y-auto max-h-80">
          <div
            v-for="user in currentSeenBy"
            :key="user.id"
            class="flex items-center p-4 hover:bg-gray-50"
          >
            <img :src="user.avatar" :alt="user.name" class="w-10 h-10 rounded-full object-cover" />
            <div class="ml-3">
              <p class="font-medium text-gray-900">{{ user.name }}</p>
              <p class="text-xs text-gray-500">{{ user.seenAt }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Reaction Modal -->
    <div
      v-if="showReactionModal"
      @click="closeReactionModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
    >
      <div
        @click.stop
        class="bg-white rounded-lg shadow-xl max-w-md w-full max-h-96 overflow-hidden"
      >
        <div class="p-4 border-b border-gray-200 flex items-center justify-between">
          <h3 class="text-lg font-semibold">Reactions</h3>
          <button @click="closeReactionModal" class="text-gray-500 hover:text-gray-700">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        <div class="overflow-y-auto max-h-80">
          <div
            v-for="user in selectedReactionUsers"
            :key="user.id"
            class="flex items-center p-4 hover:bg-gray-50"
          >
            <img :src="user.avatar" :alt="user.name" class="w-10 h-10 rounded-full object-cover" />
            <span class="ml-3 font-medium text-gray-900">{{ user.name }}</span>
            <span class="ml-auto text-2xl">{{ user.reaction }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Message Details Modal -->
    <div
      v-if="showMessageDetailsModal"
      @click="showMessageDetailsModal = false"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
    >
      <div
        @click.stop
        class="bg-white rounded-lg shadow-xl max-w-md w-full"
      >
        <div class="p-4 border-b border-gray-200 flex items-center justify-between">
          <h3 class="text-lg font-semibold">Message Details</h3>
          <button @click="showMessageDetailsModal = false" class="text-gray-500 hover:text-gray-700">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        <div class="p-6" v-if="selectedMessageDetails">
          <div class="mb-4">
            <p class="text-sm font-semibold text-gray-500 mb-1">Message</p>
            <p class="text-gray-900">{{ selectedMessageDetails.text }}</p>
          </div>
          <div class="mb-4">
            <p class="text-sm font-semibold text-gray-500 mb-1">Sent at</p>
            <p class="text-gray-900">{{ selectedMessageDetails.time }}</p>
          </div>
          <div class="mb-4">
            <p class="text-sm font-semibold text-gray-500 mb-1">Status</p>
            <p class="text-gray-900 capitalize">{{ selectedMessageDetails.status }}</p>
          </div>
          <div v-if="selectedMessageDetails.isEdited" class="mb-4">
            <p class="text-sm font-semibold text-gray-500 mb-1">Edited</p>
            <p class="text-gray-900">Yes</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete Message Menu -->
    <div
      v-if="showDeleteMessageMenu"
      @click="showDeleteMessageMenu = false"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
    >
      <div
        @click.stop
        class="bg-white rounded-lg shadow-xl max-w-sm w-full"
      >
        <div class="p-4 border-b border-gray-200">
          <h3 class="text-lg font-semibold">Delete Message</h3>
        </div>
        <div class="p-4">
          <button
            @click="deleteMessageForMe"
            class="w-full text-left px-4 py-3 text-gray-700 hover:bg-gray-100 rounded mb-2"
          >
            Delete for me
          </button>
          <button
            v-if="messageToDelete?.isMine"
            @click="deleteMessageForEveryone"
            class="w-full text-left px-4 py-3 text-red-600 hover:bg-red-50 rounded"
          >
            Delete for everyone
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue';

// Tabs
const conversationTabs = [
  { label: 'All', value: 'all' },
  { label: 'Groups', value: 'group' },
  { label: 'Personal', value: 'private' }
];

const rightPanelTabs = [
  { label: 'Members', value: 'members' },
  { label: 'Media', value: 'media' },
  { label: 'Files', value: 'files' },
  { label: 'Links', value: 'links' }
];

const activeTab = ref('all');
const activeRightTab = ref('members');

// Online Users
const onlineUsers = ref([
  { id: 10, name: 'Alex', avatar: 'https://i.pravatar.cc/150?img=11', isOnline: true },
  { id: 11, name: 'Sam', avatar: 'https://i.pravatar.cc/150?img=12', isOnline: true },
  { id: 12, name: 'Jordan', avatar: 'https://i.pravatar.cc/150?img=13', isOnline: true },
  { id: 13, name: 'Taylor', avatar: 'https://i.pravatar.cc/150?img=14', isOnline: true }
]);

// Available Users for Group Creation
const availableUsers = ref([
  { id: 20, name: 'Chris Evans', avatar: 'https://i.pravatar.cc/150?img=33' },
  { id: 21, name: 'Emma Stone', avatar: 'https://i.pravatar.cc/150?img=34' },
  { id: 22, name: 'Ryan Gosling', avatar: 'https://i.pravatar.cc/150?img=35' },
  { id: 23, name: 'Scarlett Johansson', avatar: 'https://i.pravatar.cc/150?img=36' },
  { id: 24, name: 'Tom Holland', avatar: 'https://i.pravatar.cc/150?img=37' }
]);

// Static Data - Conversations
const conversations = ref([
  {
    id: 1,
    type: 'private',
    name: 'John Doe',
    avatar: 'https://i.pravatar.cc/150?img=1',
    lastMessage: 'Hey! How are you doing?',
    lastMessageTime: '2m',
    unreadCount: 2,
    isOnline: true,
    isBlocked: false,
    created_by: 1
  },
  {
    id: 2,
    type: 'private',
    name: 'Sarah Wilson',
    avatar: 'https://i.pravatar.cc/150?img=5',
    lastMessage: 'See you tomorrow! 👋',
    lastMessageTime: '1h',
    unreadCount: 0,
    isOnline: true,
    isBlocked: false,
    created_by: 1
  },
  {
    id: 3,
    type: 'group',
    name: 'Team Discussion',
    avatar: null,
    lastMessage: 'Alice: The meeting is at 3 PM',
    lastMessageTime: '3h',
    unreadCount: 5,
    isOnline: false,
    isBlocked: false,
    created_by: 1,
    members: [
      { id: 1, name: 'Alice Johnson', avatar: 'https://i.pravatar.cc/150?img=20', role: 'Super Admin' },
      { id: 2, name: 'Bob Smith', avatar: 'https://i.pravatar.cc/150?img=21', role: 'Admin' },
      { id: 3, name: 'Charlie Brown', avatar: 'https://i.pravatar.cc/150?img=22', role: 'Member' },
      { id: 4, name: 'Diana Prince', avatar: 'https://i.pravatar.cc/150?img=23', role: 'Member' }
    ],
    settings: {
      description: 'Team discussion group',
      type: 'private',
      allow_members_to_send_messages: true,
      allow_members_to_add_remove_participants: false,
      allow_members_to_change_group_info: false,
      admins_must_approve_new_members: true
    }
  },
  {
    id: 4,
    type: 'private',
    name: 'Mike Johnson',
    avatar: 'https://i.pravatar.cc/150?img=3',
    lastMessage: 'You cannot message this person',
    lastMessageTime: '1d',
    unreadCount: 0,
    isOnline: false,
    isBlocked: true,
    created_by: 1
  },
  {
    id: 5,
    type: 'private',
    name: 'Emily Davis',
    avatar: 'https://i.pravatar.cc/150?img=9',
    lastMessage: 'Thanks for your help!',
    lastMessageTime: '2d',
    unreadCount: 0,
    isOnline: false,
    isBlocked: false,
    created_by: 1
  },
  {
    id: 6,
    type: 'group',
    name: 'Project Alpha',
    avatar: null,
    lastMessage: 'Tom: Budget approved! 🎉',
    lastMessageTime: '5h',
    unreadCount: 12,
    isOnline: false,
    isBlocked: false,
    created_by: 1,
    members: [
      { id: 5, name: 'Tom Hardy', avatar: 'https://i.pravatar.cc/150?img=30', role: 'Super Admin' },
      { id: 6, name: 'Lisa Ray', avatar: 'https://i.pravatar.cc/150?img=31', role: 'Admin' },
      { id: 7, name: 'Mark Spencer', avatar: 'https://i.pravatar.cc/150?img=32', role: 'Member' }
    ],
    settings: {
      description: 'Project Alpha collaboration',
      type: 'private',
      allow_members_to_send_messages: true,
      allow_members_to_add_remove_participants: true,
      allow_members_to_change_group_info: false,
      admins_must_approve_new_members: true
    }
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
    isEdited: false,
    replyTo: null,
    file: null,
    seenBy: null
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
    isEdited: false,
    replyTo: null,
    file: null,
    seenBy: [
      { id: 1, name: 'John Doe', avatar: 'https://i.pravatar.cc/150?img=1', seenAt: '10:33 AM' }
    ]
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
    isEdited: false,
    replyTo: {
      senderName: 'You',
      text: 'I\'m doing great! Thanks for asking.'
    },
    file: null,
    seenBy: null
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
    isEdited: false,
    replyTo: null,
    file: {
      type: 'image',
      url: 'https://picsum.photos/400/300',
      name: 'screenshot.png'
    },
    seenBy: [
      { id: 1, name: 'John Doe', avatar: 'https://i.pravatar.cc/150?img=1', seenAt: '10:38 AM' }
    ]
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
    isEdited: false,
    replyTo: null,
    file: null,
    seenBy: []
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
    isEdited: false,
    replyTo: null,
    file: null,
    seenBy: null
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
    isEdited: false,
    replyTo: null,
    file: {
      type: 'document',
      url: '#',
      name: 'project-requirements.pdf'
    },
    seenBy: null
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
    isEdited: true,
    replyTo: null,
    file: null,
    seenBy: [
      { id: 1, name: 'John Doe', avatar: 'https://i.pravatar.cc/150?img=1', seenAt: '10:51 AM' }
    ]
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
const replyingTo = ref(null);
const editingMessage = ref(null);

// Create Group Modal
const showCreateGroupModal = ref(false);
const newGroupName = ref('');
const newGroupDescription = ref('');
const newGroupType = ref('private');
const selectedUsers = ref([]);

// Other Modals
const showAddMemberModal = ref(false);
const showSeenByModalVisible = ref(false);
const currentSeenBy = ref([]);
const showMessageDetailsModal = ref(false);
const selectedMessageDetails = ref(null);
const showDeleteMessageMenu = ref(false);
const messageToDelete = ref(null);

// Computed Properties
const filteredConversations = computed(() => {
  let filtered = conversations.value;
  
  // Filter by tab
  if (activeTab.value !== 'all') {
    filtered = filtered.filter(conv => conv.type === activeTab.value);
  }
  
  // Filter by search
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    filtered = filtered.filter(conv => {
      if (conv.name.toLowerCase().includes(query)) {
        return true;
      }
      if (conv.lastMessage.toLowerCase().includes(query)) {
        return true;
      }
      return false;
    });
  }
  
  return filtered;
});

// Methods
const selectConversation = (conversation) => {
  activeConversation.value = conversation;
  showSidebar.value = false;
  
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

const handleSendMessage = () => {
  if (!newMessage.value.trim()) return;
  
  if (editingMessage.value) {
    // Edit existing message
    const msg = messages.value.find(m => m.id === editingMessage.value.id);
    if (msg) {
      msg.text = newMessage.value;
      msg.isEdited = true;
    }
    editingMessage.value = null;
  } else {
    // Send new message
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
      isEdited: false,
      replyTo: replyingTo.value ? {
        senderName: replyingTo.value.senderName,
        text: replyingTo.value.text
      } : null,
      file: null,
      seenBy: []
    };
    
    messages.value.push(newMsg);
    
    // Simulate message status updates
    setTimeout(() => {
      newMsg.status = 'delivered';
    }, 1000);
    
    setTimeout(() => {
      newMsg.status = 'seen';
      newMsg.seenBy = activeConversation.value.type === 'group' 
        ? activeConversation.value.members.slice(0, 2).map(m => ({ ...m, seenAt: 'Just now' }))
        : [{ id: 1, name: activeConversation.value.name, avatar: activeConversation.value.avatar, seenAt: 'Just now' }];
    }, 2000);
    
    replyingTo.value = null;
  }
  
  newMessage.value = '';
  
  nextTick(() => {
    scrollToBottom();
  });
};

const replyToMessage = (message) => {
  replyingTo.value = message;
  editingMessage.value = null;
};

const cancelReply = () => {
  replyingTo.value = null;
};

const editMessage = (message) => {
  editingMessage.value = message;
  newMessage.value = message.text;
  replyingTo.value = null;
};

const cancelEdit = () => {
  editingMessage.value = null;
  newMessage.value = '';
};

const showDeleteMenu = (message) => {
  messageToDelete.value = message;
  showDeleteMessageMenu.value = true;
};

const deleteMessageForMe = () => {
  // Just hide the message locally
  const msg = messages.value.find(m => m.id === messageToDelete.value.id);
  if (msg) {
    messages.value = messages.value.filter(m => m.id !== msg.id);
  }
  showDeleteMessageMenu.value = false;
  messageToDelete.value = null;
};

const deleteMessageForEveryone = () => {
  const msg = messages.value.find(m => m.id === messageToDelete.value.id);
  if (msg) {
    msg.isDeleted = true;
    msg.text = 'This message was deleted';
  }
  showDeleteMessageMenu.value = false;
  messageToDelete.value = null;
};

const openReactionModal = (message, reaction) => {
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

const showSeenByModal = (message) => {
  currentSeenBy.value = message.seenBy || [];
  showSeenByModalVisible.value = true;
};

const openMessageDetails = (message) => {
  if (message.isDeleted) return;
  selectedMessageDetails.value = message;
  showMessageDetailsModal.value = true;
};

const openMessageMenu = (event, message) => {
  // Context menu for message actions
  console.log('Message right-clicked', message);
};

const scrollToBottom = () => {
  if (messageContainer.value) {
    messageContainer.value.scrollTop = messageContainer.value.scrollHeight;
  }
};

// Group Functions
const toggleUserSelection = (user) => {
  const index = selectedUsers.value.indexOf(user.id);
  if (index > -1) {
    selectedUsers.value.splice(index, 1);
  } else {
    selectedUsers.value.push(user.id);
  }
};

const createGroup = () => {
  if (!newGroupName.value.trim() || selectedUsers.value.length === 0) return;
  
  const newGroup = {
    id: Date.now(),
    type: 'group',
    name: newGroupName.value,
    avatar: null,
    lastMessage: 'Group created',
    lastMessageTime: 'Just now',
    unreadCount: 0,
    isOnline: false,
    isBlocked: false,
    created_by: 1,
    members: selectedUsers.value.map(userId => {
      const user = availableUsers.value.find(u => u.id === userId);
      return { ...user, role: 'Member' };
    }),
    settings: {
      description: newGroupDescription.value,
      type: newGroupType.value,
      allow_members_to_send_messages: true,
      allow_members_to_add_remove_participants: false,
      allow_members_to_change_group_info: false,
      admins_must_approve_new_members: true
    }
  };
  
  conversations.value.unshift(newGroup);
  
  // Reset form
  newGroupName.value = '';
  newGroupDescription.value = '';
  newGroupType.value = 'private';
  selectedUsers.value = [];
  showCreateGroupModal.value = false;
  
  // Select the new group
  selectConversation(newGroup);
};

const startPrivateChat = (user) => {
  // Check if conversation already exists
  const existing = conversations.value.find(c => c.type === 'private' && c.name === user.name);
  if (existing) {
    selectConversation(existing);
    return;
  }
  
  // Create new private conversation
  const newConv = {
    id: Date.now(),
    type: 'private',
    name: user.name,
    avatar: user.avatar,
    lastMessage: '',
    lastMessageTime: 'Just now',
    unreadCount: 0,
    isOnline: user.isOnline,
    isBlocked: false,
    created_by: 1
  };
  
  conversations.value.unshift(newConv);
  selectConversation(newConv);
};

const addMemberToGroup = (user) => {
  if (!activeConversation.value || activeConversation.value.type !== 'group') return;
  
  const isMember = activeConversation.value.members.some(m => m.id === user.id);
  if (isMember) {
    alert('User is already a member');
    return;
  }
  
  activeConversation.value.members.push({ ...user, role: 'Member' });
  showAddMemberModal.value = false;
};

const makeAdmin = (member) => {
  const m = activeConversation.value.members.find(mem => mem.id === member.id);
  if (m) {
    m.role = 'Admin';
  }
};

const removeAdmin = (member) => {
  const m = activeConversation.value.members.find(mem => mem.id === member.id);
  if (m) {
    m.role = 'Member';
  }
};

const removeMember = (member) => {
  if (confirm(`Remove ${member.name} from the group?`)) {
    activeConversation.value.members = activeConversation.value.members.filter(m => m.id !== member.id);
  }
};

const leaveGroup = () => {
  if (confirm('Are you sure you want to leave this group?')) {
    conversations.value = conversations.value.filter(c => c.id !== activeConversation.value.id);
    activeConversation.value = null;
    showRightPanel.value = false;
  }
};

// Lifecycle Hooks
onMounted(() => {
  if (window.innerWidth >= 768 && conversations.value.length > 0) {
    selectConversation(conversations.value[0]);
  }
  
  window.addEventListener('resize', () => {
    if (window.innerWidth >= 768 && !activeConversation.value && conversations.value.length > 0) {
      selectConversation(conversations.value[0]);
    }
  });
});
</script>