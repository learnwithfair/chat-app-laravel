<template>
  <div class="flex h-screen bg-gray-100 overflow-hidden">
    <!-- Sidebar - Conversation List -->
    <ConversationList
      :conversations="filteredConversations"
      :active-conversation="activeConversation"
      :active-tab="activeTab"
      :search-query="searchQuery"
      :online-users="onlineUsers"
      :conversation-pagination="conversationPagination"
      @select="selectConversation"
      @update-tab="activeTab = $event"
      @update-search="searchQuery = $event"
      @create-group="openCreateGroupModal"
      @start-chat="startPrivateChat"
      @load-more="loadMoreConversations"
      :show-start-chat-modal="modals.startChat"
      :start-chat-users="startChatUsers"
      :start-chat-loading="startChatLoading"
      :start-chat-pagination="startChatPagination"
      @open-start-chat-modal="openStartChatModal"
      @close-start-chat-modal="modals.startChat = false"
      @search-start-chat-users="searchStartChatUsers"
      @load-more-start-chat-users="loadMoreStartChatUsers"
      @select-start-chat-user="handleStartChatUserSelect"
    />

    <!-- Main Chat Area -->
    <div
      v-if="activeConversation"
      :class="[
        'flex-1 flex flex-col bg-gray-50 transition-all duration-300',
        !activeConversation ? 'hidden md:flex' : 'flex',
      ]"
    >
      <!-- Chat Header with Search -->
      <ChatHeader
        ref="chatHeaderRef"
        :name="activeConversation.name"
        :subtitle="getConversationSubtitle"
        :avatar="getConversationAvatar"
        @back="closeChatOnMobile"
        @search="handleSearchToggle"
        @search-query-change="handleSearchQueryChange"
        @search-next="handleSearchNext"
        @search-previous="handleSearchPrevious"
        @audio-call="handleAudioCall"
        @video-call="handleVideoCall"
        @toggle-info="showRightPanel = !showRightPanel"
      />

      <!-- Message List with Highlighted Search Results -->
      <MessageList
        ref="messageListRef"
        :messages="messages"
        :is-group="activeConversation.type === 'group'"
        :search-query="messageSearchQuery"
        :highlighted-message-id="highlightedMessageId"
        :typing-users="getTypingUsers"
        :message-pagination="messagePagination"
        :conversation="activeConversation"
        @reply="replyToMessage"
        @edit="editMessage"
        @forward="forwardMessage"
        @delete="showDeleteMenu"
        @show-reactions="openReactionModal"
        @show-details="openMessageDetails"
        @show-seen-by="showSeenByModal"
        @add-reaction="handleAddReaction"
        @load-more="loadMoreMessages(activeConversation.id)"
      />

      <!-- Reply Preview -->
      <ReplyPreview v-if="replyingTo" :message="replyingTo" @cancel="cancelReply" />

      <!-- Edit Preview -->
      <EditPreview v-if="editingMessage" :message="editingMessage" @cancel="cancelEdit" />

      <!-- Message Input - UPDATED WITH BLOCK PROPS -->
      <MessageInput
        v-model="newMessage"
        :is-blocked="activeConversation.isBlocked"
        :blocked-by-me="activeConversation.blockedByMe"
        :blocked-by-them="activeConversation.blockedByThem"
        :can-send-message="activeConversation.canSendMessage"
        :conversation-type="activeConversation.type"
        :is-editing="!!editingMessage"
        :conversation-id="activeConversation.id"
        @send="handleSendMessage()"
        @send-voice="handleSendVoice"
        @send-files="handleSendMessage"
        @typing-change="(isTyping) => listenForTyping(activeConversation.id, isTyping)"
        @unblock-user="handleUnblockUser"
      />
    </div>

    <!-- Empty State -->
    <div v-else class="flex-1 flex items-center justify-center">
      <div class="text-center text-gray-500">
        <svg
          class="w-24 h-24 mx-auto mb-4 text-gray-300"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"
          />
        </svg>
        <h3 class="text-xl font-semibold mb-2">Select a conversation</h3>
        <p>Choose a conversation from the list to start messaging</p>
      </div>
    </div>

    <!-- Right Panel - Conversation Info -->
    <!-- <ConversationInfo
      v-if="showRightPanel && activeConversation"
      :conversation="activeConversation"
      :group-members="groupMembers"
      :load-more-group-members="loadMoreGroupMembers || (() => {})"
      :group-members-pagination="groupMembersPagination"
      :active-tab="activeRightTab"
      :conversation-media="conversationMedia"
      :conversation-files="conversationFiles"
      :conversation-links="conversationLinks"
      @update-tab="handleTabChange"
      @add-member="openAddMemberModal"
      @make-admin="makeAdmin"
      @remove-admin="removeAdmin"
      @remove-member="removeMember"
      @leave-group="leaveGroup"
      @update-settings="updateGroupSettings"
      @trigger-search="triggerSearchFromRightPanel"
      @toggle-block="handleToggleBlock"
      @toggle-mute="handleToggleMute"
      @delete-conversation="handleDeleteConversation"
      @delete-group="handleDeleteGroup"
      @update-avatar="handleUpdateAvatar"
      @update-description="handleUpdateDescription"
      @update-name="handleUpdateName"
    /> -->

    <ConversationInfo
      v-if="showRightPanel && activeConversation"
      :conversation="activeConversation"
      :group-members="groupMembers"
      :load-more-group-members="loadMoreGroupMembers || (() => {})"
      :group-members-pagination="groupMembersPagination"
      :active-tab="activeRightTab"
      @update-tab="handleTabChange"
      @add-member="openAddMemberModal"
      @make-admin="makeAdmin"
      @remove-admin="removeAdmin"
      @remove-member="removeMember"
      @leave-group="leaveGroup"
      @update-settings="updateGroupSettings"
      @trigger-search="triggerSearchFromRightPanel"
      @toggle-block="handleToggleBlock"
      @toggle-mute="handleToggleMute"
      @delete-conversation="handleDeleteConversation"
      @delete-group="handleDeleteGroup"
      @update-avatar="handleUpdateAvatar"
      @update-description="handleUpdateDescription"
      @update-name="handleUpdateName"
      @open-media-library="handleOpenMediaLibrary"
    />

    <!-- Modals -->
    <CreateGroupModal
      v-if="modals.createGroup"
      :available-users="availableUsers"
      :pagination="availableUsersPagination"
      @load-more="loadMoreAvailableUsers"
      @close="closeModal('createGroup')"
      @create="createGroup"
    />

    <MediaLibraryModal
      v-if="modals.mediaLibrary"
      :media="mediaLibrary.media"
      :audio="mediaLibrary.audio"
      :files="mediaLibrary.files"
      :links="mediaLibrary.links"
      :loading="mediaLibraryLoading"
      @close="closeModal('mediaLibrary')"
    />

    <AddMemberModal
      v-if="modals.addMember"
      :availableUsers="availableUsers"
      :currentGroupMembers="activeConversation.members"
      @close="closeModal('addMember')"
      @add-multiple="addMembersToGroup"
    />

    <ReactionModal
      v-if="modals.reaction"
      :message-id="reactionModalData.messageId"
      :reactions="reactionModalData.reactions"
      @close="closeReactionModal"
      @fetch-reactions="handleReactionFetch"
    />

    <SeenByModal
      v-if="modals.seenBy"
      :users="currentSeenBy"
      @close="closeModal('seenBy')"
    />

    <MessageDetailsModal
      v-if="modals.messageDetails"
      :message="selectedMessageDetails"
      @close="closeModal('messageDetails')"
    />

    <DeleteMessageModal
      v-if="modals.deleteMessage"
      :message="messageToDelete"
      @close="closeModal('deleteMessage')"
      @delete-for-me="deleteMessageForMe"
      @delete-for-everyone="deleteMessageForEveryone"
    />

    <ForwardMessageModal
      v-if="modals.forwardMessage"
      :conversations="conversations"
      :message="messageToForward"
      @close="closeModal('forwardMessage')"
      @forward="handleForwardMessage"
    />
  </div>
