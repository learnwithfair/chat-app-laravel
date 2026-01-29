import { ref, computed, onMounted, nextTick, toRaw } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { generateAvatar } from '../../Utils/Chat/avatarHelper';

export function useChat() {
    // ==================== CACHE STORAGE ====================
    const messageCache = new Map(); // conversationId -> { messages: [], hasMore: bool, currentPage: int }
    const conversationCache = ref([]);
    const lastConversationFetch = ref(null);
    const CACHE_DURATION = 30000; // 30 seconds

    // State
    const conversations = ref([]);
    const messages = ref([]);
    const onlineUsers = ref([
        { id: 10, name: 'Alex', avatar: 'https://i.pravatar.cc/150?img=11', isOnline: true },
        { id: 11, name: 'Sam', avatar: 'https://i.pravatar.cc/150?img=12', isOnline: true },
        { id: 12, name: 'Jordan', avatar: 'https://i.pravatar.cc/150?img=13', isOnline: true }
    ]);

    // const availableUsers = ref([
    //     { id: 20, name: 'Chris Evans', avatar: 'https://i.pravatar.cc/150?img=33' },
    //     { id: 21, name: 'Emma Stone', avatar: 'https://i.pravatar.cc/150?img=34' },
    //     { id: 22, name: 'Ryan Gosling', avatar: 'https://i.pravatar.cc/150?img=35' }
    // ]);

    const availableUsers = ref([]);
    const groupMembers = ref([]);


    const activeConversation = ref(null);
    const searchQuery = ref('');
    const activeTab = ref('all');
    const activeRightTab = ref('members');
    const showRightPanel = ref(false);
    const newMessage = ref('');
    const replyingTo = ref(null);
    const editingMessage = ref(null);
    const selectedFiles = ref([]);
    const loading = ref(false);
    const messagesLoading = ref(false);

    const conversationMedia = ref([]);
    const conversationFiles = ref([]);
    const conversationLinks = ref([]);
    const pendingMembers = ref([]);

    const typingUsers = ref({});
    let typingTimeout = null;

    // ==================== PAGINATION STATE ====================
    const conversationPagination = ref({
        currentPage: 1,
        lastPage: 1,
        hasMore: true,
        loading: false
    });

    const messagePagination = ref({
        currentPage: 1,
        lastPage: 1,
        hasMore: true,
        loading: false
    });

    const availableUsersPagination = ref({
        currentPage: 1,
        lastPage: 1,
        hasMore: true,
        loading: false,
    });

    const groupMembersPagination = ref({
        currentPage: 1,
        lastPage: 1,
        hasMore: false,
        loading: false,
    });


    // Modal states
    const modals = ref({
        createGroup: false,
        addMember: false,
        reaction: false,
        seenBy: false,
        messageDetails: false,
        deleteMessage: false,
        forwardMessage: false,
        startChat: false
    });

    const selectedReactionUsers = ref([]);
    const currentSeenBy = ref([]);
    const selectedMessageDetails = ref(null);
    const messageToDelete = ref(null);
    const messageToForward = ref(null);
    const messageContainer = ref(null);


    const startChatUsers = ref([]);
    const startChatLoading = ref(false);
    const startChatPagination = ref({
        currentPage: 1,
        lastPage: 1,
        hasMore: true,
        loading: false,
    });



    // API Base URL
    const API_BASE = '/api/v1';

    // ==================== API CALLS ====================

    // Fetch available users
    const fetchAvailableUsers = async (search = null, page = 1, append = false) => {
        if (append) availableUsersPagination.value.loading = true;

        try {
            const params = {
                page,
                per_page: 20,
            };

            if (search) {
                params.search = search;
            }

            const response = await axios.get(`${API_BASE}/available-users`, { params });

            const data = response.data.data.data;

            const meta = response.data.data;
            console.log("meta");
            console.log(response.data.data);

            const users = data.map(user => ({
                id: user.id,
                name: user.name,
                email: user.email,
                avatar: user.avatar_path || generateAvatar(user.name),
            }));

            if (append) {
                availableUsers.value.push(...users);
            } else {
                availableUsers.value = users;
            }

            availableUsersPagination.value = {
                currentPage: meta.current_page,
                lastPage: meta.last_page,
                hasMore: meta.current_page < meta.last_page,
                loading: false,
            };
        } catch (error) {
            console.error('Failed to fetch available users:', error);
        } finally {
            availableUsersPagination.value.loading = false;
        }
    };
    // Fetch users for starting chat
    const fetchStartChatUsers = async (search = null, page = 1, append = false) => {
        if (append) {
            startChatPagination.value.loading = true;
        } else {
            startChatLoading.value = true;
        }

        try {
            const params = {
                page,
                per_page: 20,
            };

            if (search) {
                params.search = search;
            }

            const response = await axios.get(`${API_BASE}/available-users`, { params });

            const data = response.data.data.data;
            const meta = response.data.data;

            const users = data.map(user => ({
                id: user.id,
                name: user.name,
                email: user.email,
                avatar: user.avatar_path || generateAvatar(user.name),
                isOnline: user.is_online || false,
            }));

            if (append) {
                startChatUsers.value.push(...users);
            } else {
                startChatUsers.value = users;
            }

            startChatPagination.value = {
                currentPage: meta.current_page,
                lastPage: meta.last_page,
                hasMore: meta.current_page < meta.last_page,
                loading: false,
            };
        } catch (error) {
            console.error('Failed to fetch users for start chat:', error);
        } finally {
            startChatLoading.value = false;
            startChatPagination.value.loading = false;
        }
    };

    // Load more available users
    const loadMoreAvailableUsers = async () => {
        if (
            !availableUsersPagination.value.hasMore ||
            availableUsersPagination.value.loading
        ) return;

        await fetchAvailableUsers(
            searchQuery.value,
            availableUsersPagination.value.currentPage + 1,
            true
        );
    };

    // Search available users
    const searchAvailableUsers = async () => {
        availableUsersPagination.value.currentPage = 1;
        await fetchAvailableUsers(searchQuery.value, 1, false);
    };


    //* Load more users in start chat modal (infinite scroll)
    const loadMoreStartChatUsers = async () => {
        if (
            !startChatPagination.value.hasMore ||
            startChatPagination.value.loading
        ) {
            return;
        }

        await fetchStartChatUsers(
            null,
            startChatPagination.value.currentPage + 1,
            true
        );
    };

    // Search users in start chat modal
    const searchStartChatUsers = async (query) => {
        // Reset pagination for new search
        startChatPagination.value.currentPage = 1;

        // Fetch users with search query
        await fetchStartChatUsers(query, 1, false);
    };

    // Fetch group members
    const fetchGroupMembers = async (page = 1, append = false) => {
        if (append) groupMembersPagination.value.loading = true;

        try {
            const conversationId = activeConversation.value?.id;
            if (!conversationId) return;

            const response = await axios.get(
                `${API_BASE}/group/${conversationId}/members`,
                { params: { page, per_page: 20 } }
            );
            // Array of members
            const data = response.data.data || [];

            // Map the members correctly
            const members = data.map(item => ({
                id: item.user.id,
                name: item.user.name,
                role: item.role, // role is at top level
                avatar: item.user.avatar_path || generateAvatar(item.user.name),
            }));

            if (append) {
                groupMembers.value.push(...members);
            } else {
                groupMembers.value = members;
            }

            // Handle pagination safely
            const paginationMeta = response.data.meta || {}; // adjust if your API returns meta
            groupMembersPagination.value = {
                currentPage: paginationMeta.current_page || page,
                lastPage: paginationMeta.last_page || page,
                hasMore: (paginationMeta.current_page || page) < (paginationMeta.last_page || page),
                loading: false,
            };

        } catch (e) {
            console.error("Failed to fetch group members", e);
        } finally {
            groupMembersPagination.value.loading = false;
        }
    };

    // Load more group members
    const loadMoreGroupMembers = async () => {
        if (
            !groupMembersPagination.value.hasMore ||
            groupMembersPagination.value.loading
        ) return;

        await fetchGroupMembers(
            groupMembersPagination.value.currentPage + 1,
            true
        );
    };




    // Fetch conversations with CACHING and PAGINATION
    const fetchConversations = async (query = null, page = 1, append = false) => {
        // Use cache for first page if no query
        const now = Date.now();
        if (
            page === 1 &&
            !query &&
            conversationCache.value.length > 0 &&
            lastConversationFetch.value &&
            now - lastConversationFetch.value < CACHE_DURATION
        ) {
            conversations.value = conversationCache.value;
            return;
        }

        // Set loading states
        if (append) conversationPagination.value.loading = true;
        else loading.value = true;

        try {
            // Prepare request params
            const params = { page, per_page: 30 };
            if (query) params.query = query;

            const response = await axios.get(`${API_BASE}/conversations`, { params });
            const data = response.data;
            const convs = data.data;
            const meta = data.meta;


            // Format conversations for frontend
            const formattedConversations = convs.map(conv => {
                const name = conv.type === 'private' ? conv.receiver?.name : conv.name;
                const avatar =
                    conv.type === 'private'
                        ? conv.receiver?.avatar_path ?? generateAvatar(conv.receiver?.name)
                        : conv.group_setting?.avatar ?? generateAvatar(conv.name);

                return {
                    id: conv.id,
                    type: conv.type,
                    name: name || 'Unknown',
                    avatar: avatar,
                    lastMessage: buildLastMessagePreview(conv.last_message) || 'No preview available',
                    lastMessageTime: conv.last_message?.created_at
                        ? formatTime(conv.last_message.created_at)
                        : '',
                    unreadCount: conv.unread_count || 0,
                    isOnline: conv.receiver?.is_online || false,
                    isBlocked: conv.is_blocked || false,
                    createdBy: conv.is_admin ? 1 : 0,
                    members: conv.participants || [],
                    settings: conv.group_setting || null,
                    isMuted: conv.is_muted || false,
                    receiver: conv.receiver || null,
                    is_admin: conv.is_admin,
                    role: conv.role
                };
            });

            // Append older conversations or replace
            if (append) {
                conversations.value = [...conversations.value, ...formattedConversations];
            } else {
                conversations.value = formattedConversations;
                conversationCache.value = formattedConversations; // update cache
                lastConversationFetch.value = Date.now();
            }

            // Update pagination state
            conversationPagination.value = {
                currentPage: meta.current_page,
                lastPage: meta.last_page,
                hasMore: meta.current_page < meta.last_page,
                loading: false
            };
        } catch (err) {
            console.error('Failed to fetch conversations:', err);
        } finally {
            loading.value = false;
            conversationPagination.value.loading = false;
        }
    };

    const moveConversationToTop = (conversationId, newMessage) => {
        // Find conversation
        const index = conversations.value.findIndex(c => c.id === conversationId);

        if (index !== -1) {
            // Update last message and time
            conversations.value[index] = {
                ...conversations.value[index],
                lastMessage: newMessage.text || newMessage.message,
                lastMessageTime: formatTime(newMessage.created_at || new Date())
            };

            // Move conversation to top
            const [conv] = conversations.value.splice(index, 1);
            conversations.value.unshift(conv);
        } else {
            // Optional: conversation not in list (maybe new), fetch it or add it
            fetchConversations(); // or add manually
        }
    };


    // Load more conversations (for infinite scroll)
    const loadMoreConversations = async () => {
        if (!conversationPagination.value.hasMore || conversationPagination.value.loading) {
            return;
        }
        await fetchConversations(null, conversationPagination.value.currentPage + 1, true);
    };

    // Fetch messages with CACHING and PAGINATION 

    const fetchMessages = async (conversationId, page = 1, append = false) => {
        const cached = messageCache.get(conversationId);

        if (page === 1 && cached && !append) {
            messages.value = cached.messages;
            messagePagination.value = { ...cached };
            await markMessagesAsSeen(conversationId);
            return;
        }

        if (append) messagePagination.value.loading = true;
        else messagesLoading.value = true;

        try {
            const response = await axios.get(`${API_BASE}/messages/${conversationId}`, {
                params: { page, per_page: 20 }
            });

            const data = response.data;
            const meta = data.meta;

            console.log(' Fetched messages:', data);

            // Reverse backend data to have oldest → newest for display
            const formattedMessages = data.data
                .map(msg => ({
                    id: msg.id,
                    text: msg.message,
                    isMine: msg.sender.id === getCurrentUserId(),
                    time: formatTime(msg.created_at),
                    status: getMessageStatus(msg.statuses),
                    senderName: msg.sender.name || 'Unknown',
                    senderAvatar: msg.sender.avatar_path || generateAvatar(msg.sender.name),
                    reactions: formatReactions(msg.reactions),
                    isDeleted: msg.is_deleted_for_everyone || false,
                    isEdited: false,
                    messageType: msg.message_type || 'text',
                    replyTo: msg.reply ? {
                        id: msg.reply.id,
                        senderName: msg.reply.sender.name,
                        text: msg.reply.message
                    } : null,
                    //  FIX: Format ALL attachments, not just first one
                    attachments: formatAttachments(msg.attachments),
                    seenBy: msg.statuses?.filter(s => s.status === 'seen').map(s => ({
                        id: s.user_id,
                        name: s.user?.name || 'Unknown',
                        avatar: s.user?.avatar_path || generateAvatar(s.user?.name || 'User'),
                        seenAt: formatTime(s.created_at || msg.created_at)
                    })) || []
                }))
                .reverse();

            console.log(" Formatted Messages:", formattedMessages);

            if (append) {
                messages.value = [...formattedMessages, ...messages.value];
            } else {
                messages.value = formattedMessages;
            }

            messagePagination.value = {
                currentPage: meta.current_page,
                lastPage: meta.last_page,
                hasMore: meta.current_page < meta.last_page,
                loading: false
            };

            messageCache.set(conversationId, {
                messages: messages.value,
                hasMore: messagePagination.value.hasMore,
                currentPage: messagePagination.value.currentPage,
                lastPage: messagePagination.value.lastPage
            });

            if (page === 1) await markMessagesAsSeen(conversationId);

        } catch (err) {
            console.error('Failed to fetch messages:', err);
        } finally {
            messagesLoading.value = false;
            messagePagination.value.loading = false;
        }
    };


    // Load more messages (older messages - for infinite scroll UP)
    const loadMoreMessages = async (conversationId) => {

        if (!messagePagination.value.hasMore || messagePagination.value.loading) {
            return;
        }
        await fetchMessages(conversationId, messagePagination.value.currentPage + 1, true);
    };

    // Start private conversation
    const startPrivateConversationAPI = async (userId) => {
        try {
            const response = await axios.post(`${API_BASE}/conversations/private`, {
                receiver_id: userId
            });

            const conv = response.data.data;
            const newConv = {
                id: conv.id,
                type: 'private',
                name: conv.receiver?.name || '',
                avatar: conv.receiver?.avatar_path || generateAvatar(conv.receiver?.name),
                lastMessage: 'Started a new conversation',
                lastMessageTime: 'Just now',
                unreadCount: 0,
                isOnline: conv.receiver?.is_online || false,
                isBlocked: conv.is_blocked || false,
                created_by: 1,
                receiver: conv.receiver
            };

            const existing = conversations.value.find(c => c.id === newConv.id);
            if (!existing) {
                conversations.value.unshift(newConv);
                // Invalidate cache
                lastConversationFetch.value = null;
            }

            return newConv;
        } catch (error) {
            console.error('Failed to start private conversation:', error);
            throw error;
        }
    };

    // Send message
    const sendMessageAPI = async (conversationId, messageData) => {
        try {
            const response = await axios.post(`${API_BASE}/messages`, {
                conversation_id: conversationId,
                message: messageData.text,
                reply_to_message_id: messageData.replyToId || null
            });

            // Update cache with new message
            const cached = messageCache.get(conversationId);
            if (cached) {
                // Will be updated by handleSendMessage
            }

            return response.data.data;
        } catch (error) {
            console.error('Failed to send message:', error);
            throw error;
        }
    };

    // Update message
    const updateMessageAPI = async (messageId, newText) => {
        try {
            const response = await axios.put(`${API_BASE}/messages/${messageId}`, {
                message: newText
            });
            return response.data.data;
        } catch (error) {
            console.error('Failed to update message:', error);
            throw error;
        }
    };

    // Delete message for me
    const deleteMessageForMeAPI = async (messageIds) => {
        try {
            await axios.delete(`${API_BASE}/messages/delete-for-me`, {
                data: { message_ids: messageIds }
            });
        } catch (error) {
            console.error('Failed to delete message:', error);
            throw error;
        }
    };

    // Delete message for everyone
    const deleteMessageForEveryoneAPI = async (messageIds) => {
        try {
            await axios.delete(`${API_BASE}/messages/delete-for-everyone`, {
                data: { message_ids: messageIds }
            });
        } catch (error) {
            console.error('Failed to delete message for everyone:', error);
            throw error;
        }
    };

    // Mark messages as seen
    const markMessagesAsSeen = async (conversationId) => {
        try {
            await axios.get(`${API_BASE}/messages/seen/${conversationId}`);

            // Update unread count in conversation list
            const conv = conversations.value.find(c => c.id === conversationId);
            if (conv) {
                conv.unreadCount = 0;
            }
        } catch (error) {
            console.error('Failed to mark messages as seen:', error);
        }
    };

    // Toggle reaction
    const toggleReactionAPI = async (messageId, emoji) => {
        try {
            const response = await axios.post(`${API_BASE}/messages/${messageId}/reaction`, {
                reaction: emoji
            });
            return response.data;
        } catch (error) {
            console.error('Failed to toggle reaction:', error);
            throw error;
        }
    };

    // Get reactions for a message
    const getReactionsAPI = async (messageId) => {
        try {
            const response = await axios.get(`${API_BASE}/messages/${messageId}/reaction`);
            return response.data.data;
        } catch (error) {
            console.error('Failed to fetch reactions:', error);
            throw error;
        }
    };

    // Create group
    const createGroupAPI = async (groupData) => {

        try {
            const response = await axios.post(`${API_BASE}/conversations`, {
                type: 'group',
                name: groupData.name,
                participants: groupData.members,
                group: {
                    description: groupData.description,
                    type: groupData.type
                }
            });

            // Invalidate conversation cache
            lastConversationFetch.value = null;

            return response.data.data;
        } catch (error) {
            console.error('Failed to create group:', error);
            throw error;
        }
    };


    // Add members to group
    const addMembersToGroupAPI = async (conversationId, memberIds) => {
        try {
            const response = await axios.post(`${API_BASE}/group/${conversationId}/members/add`, {
                member_ids: memberIds
            });
            return response.data;
        } catch (error) {
            console.error('Failed to add members:', error);
            throw error;
        }
    };

    // Remove member from group
    const removeMemberAPI = async (conversationId, memberIds) => {
        try {
            const response = await axios.post(`${API_BASE}/group/${conversationId}/members/remove`, {
                member_ids: memberIds
            });
            return response.data;
        } catch (error) {
            console.error('Failed to remove member:', error);
            throw error;
        }
    };

    // Add admin
    const addAdminAPI = async (conversationId, userIds) => {
        try {
            const response = await axios.post(`${API_BASE}/group/${conversationId}/admins/add`, {
                member_ids: userIds
            });
            return response.data;
        } catch (error) {
            console.error('Failed to add admin:', error);
            throw error;
        }
    };

    // Remove admin
    const removeAdminAPI = async (conversationId, userIds) => {
        try {
            const response = await axios.post(`${API_BASE}/group/${conversationId}/admins/remove`, {
                member_ids: userIds
            });
            console.log("response");
            console.log(response);
            return response.data;
        } catch (error) {
            console.error('Failed to remove admin:', error);
            throw error;
        }
    };

    // Leave group
    const leaveGroupAPI = async (conversationId) => {
        try {
            await axios.post(`${API_BASE}/group/${conversationId}/leave`);

            // Clear caches
            messageCache.delete(conversationId);
            lastConversationFetch.value = null;
        } catch (error) {
            console.error('Failed to leave group:', error);
            throw error;
        }
    };

    // Update group info
    const updateGroupInfoAPI = async (conversationId, data) => {
        try {
            const formData = new FormData();

            // Conversation name
            if (data.name) {
                formData.append('name', data.name);
            }

            // Group fields
            if (data.group) {
                Object.keys(data.group).forEach(key => {
                    let value = data.group[key];

                    // Skip null/undefined
                    if (value === null || value === undefined) return;

                    // Booleans must be 1/0 for FormData
                    if (typeof value === 'boolean') {
                        value = value ? 1 : 0;
                    }

                    // Special case: avatar file
                    if (key === 'avatar') {
                        formData.append(`group[avatar]`, value); // file object
                    } else {
                        formData.append(`group[${key}]`, value);
                    }
                });
            }

            const response = await axios.post(
                `${API_BASE}/group/${conversationId}/update`,
                formData,
                { headers: { 'Content-Type': 'multipart/form-data' } }
            );

            return response.data.data;
        } catch (error) {
            console.error('Failed to update group:', error.response?.data || error);
            throw error;
        }
    };



    // Mute/Unmute group
    const muteGroupAPI = async (conversationId, minutes = 0) => {
        try {
            const response = await axios.post(`${API_BASE}/group/${conversationId}/mute`, {
                minutes
            });
            return response.data;
        } catch (error) {
            console.error('Failed to mute/unmute group:', error);
            throw error;
        }
    };

    // Toggle block user
    const toggleBlockAPI = async (userId) => {
        try {
            const response = await axios.post(`${API_BASE}/users/${userId}/block-toggle`);
            return response.data;
        } catch (error) {
            console.error('Failed to toggle block:', error);
            throw error;
        }
    };

    // Toggle restrict user
    const toggleRestrictAPI = async (userId) => {
        try {
            const response = await axios.post(`${API_BASE}/users/${userId}/restrict-toggle`);
            return response.data;
        } catch (error) {
            console.error('Failed to toggle restrict:', error);
            throw error;
        }
    };

    // Delete conversation
    const deleteConversationAPI = async (conversationId) => {
        try {
            await axios.delete(`${API_BASE}/conversations/${conversationId}`);

            // Clear caches
            messageCache.delete(conversationId);
            lastConversationFetch.value = null;
        } catch (error) {
            console.error('Failed to delete conversation:', error);
            throw error;
        }
    };


    // Fetch conversation media
    const fetchConversationMedia = async (conversationId) => {
        try {
            const response = await axios.get(`${API_BASE}/conversations/${conversationId}/media`);
            conversationMedia.value = response.data.data.map(item => ({
                id: item.id,
                type: item.type,
                url: item.path,
                name: item.name || 'media',
                createdAt: item.created_at
            }));
        } catch (error) {
            console.error('Failed to fetch media:', error);
        }
    };

    // Fetch conversation files
    const fetchConversationFiles = async (conversationId) => {
        try {
            const response = await axios.get(`${API_BASE}/conversations/${conversationId}/files`);
            conversationFiles.value = response.data.data.map(item => ({
                id: item.id,
                type: item.type,
                url: item.path,
                name: item.name || 'file',
                size: item.size || 0,
                createdAt: item.created_at
            }));
        } catch (error) {
            console.error('Failed to fetch files:', error);
        }
    };

    // Fetch conversation links
    const fetchConversationLinks = async (conversationId) => {
        try {
            const response = await axios.get(`${API_BASE}/conversations/${conversationId}/links`);
            conversationLinks.value = response.data.data.map(item => ({
                id: item.id,
                url: item.url,
                sharedAt: item.created_at
            }));
        } catch (error) {
            console.error('Failed to fetch links:', error);
        }
    };

    // Toggle mute
    const toggleMuteAPI = async (conversationId, isMuted) => {
        try {
            const response = await axios.post(`${API_BASE}/conversations/${conversationId}/mute`, {
                mute: isMuted
            });
            return response.data;
        } catch (error) {
            console.error('Failed to toggle mute:', error);
            throw error;
        }
    };

    // Update group avatar
    const updateGroupAvatarAPI = async (conversationId, avatarFile) => {
        try {
            const formData = new FormData();
            formData.append('group[avatar]', avatarFile);

            const response = await axios.post(
                `${API_BASE}/group/${conversationId}/update`,
                formData,
                { headers: { 'Content-Type': 'multipart/form-data' } }
            );

            return response.data.data;
        } catch (error) {
            console.error('Failed to update avatar:', error);
            throw error;
        }
    };

    // Update group description
    const updateGroupDescriptionAPI = async (conversationId, description) => {
        try {
            const response = await axios.post(`${API_BASE}/group/${conversationId}/update`, {
                group: { description }
            });
            return response.data.data;
        } catch (error) {
            console.error('Failed to update description:', error);
            throw error;
        }
    };

    // Fetch pending members (for approval system)
    const fetchPendingMembers = async (conversationId) => {
        try {
            const response = await axios.get(`${API_BASE}/group/${conversationId}/pending-members`);
            pendingMembers.value = response.data.data.map(item => ({
                id: item.user.id,
                name: item.user.name,
                avatar: item.user.avatar_path || generateAvatar(item.user.name),
                requestedAt: item.created_at
            }));
        } catch (error) {
            console.error('Failed to fetch pending members:', error);
        }
    };

    // Approve member
    const approveMemberAPI = async (conversationId, userId) => {
        try {
            const response = await axios.post(`${API_BASE}/group/${conversationId}/approve-member`, {
                user_id: userId
            });
            return response.data;
        } catch (error) {
            console.error('Failed to approve member:', error);
            throw error;
        }
    };

    // Reject member
    const rejectMemberAPI = async (conversationId, userId) => {
        try {
            const response = await axios.post(`${API_BASE}/group/${conversationId}/reject-member`, {
                user_id: userId
            });
            return response.data;
        } catch (error) {
            console.error('Failed to reject member:', error);
            throw error;
        }
    };

    // ==================== REAL-TIME UPDATE HANDLER  (For websocket to get real-time updates)====================

    const handleNewMessage = (incomingMsg) => {
        const conversationId = incomingMsg.conversation_id;

        // Check if the message already exists in UI
        if (
            activeConversation.value?.id === conversationId &&
            messages.value.some(msg => msg.id === incomingMsg.id)
        ) {
            return; // Ignore duplicate in active conversation
        }

        const formatted = {
            id: incomingMsg.id,
            text: incomingMsg.message,
            isMine: incomingMsg.sender.id === getCurrentUserId(),
            time: formatTime(incomingMsg.created_at),
            status: 'delivered',
            senderName: incomingMsg.sender.id === getCurrentUserId() ? 'You' : incomingMsg.sender.name,
            senderAvatar: incomingMsg.sender.avatar_path || generateAvatar(incomingMsg.sender.name),
            reactions: [],
            isDeleted: false,
            isEdited: false,
            replyTo: incomingMsg.reply
                ? {
                    senderName: incomingMsg.reply.sender.name,
                    text: incomingMsg.reply.message
                }
                : null,
            file: incomingMsg.attachments?.length > 0 ? formatAttachment(incomingMsg.attachments[0]) : null,
            seenBy: []
        };

        // --- Update cache ---
        const cached = messageCache.get(conversationId);
        if (!cached) {
            messageCache.set(conversationId, {
                messages: [formatted],
                currentPage: 1,
                lastPage: 1,
                hasMore: false,
                loading: false
            });
        } else {
            if (!cached.messages.some(m => m.id === formatted.id)) {
                cached.messages.push(formatted);
            }
            messageCache.set(conversationId, cached);
        }

        // --- Update UI if active conversation ---
        if (activeConversation.value?.id === conversationId) {
            messages.value.push(formatted);
            nextTick(() => scrollToBottom());
        }

        // --- Update conversation list ---
        const index = conversations.value.findIndex(c => c.id === conversationId);

        if (index !== -1) {
            const conv = conversations.value[index];
            conv.lastMessage = formatted.text;
            conv.lastMessageTime = formatted.time;

            // Increment unread if not active
            if (activeConversation.value?.id !== conversationId) {
                conv.unreadCount = (conv.unreadCount || 0) + 1;
            }

            // Move to top
            conversations.value.splice(index, 1);
            conversations.value.unshift(conv);
        } else {
            // Optional: if conversation is not in the list, add it (useful for brand new convs)
            conversations.value.unshift({
                id: conversationId,
                name: formatted.senderName,
                avatar: formatted.senderAvatar,
                lastMessage: formatted.text,
                lastMessageTime: formatted.time,
                unreadCount: 1,
                type: 'private', // adjust as needed
                members: [formatted.senderName], // adjust as needed
                isMuted: false,
                isBlocked: false
            });
        }

        // --- Invalidate conversation cache so next fetch refreshes if needed ---
        lastConversationFetch.value = null;
    };

    // Clear all caches (for logout or manual refresh)
    const clearCache = () => {
        messageCache.clear();
        conversationCache.value = [];
        lastConversationFetch.value = null;
    };

    // ==================== HELPER FUNCTIONS ====================

    // const formatTime = (datetime) => {
    //     const date = new Date(datetime);
    //     const now = new Date();
    //     const diff = now - date;

    //     if (diff < 60000) return 'Just now';
    //     if (diff < 3600000) return `${Math.floor(diff / 60000)}m`;
    //     if (diff < 86400000) return `${Math.floor(diff / 3600000)}h`;
    //     if (diff < 604800000) return `${Math.floor(diff / 86400000)}d`;

    //     return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    // };

    const formatTime = (datetime) => {
        const date = parseLocalDateTime(datetime);

        // 🚨 IMPORTANT GUARD
        if (!date || isNaN(date.getTime())) {
            return 'Just now';
        }

        const now = new Date();
        const diff = now - date;

        if (diff < 60 * 1000) return 'Just now';
        if (diff < 60 * 60 * 1000) return `${Math.floor(diff / 60000)}m`;
        if (diff < 24 * 60 * 60 * 1000) return `${Math.floor(diff / 3600000)}h`;

        return date.toLocaleTimeString('en-US', {
            hour: 'numeric',
            minute: '2-digit',
            hour12: true,
        });
    };


    const parseLocalDateTime = (datetime) => {
        if (!datetime) return null;

        // Already a Date object
        if (datetime instanceof Date) return datetime;

        // Must be a string
        if (typeof datetime !== 'string') return null;

        // Expected: "YYYY-MM-DD HH:mm:ss"
        if (!datetime.includes(' ')) return null;

        const [datePart, timePart] = datetime.split(' ');
        if (!datePart || !timePart) return null;

        const [year, month, day] = datePart.split('-').map(Number);
        const [hour, minute, second = 0] = timePart.split(':').map(Number);

        const date = new Date(year, month - 1, day, hour, minute, second);
        return isNaN(date.getTime()) ? null : date;
    };






    const formatReactions = (reactions) => {
        if (!reactions || !reactions.reactions) return [];

        return Object.entries(reactions.reactions).map(([emoji, count]) => ({
            emoji,
            count
        }));
    };

    // Update formatAttachment to handle single or multiple files
    const formatAttachment = (attachment) => {
        return {
            id: attachment.id,
            type: attachment.type,
            url: attachment.path,
            name: attachment.name || 'file',
            size: attachment.size || 0
        };
    };

    // Format multiple attachments
    const formatAttachments = (attachments) => {
        if (!attachments || attachments.length === 0) return null;
        return attachments.map(att => formatAttachment(att));
    };

    const getMessageStatus = (statuses) => {
        if (!statuses || statuses.length === 0) return 'sent';

        const hasSeenStatus = statuses.some(s => s.status === 'seen');
        if (hasSeenStatus) return 'seen';

        const hasDeliveredStatus = statuses.some(s => s.status === 'delivered');
        if (hasDeliveredStatus) return 'delivered';

        return 'sent';
    };

    const getCurrentUserId = () => {
        return window.authUser?.id || 1;
    };

    // ==================== COMPUTED ====================

    const filteredConversations = computed(() => {
        let filtered = conversations.value;

        if (activeTab.value !== 'all') {
            filtered = filtered.filter(conv => conv.type === activeTab.value);
        }

        if (searchQuery.value) {
            const query = searchQuery.value.toLowerCase();
            filtered = filtered.filter(conv => {
                const rawConv = toRaw(conv);
                const nameMatch = rawConv.name ? rawConv.name.toLowerCase().includes(query) : false;
                const messageMatch = rawConv.lastMessage ? rawConv.lastMessage.toLowerCase().includes(query) : false;

                return nameMatch || messageMatch;
            });
        }

        return filtered;
    });

    const getConversationSubtitle = computed(() => {
        if (!activeConversation.value) return '';

        if (activeConversation.value.type === 'group') {
            return `${activeConversation.value.members?.length || 0} members`;
        }

        return activeConversation.value.isOnline ? 'Online' : 'Offline';
    });

    const getConversationAvatar = computed(() => {
        if (!activeConversation.value) return '';
        return activeConversation.value.avatar || activeConversation.value.members?.[0]?.avatar || '';
    });

    // ==================== METHODS ====================

    const selectConversation = async (conversation) => {
        activeConversation.value = conversation;

        // Reset pagination for new conversation
        messagePagination.value = {
            currentPage: 1,
            lastPage: 1,
            hasMore: true,
            loading: false
        };

        await fetchMessages(conversation.id);
        nextTick(() => {
            scrollToBottom();
        });
    };

    const closeChatOnMobile = () => {
        activeConversation.value = null;
    };

    const startPrivateChat = async (user) => {
        try {
            const newConv = await startPrivateConversationAPI(user.id);
            selectConversation(newConv);
        } catch (error) {
            console.error('Failed to start chat:', error);
        }
    };

    // const handleSendMessage = async () => {
    //     if (!newMessage.value.trim() || !activeConversation.value) return;

    //     const messageText = newMessage.value;
    //     const replyToId = replyingTo.value?.id || null;

    //     try {
    //         let sentMsg;

    //         if (editingMessage.value) {
    //             // Update existing message
    //             await updateMessageAPI(editingMessage.value.id, messageText);

    //             const msg = messages.value.find(m => m.id === editingMessage.value.id);
    //             if (msg) {
    //                 msg.text = messageText;
    //                 msg.isEdited = true;
    //             }

    //             // Update cache
    //             const cached = messageCache.get(activeConversation.value.id);
    //             if (cached) {
    //                 const cachedMsg = cached.messages.find(m => m.id === editingMessage.value.id);
    //                 if (cachedMsg) {
    //                     cachedMsg.text = messageText;
    //                     cachedMsg.isEdited = true;
    //                 }
    //             }

    //             editingMessage.value = null;
    //         } else {
    //             // Send new message
    //             sentMsg = await sendMessageAPI(activeConversation.value.id, {
    //                 text: messageText,
    //                 replyToId
    //             });

    //             const newMsg = {
    //                 id: sentMsg.id,
    //                 text: sentMsg.message,
    //                 isMine: true,
    //                 time: formatTime(sentMsg.created_at),
    //                 status: 'sent',
    //                 senderName: 'You',
    //                 senderAvatar: '',
    //                 reactions: [],
    //                 isDeleted: false,
    //                 isEdited: false,
    //                 replyTo: replyingTo.value ? {
    //                     senderName: replyingTo.value.senderName,
    //                     text: replyingTo.value.text
    //                 } : null,
    //                 file: null,
    //                 seenBy: []
    //             };

    //             // Add message to UI
    //             messages.value.push(newMsg);

    //             const cached = messageCache.get(activeConversation.value.id);

    //             if (!cached) {
    //                 messageCache.set(activeConversation.value.id, { messages: [newMsg], currentPage: 1, lastPage: 1, hasMore: false, loading: false });
    //             } else {
    //                 // Only push if the message ID doesn't exist yet
    //                 if (!cached.messages.some(m => m.id === newMsg.id)) {
    //                     cached.messages.push(newMsg);
    //                 }
    //                 messageCache.set(activeConversation.value.id, cached);
    //             }


    //             replyingTo.value = null;

    //             // --- NEW: Move conversation to top in sidebar ---
    //             moveConversationToTop(activeConversation.value.id, sentMsg);
    //         }

    //         newMessage.value = '';
    //         nextTick(() => {
    //             scrollToBottom();
    //         });

    //     } catch (error) {
    //         console.error('Failed to send message:', error);
    //     }
    // };

    // In your useChat.js composable


    // Updated handleStartChatUserSelect to check for existing conversation
    const handleStartChatUserSelect = async (user) => {
        try {
            // Check if conversation already exists with this user
            const existingConv = conversations.value.find(
                c => c.type === 'private' && c.receiver?.id === user.id
            );

            if (existingConv) {
                // Select existing conversation
                console.log('Opening existing conversation with', user.name);
                selectConversation(existingConv);
            } else {
                // Create new private conversation
                console.log('Creating new conversation with', user.name);
                const newConv = await startPrivateConversationAPI(user.id);

                // Select the new conversation
                selectConversation(newConv);
            }

            // Close the modal
            modals.value.startChat = false;

            // Clear search/users state
            startChatUsers.value = [];

        } catch (error) {
            console.error('Failed to start chat:', error);
            alert('Failed to start chat. Please try again.');
        }
    };

    // Updated handleSendMessage to support file attachments
    const handleSendMessage = async (filesFromInput = null) => {
        const files = filesFromInput || selectedFiles.value;

        if ((!newMessage.value.trim() && files.length === 0) || !activeConversation.value) return;

        const messageText = newMessage.value;
        const replyToId = replyingTo.value?.id || null;

        try {
            let sentMsg;

            if (editingMessage.value) {
                await updateMessageAPI(editingMessage.value.id, messageText);

                const msg = messages.value.find(m => m.id === editingMessage.value.id);
                if (msg) {
                    msg.text = messageText;
                    msg.isEdited = true;
                }

                const cached = messageCache.get(activeConversation.value.id);
                if (cached) {
                    const cachedMsg = cached.messages.find(m => m.id === editingMessage.value.id);
                    if (cachedMsg) {
                        cachedMsg.text = messageText;
                        cachedMsg.isEdited = true;
                    }
                }

                editingMessage.value = null;
            } else {
                const formData = new FormData();
                formData.append('conversation_id', activeConversation.value.id);
                formData.append('message', messageText || '');

                if (replyToId) {
                    formData.append('reply_to_message_id', replyToId);
                }

                if (files.length > 0) {

                    files.forEach((fileObj, index) => {
                        formData.append(`attachments[${index}][path]`, fileObj.file);
                    });

                    if (files.length > 1) {
                        formData.append('message_type', 'multiple');
                    } else {
                        const firstFile = files[0];
                        if (firstFile.type.startsWith('image/')) {
                            formData.append('message_type', 'image');
                        } else if (firstFile.type.startsWith('video/')) {
                            formData.append('message_type', 'video');
                        } else if (firstFile.type.startsWith('audio/')) {
                            formData.append('message_type', 'audio');
                        } else {
                            formData.append('message_type', 'file');
                        }
                    }
                } else {
                    formData.append('message_type', 'text');
                }

                console.log('📤 Sending FormData');

                sentMsg = await sendMessageWithFilesAPI(formData);

                console.log('✅ Message sent:', sentMsg);

                const newMsg = {
                    id: sentMsg.id,
                    text: sentMsg.message,
                    isMine: true,
                    time: formatTime(sentMsg.created_at),
                    status: 'sent',
                    senderName: 'You',
                    senderAvatar: '',
                    reactions: [],
                    isDeleted: false,
                    isEdited: false,
                    messageType: sentMsg.message_type || 'text',
                    replyTo: replyingTo.value ? {
                        senderName: replyingTo.value.senderName,
                        text: replyingTo.value.text
                    } : null,
                    attachments: formatAttachments(sentMsg.attachments),
                    seenBy: []
                };

                console.log('📨 New message object:', newMsg);

                messages.value.push(newMsg);

                const cached = messageCache.get(activeConversation.value.id);
                if (!cached) {
                    messageCache.set(activeConversation.value.id, {
                        messages: [newMsg],
                        currentPage: 1,
                        lastPage: 1,
                        hasMore: false,
                        loading: false
                    });
                } else {
                    if (!cached.messages.some(m => m.id === newMsg.id)) {
                        cached.messages.push(newMsg);
                    }
                    messageCache.set(activeConversation.value.id, cached);
                }

                replyingTo.value = null;

                // Update conversation list with proper preview
                updateConversationPreview(activeConversation.value.id, sentMsg);
            }

            newMessage.value = '';
            selectedFiles.value = [];

            nextTick(() => {
                scrollToBottom();
            });

        } catch (error) {
            // console.error(' Failed to send message:', error);
            if (error.response) {
                console.error('Response error:', error.response.data);
            }
        }
    };


    //  Update conversation preview in sidebar
    const updateConversationPreview = (conversationId, message) => {
        const index = conversations.value.findIndex(c => c.id === conversationId);
        if (index === -1) return;

        const conv = conversations.value[index];

        conversations.value[index] = {
            ...conv,
            lastMessage: buildLastMessagePreview(message),
            lastMessageTime: formatTime(message.created_at || new Date())
        };

        // Move conversation to top
        const [movedConv] = conversations.value.splice(index, 1);
        conversations.value.unshift(movedConv);
    };

    const buildLastMessagePreview = (message) => {
        if (!message) return '';

        let previewText = message.message || '';

        if (!previewText && message.attachments?.length > 0) {
            const attachmentTypes = message.attachments.map(a => a.type);

            if (attachmentTypes.includes('image')) previewText = '📷 Photo';
            else if (attachmentTypes.includes('video')) previewText = '🎥 Video';
            else if (attachmentTypes.includes('audio')) previewText = '🎵 Audio';
            else previewText = '📎 File';

            if (message.attachments.length > 1) {
                previewText += ` +${message.attachments.length - 1}`;
            }
        }

        return previewText;
    };


    // New API method for sending files
    const sendMessageWithFilesAPI = async (formData) => {
        try {
            const response = await axios.post(`${API_BASE}/messages`, formData, {
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            });
            return response.data.data;
        } catch (error) {
            console.error('Failed to send message with files:', error);
            throw error;
        }
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

    const forwardMessage = (message) => {
        messageToForward.value = message;
        modals.value.forwardMessage = true;
    };

    const handleForwardMessage = async ({ message, conversationIds }) => {
        console.log('Forwarding message to:', conversationIds);
    };

    const showDeleteMenu = (message) => {
        messageToDelete.value = message;
        modals.value.deleteMessage = true;
    };

    const deleteMessageForMe = async () => {
        try {
            await deleteMessageForMeAPI([messageToDelete.value.id]);
            messages.value = messages.value.filter(m => m.id !== messageToDelete.value.id);

            // Update cache
            const cached = messageCache.get(activeConversation.value.id);
            if (cached) {
                cached.messages = cached.messages.filter(m => m.id !== messageToDelete.value.id);
            }

            closeModal('deleteMessage');
        } catch (error) {
            console.error('Failed to delete message:', error);
        }
    };

    const deleteMessageForEveryone = async () => {
        try {
            await deleteMessageForEveryoneAPI([messageToDelete.value.id]);
            const msg = messages.value.find(m => m.id === messageToDelete.value.id);
            if (msg) {
                msg.isDeleted = true;
                msg.text = 'This message was deleted';
            }

            // Update cache
            const cached = messageCache.get(activeConversation.value.id);
            if (cached) {
                const cachedMsg = cached.messages.find(m => m.id === messageToDelete.value.id);
                if (cachedMsg) {
                    cachedMsg.isDeleted = true;
                    cachedMsg.text = 'This message was deleted';
                }
            }

            closeModal('deleteMessage');
        } catch (error) {
            console.error('Failed to delete message for everyone:', error);
        }
    };

    const openReactionModal = async ({ message, reaction }) => {
        try {
            const reactions = await getReactionsAPI(message.id);
            selectedReactionUsers.value = reactions.filter(r => r.reaction === reaction.emoji);
            modals.value.reaction = true;
        } catch (error) {
            console.error('Failed to fetch reactions:', error);
        }
    };

    const openMessageDetails = (message) => {
        if (message.isDeleted) return;
        selectedMessageDetails.value = message;
        modals.value.messageDetails = true;
    };
    // old

    // Start Chat
    const openStartChatModal = async () => {
        modals.value.startChat = true;

        // Reset state
        startChatUsers.value = [];
        startChatPagination.value = {
            currentPage: 1,
            lastPage: 1,
            hasMore: true,
            loading: false,
        };

        // Fetch initial users
        await fetchStartChatUsers(null, 1, false);
    };

    const showSeenByModal = (message) => {
        currentSeenBy.value = message.seenBy || [];
        modals.value.seenBy = true;
    };

    const handleSearch = () => {
        fetchConversations(searchQuery.value);
    };

    const handleAudioCall = () => {
        console.log('Starting audio call');
    };

    const handleVideoCall = () => {
        console.log('Starting video call');
    };

    // Group Management
    const openCreateGroup = async () => {
        openCreateGroupModal();

        // reset pagination
        await fetchAvailableUsers(null, 1, false);
    };

    const openCreateGroupModal = async () => {
        modals.value.createGroup = true;

        // reset & fetch users
        availableUsers.value = [];
        availableUsersPagination.value.currentPage = 1;

        await fetchAvailableUsers(null, 1, false);
    };

    const openAddMemberModal = async () => {
        modals.value.addMember = true;

        availableUsers.value = [];
        availableUsersPagination.value.currentPage = 1;

        await fetchAvailableUsers(null, 1, false);
    };


    const createGroup = async ({ name, description, type, members }) => {
        try {
            const newGroup = await createGroupAPI({
                name,
                description,
                type,
                members
            });

            conversations.value.unshift({
                id: newGroup.id,
                type: 'group',
                name: newGroup.name,
                avatar: generateAvatar(newGroup.name),
                lastMessage: 'Group created',
                lastMessageTime: 'Just now',
                unreadCount: 0,
                isOnline: false,
                isBlocked: false,
                created_by: 1,
                members: newGroup.participants || [],
                settings: newGroup.group_setting || null
            });

            selectConversation(conversations.value[0]);
            closeModal('createGroup');
        } catch (error) {
            console.error('Failed to create group:', error);
        }
    };

    // const openAddMemberModal = async () => {
    //     modals.value.addMember = true;

    //     availableUsers.value = [];
    //     availableUsersPagination.value.currentPage = 1;

    //     await fetchAvailableUsers();
    // };
    // const openAddMember = async () => {
    //     openAddMemberModal();

    //     await fetchAvailableUsers(null, 1, false);
    // };


    // const addMembersToGroup = async (userIds) => {
    //     if (!activeConversation.value || activeConversation.value.type !== 'group') return;

    //     try {
    //         await addMembersToGroupAPI(activeConversation.value.id, userIds);

    //         // Refresh conversation or add members locally
    //         userIds.forEach((userId) => {
    //             const user = availableUsers.value.find(u => u.id === userId);
    //             console.log(user);
    //             if (user) {
    //                 activeConversation.value.members.push({
    //                     ...user,
    //                     role: 'Member'
    //                 });
    //             }
    //         });

    //         closeModal('addMember');
    //     } catch (error) {
    //         console.error('Failed to add members:', error);
    //     }
    // };

    const addMembersToGroup = async (memberIds) => {
        if (!activeConversation.value) return;

        try {
            const conversationId = activeConversation.value.id;
            const res = await addMembersToGroupAPI(conversationId, memberIds);

            const members = res.data?.original?.data?.members || [];

            members.forEach(user => {
                if (!groupMembers.value.some(m => m.id === user.id)) {
                    groupMembers.value.push({
                        id: user.id,
                        name: user.name,
                        role: user.role ?? 'member',
                        avatar: user.avatar || generateAvatar(user.name),
                    });
                }
            });

            console.log(`${members.length} members added (realtime)`);
            closeModal('addMember');

        } catch (err) {
            console.error('Error adding members:', err);
        }
    };




    // const makeAdmin = async (member) => {
    //     try {
    //         await addAdminAPI(activeConversation.value.id, [member.id]);
    //         const m = activeConversation.value.members.find(mem => mem.id === member.id);
    //         console.log("makeAdmin");
    //         console.log(m);
    //         if (m) m.role = 'admin';
    //         console.log("later");
    //         console.log(m);
    //     } catch (error) {
    //         console.error('Failed to make admin:', error);
    //     }
    // };

    // const removeAdmin = async (member) => {
    //     try {
    //         await removeAdminAPI(activeConversation.value.id, [member.id]);
    //         const m = activeConversation.value.members.find(mem => mem.id === member.id);
    //         console.log("removeAdmin");
    //         console.log(m);
    //         if (m) m.role = 'member';
    //         console.log("later");
    //         console.log(m);
    //     } catch (error) {
    //         console.error('Failed to remove admin:', error);
    //     }
    // };

    const makeAdmin = async (member) => {
        try {
            await addAdminAPI(activeConversation.value.id, [member.id]);

            // Update activeConversation members
            const index1 = activeConversation.value.members.findIndex(m => m.id === member.id);
            if (index1 !== -1) {
                activeConversation.value.members[index1] = {
                    ...activeConversation.value.members[index1],
                    role: 'admin'
                };
            }

            // Update groupMembers for GroupMembers.vue
            const index2 = groupMembers.value.findIndex(m => m.id === member.id);
            if (index2 !== -1) {
                groupMembers.value[index2] = {
                    ...groupMembers.value[index2],
                    role: 'admin'
                };
            }
        } catch (error) {
            console.error('Failed to make admin:', error);
        }
    };

    const removeAdmin = async (member) => {
        try {
            const res = await removeAdminAPI(activeConversation.value.id, [member.id]);

            console.log(res);
            // Update activeConversation members
            const index1 = activeConversation.value.members.findIndex(m => m.id === member.id);
            if (index1 !== -1) {
                activeConversation.value.members[index1] = {
                    ...activeConversation.value.members[index1],
                    role: 'member'
                };
            }

            // Update groupMembers for GroupMembers.vue
            const index2 = groupMembers.value.findIndex(m => m.id === member.id);
            if (index2 !== -1) {
                groupMembers.value[index2] = {
                    ...groupMembers.value[index2],
                    role: 'member'
                };
            }
        } catch (error) {
            console.error('Failed to remove admin:', error);
        }
    };


    // const removeMember = async (member) => {
    //     if (!confirm(`Remove ${member.name} from the group?`)) return;

    //     try {
    //         await removeMemberAPI(activeConversation.value.id, [member.id]);
    //         activeConversation.value.members = activeConversation.value.members.filter(m => m.id !== member.id);
    //     } catch (error) {
    //         console.error('Failed to remove member:', error);
    //     }
    // };

    const removeMember = async (member) => {
        if (!confirm(`Remove ${member.name} from the group?`)) return;

        try {
            const res = await removeMemberAPI(
                activeConversation.value.id,
                [member.id]
            );

            // Optional immediate UI update (actor only)
            const removedMembers = res?.data?.original?.data?.members || [];

            removedMembers.forEach(u => {
                groupMembers.value = groupMembers.value.filter(
                    m => m.id !== u.id
                );
            });

        } catch (error) {
            console.error('Failed to remove member:', error);
        }
    };

    const leaveGroup = async () => {
        if (!confirm('Are you sure you want to leave this group?')) return;

        try {
            await leaveGroupAPI(activeConversation.value.id);
            conversations.value = conversations.value.filter(c => c.id !== activeConversation.value.id);
            activeConversation.value = null;
            showRightPanel.value = false;
        } catch (error) {
            console.error('Failed to leave group:', error);
        }
    };

    const updateGroupSettings = async (settings) => {
        if (!activeConversation.value || activeConversation.value.type !== 'group') return;

        try {
            const updated = await updateGroupInfoAPI(activeConversation.value.id, {
                name: activeConversation.value.name,
                group: settings
            });


            // merge backend response into reactive object
            activeConversation.value.settings = {
                ...activeConversation.value.settings,
                ...updated.group_setting
            };
        } catch (error) {
            console.error('Failed to update group settings:', error);
        }
    };


    const closeModal = (modalName) => {
        modals.value[modalName] = false;
        if (modalName === 'deleteMessage') messageToDelete.value = null;
        if (modalName === 'forwardMessage') messageToForward.value = null;
        if (modalName === 'messageDetails') selectedMessageDetails.value = null;
    };

    // Add reaction handler
    const handleAddReaction = async ({ messageId, emoji }) => {
        try {
            await toggleReactionAPI(messageId, emoji);

            const message = messages.value.find(m => m.id === messageId);
            if (!message) return;

            if (!message.reactions) {
                message.reactions = [];
            }

            const existingReaction = message.reactions.find(r => r.emoji === emoji);

            if (existingReaction) {
                existingReaction.count++;
            } else {
                message.reactions.push({
                    emoji: emoji,
                    count: 1
                });
            }
        } catch (error) {
            console.error('Failed to add reaction:', error);
        }
    };

    const handleRemoveReaction = async ({ messageId, emoji }) => {
        try {
            await toggleReactionAPI(messageId, emoji);

            const message = messages.value.find(m => m.id === messageId);
            if (!message || !message.reactions) return;

            const reactionIndex = message.reactions.findIndex(r => r.emoji === emoji);
            if (reactionIndex === -1) return;

            const reaction = message.reactions[reactionIndex];

            if (reaction.count > 1) {
                reaction.count--;
            } else {
                message.reactions.splice(reactionIndex, 1);
            }
        } catch (error) {
            console.error('Failed to remove reaction:', error);
        }
    };

    // Voice message handler
    const handleSendVoice = async (audioBlob, duration) => {
        if (!activeConversation.value) return;

        const audioUrl = URL.createObjectURL(audioBlob);

        const voiceMsg = {
            id: Date.now(),
            text: 'Voice message',
            isMine: true,
            time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
            status: 'sent',
            senderName: 'You',
            senderAvatar: '',
            type: 'voice',
            file: {
                type: 'audio',
                url: audioUrl,
                duration: duration,
                blob: audioBlob,
                name: 'voice-message.webm'
            },
            isDeleted: false,
            isEdited: false,
            reactions: [],
            seenBy: [],
            replyTo: replyingTo.value ? {
                senderName: replyingTo.value.senderName,
                text: replyingTo.value.text
            } : null
        };

        messages.value.push(voiceMsg);

        if (replyingTo.value) {
            replyingTo.value = null;
        }

        nextTick(() => {
            scrollToBottom();
        });

        await uploadVoiceMessage(audioBlob, voiceMsg, activeConversation.value.id);
    };

    // Handle tab change and fetch data accordingly
    const handleTabChange = async (tab) => {
        activeRightTab.value = tab;

        if (!activeConversation.value) return;

        const conversationId = activeConversation.value.id;

        if (tab === 'media' && conversationMedia.value.length === 0) {
            await fetchConversationMedia(conversationId);
        } else if (tab === 'files' && conversationFiles.value.length === 0) {
            await fetchConversationFiles(conversationId);
        } else if (tab === 'links' && conversationLinks.value.length === 0) {
            await fetchConversationLinks(conversationId);
        } else if (tab === 'members' && activeConversation.value.type === 'group') {
            if (groupMembers.value.length === 0) {
                await fetchGroupMembers();
            }
            // Check for pending members if admin
            if (activeConversation.value.settings?.admins_must_approve_new_members &&
                (activeConversation.value.role === 'super_admin' || activeConversation.value.role === 'admin')) {
                await fetchPendingMembers(conversationId);
            }
        }
    };

    // Handle toggle block
    const handleToggleBlock = async (conversationId) => {
        try {
            const conv = conversations.value.find(c => c.id === conversationId);
            if (!conv || !conv.receiver) return;

            await toggleBlockAPI(conv.receiver.id);

            // Update local state
            conv.isBlocked = !conv.isBlocked;

            if (activeConversation.value?.id === conversationId) {
                activeConversation.value.isBlocked = conv.isBlocked;
            }
        } catch (error) {
            console.error('Failed to toggle block:', error);
            alert('Failed to update block status');
        }
    };

    // Handle toggle mute
    const handleToggleMute = async (isMuted) => {
        try {
            if (!activeConversation.value) return;

            await toggleMuteAPI(activeConversation.value.id, isMuted);

            // Update local state
            activeConversation.value.isMuted = isMuted;

            const conv = conversations.value.find(c => c.id === activeConversation.value.id);
            if (conv) {
                conv.isMuted = isMuted;
            }
        } catch (error) {
            console.error('Failed to toggle mute:', error);
            alert('Failed to update notification settings');
        }
    };

    // Handle delete conversation
    const handleDeleteConversation = async (conversationId) => {
        try {
            await deleteConversationAPI(conversationId);

            // Remove from list
            conversations.value = conversations.value.filter(c => c.id !== conversationId);

            // Clear active conversation if it's the deleted one
            if (activeConversation.value?.id === conversationId) {
                activeConversation.value = null;
                showRightPanel.value = false;
            }
        } catch (error) {
            console.error('Failed to delete conversation:', error);
            alert('Failed to delete conversation');
        }
    };

    // Handle update avatar
    const handleUpdateAvatar = async (file) => {
        try {
            if (!activeConversation.value || activeConversation.value.type !== 'group') return;

            const updated = await updateGroupAvatarAPI(activeConversation.value.id, file);

            // Update local state
            const newAvatar = updated.group_setting?.avatar || generateAvatar(activeConversation.value.name);
            activeConversation.value.avatar = newAvatar;

            const conv = conversations.value.find(c => c.id === activeConversation.value.id);
            if (conv) {
                conv.avatar = newAvatar;
            }
        } catch (error) {
            console.error('Failed to update avatar:', error);
            alert('Failed to update group avatar');
        }
    };

    // Handle update description
    const handleUpdateDescription = async (description) => {
        try {
            if (!activeConversation.value || activeConversation.value.type !== 'group') return;

            const updated = await updateGroupDescriptionAPI(activeConversation.value.id, description);

            // Update local state
            if (activeConversation.value.settings) {
                activeConversation.value.settings.description = description;
            }
        } catch (error) {
            console.error('Failed to update description:', error);
            alert('Failed to update group description');
        }
    };

    // Approve pending member
    const approveMember = async (userId) => {
        try {
            if (!activeConversation.value) return;

            await approveMemberAPI(activeConversation.value.id, userId);

            // Remove from pending list
            pendingMembers.value = pendingMembers.value.filter(m => m.id !== userId);

            // Refresh group members
            await fetchGroupMembers();
        } catch (error) {
            console.error('Failed to approve member:', error);
            alert('Failed to approve member');
        }
    };

    // Reject pending member
    const rejectMember = async (userId) => {
        try {
            if (!activeConversation.value) return;

            await rejectMemberAPI(activeConversation.value.id, userId);

            // Remove from pending list
            pendingMembers.value = pendingMembers.value.filter(m => m.id !== userId);
        } catch (error) {
            console.error('Failed to reject member:', error);
            alert('Failed to reject member');
        }
    };

    const uploadVoiceMessage = async (audioBlob, message, conversationId) => {
        const formData = new FormData();
        formData.append('audio', audioBlob, 'voice-message.webm');
        formData.append('conversation_id', conversationId);
        formData.append('duration', message.file.duration);

        if (message.replyTo) {
            formData.append('reply_to_id', message.replyTo.id);
        }

        try {
            const response = await axios.post(`${API_BASE}/messages`, formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });

            const msg = messages.value.find(m => m.id === message.id);
            if (msg && response.data) {
                msg.id = response.data.id;
                msg.file.url = response.data.file_url;
                msg.status = 'delivered';
            }
        } catch (error) {
            console.error('Failed to upload voice message:', error);
            const msg = messages.value.find(m => m.id === message.id);
            if (msg) {
                msg.status = 'failed';
            }
        }
    };

    const listenForTyping = (conversationId) => {
        // Implement with Laravel Echo/Pusher
        // window.Echo.private(`conversation.${conversationId}`)
        //     .listenForWhisper('typing', (e) => {
        //         if (!typingUsers.value[conversationId]) {
        //             typingUsers.value[conversationId] = [];
        //         }
        //         // Add typing user logic
        //     });
    };

    const scrollToBottom = () => {
        if (messageContainer.value) {
            messageContainer.value.scrollTop = messageContainer.value.scrollHeight;
        }
    };

    onMounted(async () => {
        await fetchConversations();

        if (window.innerWidth >= 768 && conversations.value.length > 0) {
            selectConversation(conversations.value[0]);
        }
    });

    return {
        // State
        conversations,
        activeConversation,
        messages,
        searchQuery,
        activeTab,
        activeRightTab,
        showRightPanel,
        onlineUsers,
        availableUsers,
        newMessage,
        replyingTo,
        editingMessage,
        selectedFiles,
        modals,
        selectedReactionUsers,
        currentSeenBy,
        selectedMessageDetails,
        messageToDelete,
        messageToForward,
        messageContainer,

        // Computed
        filteredConversations,
        getConversationSubtitle,
        getConversationAvatar,

        // Methods
        selectConversation,
        closeChatOnMobile,
        startPrivateChat,
        handleSendMessage,
        replyToMessage,
        cancelReply,
        editMessage,
        cancelEdit,
        forwardMessage,
        handleForwardMessage,
        showDeleteMenu,
        deleteMessageForMe,
        deleteMessageForEveryone,
        openReactionModal,
        openMessageDetails,
        showSeenByModal,
        handleSearch,
        handleAudioCall,
        handleVideoCall,
        handleAddReaction,
        handleRemoveReaction,
        handleSendVoice,
        typingUsers,
        listenForTyping,
        conversationPagination,
        messagePagination,
        loadMoreConversations,
        loadMoreMessages,
        clearCache,

        availableUsers,
        availableUsersPagination,
        fetchAvailableUsers,
        loadMoreAvailableUsers,
        searchAvailableUsers,

        startChatUsers,
        startChatLoading,
        startChatPagination,
        openStartChatModal,
        fetchStartChatUsers,
        loadMoreStartChatUsers,
        searchStartChatUsers,
        handleStartChatUserSelect,

        groupMembers,
        groupMembersPagination,
        fetchGroupMembers,
        loadMoreGroupMembers,



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


        // New additions
        conversationMedia,
        conversationFiles,
        conversationLinks,
        pendingMembers,
        fetchConversationMedia,
        fetchConversationFiles,
        fetchConversationLinks,
        handleTabChange,
        handleToggleBlock,
        handleToggleMute,
        handleDeleteConversation,
        handleUpdateAvatar,
        handleUpdateDescription,
        fetchPendingMembers,
        approveMember,
        rejectMember,

        // Modal Management
        closeModal,
        scrollToBottom
    };
};