</template>

<script setup>
import { ref, computed, watch } from "vue";
import ConversationList from "@/Components/Chat/Sidebar/ConversationList.vue";
import ChatHeader from "@/Components/Chat/ChatArea/ChatHeader.vue";
import MessageList from "@/Components/Chat/ChatArea/MessageList.vue";
import ReplyPreview from "@/Components/Chat/ChatArea/ReplyPreview.vue";
import EditPreview from "@/Components/Chat/ChatArea/EditPreview.vue";
import MessageInput from "@/Components/Chat/ChatArea/MessageInput.vue";
import ConversationInfo from "@/Components/Chat/RightPanel/ConversationInfo.vue";
import CreateGroupModal from "@/Components/Chat/Modals/CreateGroupModal.vue";
import AddMemberModal from "@/Components/Chat/Modals/AddMemberModal.vue";
import ReactionModal from "@/Components/Chat/Modals/ReactionModal.vue";
import SeenByModal from "@/Components/Chat/Modals/SeenByModal.vue";
import MessageDetailsModal from "@/Components/Chat/Modals/MessageDetailsModal.vue";
import DeleteMessageModal from "@/Components/Chat/Modals/DeleteMessageModal.vue";
import ForwardMessageModal from "@/Components/Chat/Modals/ForwardMessageModal.vue";

import { useChat } from "@/Composables/Chat/useChat";
import { generateAvatar } from "../../Utils/Chat/avatarHelper";
import MediaLibraryModal from "../../components/chat/Modals/MediaLibraryModal.vue";

const {
  // State
  conversations,
  activeConversation,
  messages,
  conversationPagination,
  messagePagination,
  loadMoreConversations,
  loadMoreMessages,
  searchQuery,
  activeTab,
  activeRightTab,
  showRightPanel,
  onlineUsers,
  newMessage,
  replyingTo,
  editingMessage,
  modals,
  selectedReactionUsers,
  currentSeenBy,
  selectedMessageDetails,
  messageToDelete,
  messageToForward,
  typingUsers,
  listenForTyping,

  // Computed
  filteredConversations,
  getConversationSubtitle,
  getConversationAvatar,

  // Methods
  selectConversation,
  closeChatOnMobile,
  startPrivateChat,
  handleSendMessage,
  handleSendVoice,
  replyToMessage,
  cancelReply,
  editMessage,
  cancelEdit,
  forwardMessage,
  handleForwardMessage,
  showDeleteMenu,
  deleteMessageForMe,
  deleteMessageForEveryone,
  openMessageDetails,
  showSeenByModal,
  handleAudioCall,
  handleVideoCall,
  handleAddReaction,

  availableUsers,
  availableUsersPagination,
  fetchAvailableUsers,
  loadMoreAvailableUsers,

  startChatUsers,
  startChatLoading,
  startChatPagination,
  openStartChatModal,
  searchStartChatUsers,
  loadMoreStartChatUsers,
  handleStartChatUserSelect,

  groupMembers,
  groupMembersPagination,
  fetchGroupMembers,
  loadMoreGroupMembers,

  // Media Library
  mediaLibrary,
  mediaLibraryLoading,
  handleOpenMediaLibrary,

  // Reaction
  reactionModalData,
  openReactionModal,
  closeReactionModal,
  handleReactionFetch,

  // Group Management
  openCreateGroupModal,
  createGroup,
  openAddMemberModal,
  addMembersToGroup,
  makeAdmin,
  removeAdmin,
  removeMember,
  leaveGroup,
  updateGroupSettings,

  handleTabChange,
  handleToggleBlock,
  handleToggleMute,
  handleDeleteConversation,
  handleDeleteGroup,
  handleUpdateAvatar,
  handleUpdateDescription,
  handleUpdateName,

  // BLOCK/UNBLOCK - This should come from useChat
  handleUnblockUser,

  // Modal Management
  closeModal,
} = useChat();

// Search functionality (UI-specific logic, stays here)
const chatHeaderRef = ref(null);
const messageListRef = ref(null);
const messageSearchQuery = ref("");
const searchResultsIds = ref([]);
const currentSearchIndex = ref(0);
const highlightedMessageId = ref(null);

const handleSearchToggle = (data) => {
  if (data.isActive) {
    messageSearchQuery.value = data.query;
  } else {
    messageSearchQuery.value = "";
    searchResultsIds.value = [];
    currentSearchIndex.value = 0;
    highlightedMessageId.value = null;
  }
};

const handleSearchQueryChange = (query) => {
  messageSearchQuery.value = query;

  if (!query) {
    searchResultsIds.value = [];
    currentSearchIndex.value = 0;
    highlightedMessageId.value = null;
    chatHeaderRef.value?.setSearchResults(0);
    return;
  }

  const results = messages.value.filter((msg) =>
    msg.text.toLowerCase().includes(query.toLowerCase())
  );

  searchResultsIds.value = results.map((msg) => msg.id);
  currentSearchIndex.value = 0;

  chatHeaderRef.value?.setSearchResults(searchResultsIds.value.length);

  if (searchResultsIds.value.length > 0) {
    highlightedMessageId.value = searchResultsIds.value[0];
    scrollToMessage(searchResultsIds.value[0]);
  }
};

const handleSearchNext = (index) => {
  if (searchResultsIds.value.length === 0) return;
  highlightedMessageId.value = searchResultsIds.value[index];
  scrollToMessage(highlightedMessageId.value);
};

const handleSearchPrevious = (index) => {
  if (searchResultsIds.value.length === 0) return;
  highlightedMessageId.value = searchResultsIds.value[index];
  scrollToMessage(highlightedMessageId.value);
};

const scrollToMessage = (messageId) => {
  console.log("Scrolling to message:", messageId);
};

const triggerSearchFromRightPanel = () => {
  chatHeaderRef.value?.openSearch();
};

const getTypingUsers = computed(() => {
  if (!activeConversation.value) return [];

  const users = typingUsers.value[activeConversation.value.id] || [];

  // Return array of user objects with avatar
  return users.map((user) => ({
    id: user.id,
    name: user.name,
    avatar: user.avatar || generateAvatar(user.name), // fallback
  }));
});

watch(
  activeConversation,
  (conv) => {
    if (!conv) return;

    if (conv.type === "group") {
      // Reset pagination
      groupMembers.value = [];
      groupMembersPagination.value = { current_page: 0, last_page: 1, per_page: 20 };

      fetchGroupMembers(conv.id); // fetch first page
    }
  },
  { immediate: true }
);
</script>
