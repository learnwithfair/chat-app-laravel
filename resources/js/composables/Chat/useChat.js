import { ref, computed, onMounted, onBeforeUnmount, nextTick, toRaw } from 'vue';
import axios from 'axios';
import { generateAvatar } from '../../Utils/Chat/avatarHelper';
import { usePage } from '@inertiajs/vue3';


export function useChat() {
    // ==================== CACHE STORAGE ====================
    const messageCache = new Map();
    const conversationCache = ref([]);
    const lastConversationFetch = ref(null);
    const CACHE_DURATION = 30000; // 30 seconds

    // State
    const conversations = ref([]);
    const messages = ref([]);
    const onlineUsers = ref([]);
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

    const pendingMembers = ref([]);

    const typingUsers = ref({});
    let typingTimeout = null;
    let globalPresenceChannel = null;

    const mediaLibrary = ref({
        media: [],
        audio: [],
        files: [],
        links: []
    });
    const mediaLibraryLoading = ref(false);

    // Pinned Messages
    const pinnedMessages = ref([]);
    const pinnedMessagesLoading = ref(false);
    const showPinnedBar = ref(false);

    const processedMessageIds = new Set();


    const page = usePage();

    const authUser = computed(() => page.props.auth?.user || null);

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
        startChat: false,
        mediaLibrary: false,
        mute: false
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

    const reactionModalData = ref({
        visible: false,
        messageId: null,
        reactions: [],
        users: []
    });

    // API Base URL
    const API_BASE = '/api/v1';

    // ==================== WEBSOCKET STATE ====================
    let userChannel = null;
    let conversationChannels = new Map();


    const getCurrentUserId = () => authUser.value?.id;

    // ==================== WEBSOCKET SUBSCRIPTIONS ====================


    const subscribeToGlobalPresence = () => {
        globalPresenceChannel = window.Echo.join('online')
            .here((users) => {
                console.log('👥 Online users:', users);
                onlineUsers.value = users.map(user => ({
                    id: user.id,
                    name: user.name,
                    avatar: user.avatar_path || generateAvatar(user.name),
                    isOnline: true
                }));
            })
            .joining((user) => {
                console.log('✅ User came online:', user);
                if (!onlineUsers.value.some(u => u.id === user.id)) {
                    onlineUsers.value.push({
                        id: user.id,
                        name: user.name,
                        avatar: user.avatar_path || generateAvatar(user.name),
                        isOnline: true
                    });
                }
            })
            .leaving((user) => {
                console.log('❌ User went offline:', user);
                onlineUsers.value = onlineUsers.value.filter(u => u.id !== user.id);
            });
    };

    const subscribeToUserChannel = () => {
        const userId = getCurrentUserId();
        if (!userId) {
            console.error('❌ Cannot subscribe: No authenticated user');
            return;
        }

        userChannel = window.Echo.private(`user.${userId}`)
            .listen('.ConversationEvent', (event) => {
                console.log('📢 ConversationEvent received:', event);
                handleConversationEvent(event);
            });

        console.log('✅ Subscribed to user channel:', userId);
    };

    const subscribeToConversation = (conversationId) => {
        if (conversationChannels.has(conversationId)) {
            console.log('⚠️ Already subscribed to conversation:', conversationId);
            return;
        }

        const channel = window.Echo.join(`conversation.${conversationId}`)
            .listen('.MessageEvent', (event) => {
                console.log('💬 MessageEvent received:', {
                    conversationId,
                    event
                });
                handleMessageEvent(conversationId, event);
            })
            .listenForWhisper('typing', (e) => {
                console.log('⌨️ Typing event:', e);
                handleTypingEvent(conversationId, e);
            })
            .here((users) => {
                console.log('👥 Users currently in conversation:', users);
                updateOnlineStatus(conversationId, users);
            })
            .joining((user) => {
                console.log('✅ User joined:', user);
                markUserOnline(user.id);
            })
            .leaving((user) => {
                console.log('❌ User left:', user);
                markUserOffline(user.id);
            });

        conversationChannels.set(conversationId, channel);
        console.log('✅ Subscribed to conversation:', conversationId);
    };

    const unsubscribeFromConversation = (conversationId) => {
        const channel = conversationChannels.get(conversationId);
        if (channel) {
            window.Echo.leave(`conversation.${conversationId}`);
            conversationChannels.delete(conversationId);
            console.log('❌ Unsubscribed from conversation:', conversationId);
        }
    };

    // ==================== WEBSOCKET EVENT HANDLERS ====================

    const handleConversationEvent = (event) => {
        console.log('🎯 handleConversationEvent:', event);
        const { action, conversation } = event;

        switch (action) {
            case 'added':
                addOrUpdateConversation(conversation);
                break;
            case 'removed':
                removeConversation(conversation.id);
                break;
            case 'left':
                if (activeConversation.value?.id === conversation.id) {
                    fetchGroupMembers();
                }
                break;
            case 'updated':
                updateConversationInfo(conversation);
                break;
            case 'deleted':
                removeConversation(conversation.id);
                break;
            case 'read':
                updateUnreadCount(conversation.id, 0);
                break;
        }
    };

    const handleMessageEvent = (conversationId, event) => {
        console.log('🎯 handleMessageEvent called:', {
            conversationId,
            eventType: event.type,
            hasPayload: !!event.payload
        });

        const { type, payload } = event;

        switch (type) {
            case 'sent':
                console.log('📨 Calling handleNewMessage with payload:', payload);
                handleNewMessage(payload);
                break;
            case 'updated':
                handleMessageUpdate(payload);
                break;
            case 'deleted_for_everyone':
                handleMessageDeletedForEveryone(payload);
                break;
            case 'reaction':
                handleReactionUpdate(payload);
                break;
            case 'delivered':
                handleMessageDelivered(payload);
                break;
            case 'pinned':
                handleMessagePinned(payload);
                break;
            case 'unpinned':
                handleMessageUnpinned(payload);
                break;
            case 'seen':
                handleMessageSeen(payload);
                break;
            default:
                console.warn('⚠️ Unknown message event type:', type);
        }
    };

    const handleTypingEvent = (conversationId, event) => {
        const { userId, userName, isTyping } = event;
        const currentUserId = getCurrentUserId();
        if (userId === currentUserId) {
            return; // Don't show own typing
        }

        if (!typingUsers.value[conversationId]) {
            typingUsers.value[conversationId] = [];
        }

        if (isTyping) {
            const existingUser = typingUsers.value[conversationId].find(u => u.id === userId);
            if (!existingUser) {
                typingUsers.value[conversationId].push({
                    id: userId,
                    name: userName,
                    avatar: generateAvatar(userName)
                });
                console.log('✅ User typing:', userName);
            }

            setTimeout(() => {
                typingUsers.value[conversationId] = typingUsers.value[conversationId]
                    .filter(u => u.id !== userId);
            }, 5000);
        } else {
            typingUsers.value[conversationId] = typingUsers.value[conversationId]
                .filter(u => u.id !== userId);
            console.log('✅ User stopped typing:', userName);
        }
    };

    // ==================== ONLINE STATUS HANDLERS ====================

    const updateOnlineStatus = (conversationId, users) => {
        const userIds = users.map(u => u.id);

        const conv = conversations.value.find(c => c.id === conversationId);
        if (conv && conv.type === 'private' && conv.receiver) {
            conv.isOnline = userIds.includes(conv.receiver.id);
        }

        if (activeConversation.value?.id === conversationId && activeConversation.value.receiver) {
            activeConversation.value.isOnline = userIds.includes(activeConversation.value.receiver.id);
        }
    };

    const markUserOnline = (userId) => {
        conversations.value.forEach(conv => {
            if (conv.type === 'private' && conv.receiver?.id === userId) {
                conv.isOnline = true;
            }
        });

        if (activeConversation.value?.receiver?.id === userId) {
            activeConversation.value.isOnline = true;
        }
    };

    const markUserOffline = (userId) => {
        conversations.value.forEach(conv => {
            if (conv.type === 'private' && conv.receiver?.id === userId) {
                conv.isOnline = false;
            }
        });

        if (activeConversation.value?.receiver?.id === userId) {
            activeConversation.value.isOnline = false;
        }
    };

    // ==================== MESSAGE EVENT HELPERS ====================

    const handleNewMessage = (incomingMsg) => {
        console.log('📬 handleNewMessage START:', {
            messageId: incomingMsg.id,
            conversationId: incomingMsg.conversation_id,
            senderId: incomingMsg.sender?.id,
            myId: getCurrentUserId(),
            activeConvId: activeConversation.value?.id,
            message: incomingMsg.message
        });

        const conversationId = parseInt(incomingMsg.conversation_id);
        const myId = getCurrentUserId();

        if (processedMessageIds.has(incomingMsg.id)) {
            console.log('⚠️ Duplicate prevented');
            return;
        }
        // Check if message already exists
        if (messages.value.some(msg => msg.id === incomingMsg.id)) {
            return;
        }

        processedMessageIds.add(incomingMsg.id);


        const formatted = {
            id: incomingMsg.id,
            text: incomingMsg.message,
            isMine: incomingMsg.sender?.id === myId,
            isPinned: incomingMsg.is_pinned || false,
            time: formatTime(incomingMsg.created_at),
            status: incomingMsg.sender?.id === myId ? 'sent' : 'delivered',
            senderName: incomingMsg.sender?.id === myId ? 'You' : (incomingMsg.sender?.name || 'Unknown'),
            senderAvatar: incomingMsg.sender?.avatar_path || generateAvatar(incomingMsg.sender?.name || 'User'),
            reactions: formatReactions(incomingMsg.reactions) || [],
            isDeleted: false,
            isEdited: false,
            messageType: incomingMsg.message_type || 'text',
            replyTo: incomingMsg.reply ? {
                id: incomingMsg.reply.id,
                senderName: incomingMsg.reply.sender.name,
                text: incomingMsg.reply.message
            } : null,
            forwardFrom: incomingMsg.forward ? {
                id: incomingMsg.forward.id,
                senderName: incomingMsg.forward.sender.name,
                text: incomingMsg.forward.message
            } : null,
            attachments: formatAttachments(incomingMsg.attachments),
            seenBy: []
        };

        console.log('📝 Formatted message:', formatted);

        // Update cache
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
                console.log("Cached add=======");
                cached.messages.push(formatted);
            }
        }

        // Update UI if active conversation
        if (activeConversation.value?.id === conversationId) {
            console.log('✅ Adding message to active conversation');
            messages.value = cached.messages;
            nextTick(() => scrollToBottom());
        } else {
            console.log('ℹ️ Message for inactive conversation:', conversationId);
        }

        // Update conversation list
        updateConversationPreview(conversationId, incomingMsg);

        // Increment unread if not active and not mine
        if (activeConversation.value?.id !== conversationId && !formatted.isMine) {
            const conv = conversations.value.find(c => c.id === conversationId);
            if (conv) {
                conv.unreadCount = (conv.unreadCount || 0) + 1;
                console.log('📬 Unread count updated:', conv.unreadCount);
            }
        }

        lastConversationFetch.value = null;
        console.log('✅ handleNewMessage COMPLETE');
    };

    const handleMessageUpdate = (messageData) => {
        const msg = messages.value.find(m => m.id === messageData.id);

        console.log("Update ============");
        console.log(msg);
        console.log("messageData");
        console.log(messageData);
        if (msg) {
            msg.text = messageData.message;
            msg.isEdited = true;
        }

        const cached = messageCache.get(messageData.conversation_id);
        if (cached) {
            const cachedMsg = cached.messages.find(m => m.id === messageData.id);
            if (cachedMsg) {
                cachedMsg.text = messageData.message;
                cachedMsg.isEdited = true;
            }
        }

        updateConversationPreview(messageData.conversation_id, messageData);
    };

    const handleMessageDeletedForEveryone = (messageData) => {
        const msg = messages.value.find(m => m.id === messageData.id);
        if (msg) {
            msg.isDeleted = true;
            msg.text = 'This message was deleted';
        }

        const cached = messageCache.get(messageData.conversation_id);
        if (cached) {
            const cachedMsg = cached.messages.find(m => m.id === messageData.id);
            if (cachedMsg) {
                cachedMsg.isDeleted = true;
                cachedMsg.text = 'This message was deleted';
            }
        }
    };

    const handleReactionUpdate = (reactionData) => {
        const msg = messages.value.find(m => m.id === reactionData.message_id);
        if (!msg) return;

        msg.reactions = buildGroupedReactions(reactionData.reactions);
    };

    const handleMessageDelivered = (statusData) => {
        const msg = messages.value.find(m => m.id === statusData.message_id);
        if (msg && msg.isMine) {
            msg.status = 'delivered';
        }
    };

    const handleMessagePinned = (messageData) => {
        const msg = messages.value.find(m => m.id === messageData.id);
        if (msg) {
            msg.isPinned = true;
        }

        if (activeConversation.value?.id === messageData.conversation_id) {
            fetchPinnedMessages(messageData.conversation_id);
        }
    };

    const handleMessageUnpinned = (messageData) => {
        const msg = messages.value.find(m => m.id === messageData.id);
        if (msg) {
            msg.isPinned = false;
        }

        pinnedMessages.value = pinnedMessages.value.filter(m => m.id !== messageData.id);

        if (pinnedMessages.value.length === 0) {
            showPinnedBar.value = false;
        }
    };

    // Add this new function
    const handleMessageSeen = (statusData) => {
        const msg = messages.value.find(m => m.id === statusData.message_id);
        if (msg && msg.isMine) {
            msg.status = 'seen';

            if (statusData.user) {
                if (!msg.seenBy) msg.seenBy = [];
                if (!msg.seenBy.some(u => u.id === statusData.user.id)) {
                    msg.seenBy.push({
                        id: statusData.user.id,
                        name: statusData.user.name,
                        avatar: statusData.user.avatar_path || generateAvatar(statusData.user.name),
                        seenAt: formatTime(statusData.created_at || new Date())
                    });
                }
            }
        }
    };

    // ==================== CONVERSATION EVENT HELPERS ====================

    const addOrUpdateConversation = async (convData) => {
        const existing = conversations.value.find(c => c.id === convData.id);

        if (existing) {
            updateConversationInfo(convData);
        } else {
            const formatted = {
                id: convData.id,
                type: convData.type,
                name: convData.name,
                avatar: convData.meta?.avatar || generateAvatar(convData.name),
                lastMessage: 'New conversation',
                lastMessageTime: 'Just now',
                unreadCount: 0,
                isOnline: false,
                isBlocked: false,
                blockedByMe: false,
                blockedByThem: false,
                members: [],
                settings: convData.meta || null,
                isMuted: false,
                receiver: null,
                is_admin: false,
                role: 'member',
                canSendMessage: true
            };

            conversations.value.unshift(formatted);
            lastConversationFetch.value = null;

            subscribeToConversation(convData.id);
            if (convData.type === 'group') {
                await fetchConversations();
            }
            console.log('🔔 New conversation added:', convData.name);
        }
    };

    const removeConversation = (conversationId) => {
        conversations.value = conversations.value.filter(c => c.id !== conversationId);

        if (activeConversation.value?.id === conversationId) {
            activeConversation.value = null;
        }

        messageCache.delete(conversationId);
        unsubscribeFromConversation(conversationId);
    };

    const updateConversationInfo = (convData) => {
        const index = conversations.value.findIndex(c => c.id === convData.id);
        if (index === -1) return;

        conversations.value[index] = {
            ...conversations.value[index],
            name: convData.name || conversations.value[index].name,
            avatar: convData.meta?.avatar || conversations.value[index].avatar,
            settings: convData.meta || conversations.value[index].settings
        };

        if (activeConversation.value?.id === convData.id) {
            activeConversation.value = { ...conversations.value[index] };
        }
    };

    const updateUnreadCount = (conversationId, count) => {
        const conv = conversations.value.find(c => c.id === conversationId);
        if (conv) {
            conv.unreadCount = count;
        }
    };

    // ==================== TYPING INDICATOR ====================

    const sendTypingIndicator = (isTyping) => {
        if (!activeConversation.value) return;

        const conversationId = activeConversation.value.id;
        const channel = conversationChannels.get(conversationId);

        if (!channel) {
            console.warn('⚠️ Cannot send typing: channel not found');
            return;
        }

        const userId = getCurrentUserId();
        const userName = window.authUser?.name || 'User';

        channel.whisper('typing', {
            userId,
            userName,
            isTyping
        });

        console.log('⌨️ Sent typing indicator:', { userId, userName, isTyping });
    };

    let typingDebounce = null;
    const handleTypingChange = (isTyping) => {
        if (isTyping) {
            sendTypingIndicator(true);

            clearTimeout(typingDebounce);
            typingDebounce = setTimeout(() => {
                sendTypingIndicator(false);
            }, 3000);
        } else {
            clearTimeout(typingDebounce);
            sendTypingIndicator(false);
        }
    };

    // ==================== API CALLS (Keep all existing ones) ====================

    const fetchAvailableUsers = async (search = null, page = 1, append = false) => {
        if (append) availableUsersPagination.value.loading = true;

        try {
            const params = { page, per_page: 20 };
            if (search) params.search = search;

            const response = await axios.get(`${API_BASE}/available-users`, { params });
            const data = response.data.data.data;
            const meta = response.data.data;

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

    const fetchStartChatUsers = async (search = null, page = 1, append = false) => {
        if (append) {
            startChatPagination.value.loading = true;
        } else {
            startChatLoading.value = true;
        }

        try {
            const params = { page, per_page: 20 };
            if (search) params.search = search;

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

    const loadMoreAvailableUsers = async () => {
        if (!availableUsersPagination.value.hasMore || availableUsersPagination.value.loading) return;
        await fetchAvailableUsers(searchQuery.value, availableUsersPagination.value.currentPage + 1, true);
    };

    const searchAvailableUsers = async () => {
        availableUsersPagination.value.currentPage = 1;
        await fetchAvailableUsers(searchQuery.value, 1, false);
    };

    const loadMoreStartChatUsers = async () => {
        if (!startChatPagination.value.hasMore || startChatPagination.value.loading) return;
        await fetchStartChatUsers(null, startChatPagination.value.currentPage + 1, true);
    };

    const searchStartChatUsers = async (query) => {
        startChatPagination.value.currentPage = 1;
        await fetchStartChatUsers(query, 1, false);
    };

    const fetchGroupMembers = async (page = 1, append = false) => {
        if (append) groupMembersPagination.value.loading = true;

        try {
            const conversationId = activeConversation.value?.id;
            if (!conversationId) return;

            const response = await axios.get(`${API_BASE}/group/${conversationId}/members`, {
                params: { page, per_page: 20 }
            });

            const data = response.data.data || [];

            const members = data.map(item => ({
                id: item.user.id,
                name: item.user.name,
                role: item.role,
                avatar: item.user.avatar_path || generateAvatar(item.user.name),
            }));

            if (append) {
                groupMembers.value.push(...members);
            } else {
                groupMembers.value = members;
            }

            const paginationMeta = response.data.meta || {};
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

    const fetchOnlineUsers = async () => {
        try {
            const response = await axios.get(`${API_BASE}/online-users`);
            onlineUsers.value = response.data.data.map(user => ({
                id: user.id,
                name: user.name,
                avatar: user.avatar_path || generateAvatar(user.name),
                isOnline: true
            }));
        } catch (error) {
            console.error('Failed to fetch online users:', error);
        }
    };

    const loadMoreGroupMembers = async () => {
        if (!groupMembersPagination.value.hasMore || groupMembersPagination.value.loading) return;
        await fetchGroupMembers(groupMembersPagination.value.currentPage + 1, true);
    };

    const fetchConversations = async (query = null, page = 1, append = false) => {
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

        if (append) conversationPagination.value.loading = true;
        else loading.value = true;

        try {
            const params = { page, per_page: 30 };
            if (query) params.query = query;

            const response = await axios.get(`${API_BASE}/conversations`, { params });
            const data = response.data;
            const convs = data.data;
            const meta = data.meta;

            const formattedConversations = convs.map(conv => {
                const name = conv.type === 'private' ? conv.receiver?.name : conv.name;
                const avatar =
                    conv.type === 'private'
                        ? conv.receiver?.avatar_path ?? generateAvatar(conv.receiver?.name)
                        : conv.group_setting?.avatar ?? generateAvatar(conv.name);

                const blocked = conv.blocked || { by_me: false, by_them: false };
                const isBlocked = conv.is_blocked || blocked.by_me || blocked.by_them;

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
                    isBlocked,
                    blockedByMe: blocked.by_me,
                    blockedByThem: blocked.by_them,
                    createdBy: conv.created_by,
                    createdAt: conv.created_at,
                    members: conv.participants || [],
                    settings: conv.group_setting || null,
                    isMuted: conv.is_muted || false,
                    receiver: conv.receiver || null,
                    is_admin: conv.is_admin,
                    role: conv.role,
                    canSendMessage: conv.can_send_message,
                    inviteLink: conv.invite_link
                };
            });

            if (append) {
                conversations.value = [...conversations.value, ...formattedConversations];
            } else {
                conversations.value = formattedConversations;
                conversationCache.value = formattedConversations;
                lastConversationFetch.value = Date.now();
            }

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

    const loadMoreConversations = async () => {
        if (!conversationPagination.value.hasMore || conversationPagination.value.loading) return;
        await fetchConversations(null, conversationPagination.value.currentPage + 1, true);
    };

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

            const formattedMessages = data.data

                .map(msg => {
                    processedMessageIds.add(msg.id); // ADD THIS LINE

                    return {
                        id: msg.id,
                        text: msg.message,
                        isMine: msg.is_mine,
                        isPinned: msg.is_pinned,
                        time: formatTime(msg.created_at),
                        status: getMessageStatus(msg.statuses),
                        senderName: msg.sender?.name || 'Unknown',
                        senderAvatar: msg.sender?.avatar_path || generateAvatar(msg.sender?.name),
                        reactions: formatReactions(msg.reactions),
                        isDeleted: msg.is_deleted_for_everyone || false,
                        isEdited: false,
                        messageType: msg.message_type || 'text',
                        replyTo: msg.reply ? {
                            id: msg.reply.id,
                            senderName: msg.reply.sender.name,
                            text: msg.reply.message
                        } : null,
                        forwardFrom: msg.forward ? {
                            id: msg.forward.id,
                            senderName: msg.forward.sender.name,
                            text: msg.forward.message
                        } : null,
                        attachments: formatAttachments(msg.attachments),
                        seenBy: msg.statuses
                            ?.filter(s => s.status === 'seen')
                            .map(s => ({
                                id: s.user_id,
                                name: s.name || 'Unknown',
                                avatar: s.avatar_path || generateAvatar(s.name || 'User'),
                                seenAt: formatTime(s.created_at || msg.created_at)
                            })) || []
                    };
                })
                .reverse();

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

    const fetchPinnedMessages = async (conversationId) => {
        pinnedMessagesLoading.value = true;

        try {
            const response = await axios.get(`${API_BASE}/messages/${conversationId}/pined-messages`);
            const data = response.data.data;

            pinnedMessages.value = data.map(msg => ({
                id: msg.id,
                text: msg.message,
                isMine: msg.is_mine,
                isPinned: msg.is_pinned,
                time: formatTime(msg.created_at),
                status: getMessageStatus(msg.statuses),
                senderName: msg.sender?.name || 'Unknown',
                senderAvatar: msg.sender?.avatar_path || generateAvatar(msg.sender?.name),
                reactions: formatReactions(msg.reactions),
                isDeleted: msg.is_deleted_for_everyone || false,
                messageType: msg.message_type || 'text',
                attachments: formatAttachments(msg.attachments),
                replyTo: msg.reply ? {
                    id: msg.reply.id,
                    senderName: msg.reply.sender.name,
                    text: msg.reply.message
                } : null,
                forwardFrom: msg.forward ? {
                    id: msg.forward.id,
                    senderName: msg.forward.sender.name,
                    text: msg.forward.message
                } : null,
            }));

            showPinnedBar.value = pinnedMessages.value.length > 0;

        } catch (error) {
            console.error('Failed to fetch pinned messages:', error);
            pinnedMessages.value = [];
        } finally {
            pinnedMessagesLoading.value = false;
        }
    };

    const togglePinMessageAPI = async (messageId) => {
        try {
            const response = await axios.post(`${API_BASE}/messages/${messageId}/toggle-pin`);
            return response.data;
        } catch (error) {
            console.error('Failed to toggle pin:', error);
            throw error;
        }
    };

    const fetchMediaLibrary = async (conversationId) => {
        mediaLibraryLoading.value = true;

        try {
            const response = await axios.get(`${API_BASE}/conversations/${conversationId}/media`);
            const data = response.data.data;

            mediaLibrary.value.media = (data.media || []).map(item => ({
                id: item.id,
                type: item.type,
                url: item.path,
                name: item.name || 'media',
                createdAt: item.created_at
            }));

            mediaLibrary.value.audio = (data.audio || []).map(item => ({
                id: item.id,
                type: item.type,
                url: item.path,
                name: item.name || 'audio',
                size: item.size || 0,
                createdAt: item.created_at
            }));

            mediaLibrary.value.files = (data.files || []).map(item => ({
                id: item.id,
                type: item.type,
                url: item.path,
                name: item.name || 'file',
                size: item.size || 0,
                createdAt: item.created_at
            }));

            mediaLibrary.value.links = (data.links || []).map(item => ({
                message_id: item.message_id,
                url: item.url,
                created_at: item.created_at
            }));

        } catch (error) {
            console.error('Failed to fetch media library:', error);
            mediaLibrary.value = {
                media: [],
                audio: [],
                files: [],
                links: []
            };
        } finally {
            mediaLibraryLoading.value = false;
        }
    };

    const handleOpenMediaLibrary = async () => {
        if (!activeConversation.value) return;
        modals.value.mediaLibrary = true;
        await fetchMediaLibrary(activeConversation.value.id);
    };

    const loadMoreMessages = async (conversationId) => {
        if (!messagePagination.value.hasMore || messagePagination.value.loading) return;
        await fetchMessages(conversationId, messagePagination.value.currentPage + 1, true);
    };

    const startPrivateConversationAPI = async (userId) => {
        try {
            const response = await axios.post(`${API_BASE}/conversations/private`, {
                receiver_id: userId
            });

            const conv = response.data.data;
            const blocked = conv.blocked || { by_me: false, by_them: false };
            const isBlocked = conv.is_blocked || blocked.by_me || blocked.by_them;

            const newConv = {
                id: conv.id,
                type: conv.type || 'private',
                name: conv.receiver?.name || '',
                avatar: conv.receiver?.avatar_path || generateAvatar(conv.receiver?.name),
                lastMessage: buildLastMessagePreview(conv.last_message) || 'No preview available',
                lastMessageTime: conv.last_message?.created_at
                    ? formatTime(conv.last_message.created_at)
                    : 'Just now',
                unreadCount: conv.unread_count || 0,
                isOnline: conv.receiver?.is_online || false,
                isBlocked,
                blockedByMe: blocked.by_me,
                blockedByThem: blocked.by_them,
                createdBy: conv.created_by,
                createdAt: conv.created_at,
                members: conv.participants || [],
                settings: conv.group_setting || null,
                isMuted: conv.is_muted || false,
                receiver: conv.receiver || null,
                is_admin: conv.is_admin,
                role: conv.role,
                canSendMessage: conv.can_send_message,
            };

            const existing = conversations.value.find(c => c.id === newConv.id);
            if (!existing) {
                conversations.value.unshift(newConv);
                lastConversationFetch.value = null;
            }

            return newConv;
        } catch (error) {
            console.error('Failed to start private conversation:', error);
            throw error;
        }
    };

    const sendMessageWithFilesAPI = async (formData) => {
        try {
            const response = await axios.post(`${API_BASE}/messages`, formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
            return response.data.data;
        } catch (error) {
            console.error('Failed to send message with files:', error);
            throw error;
        }
    };

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

    const markMessagesAsSeen = async (conversationId) => {
        try {
            await axios.get(`${API_BASE}/messages/seen/${conversationId}`);

            const conv = conversations.value.find(c => c.id === conversationId);
            if (conv) {
                conv.unreadCount = 0;
            }
        } catch (error) {
            console.error('Failed to mark messages as seen:', error);
        }
    };

    const toggleReactionAPI = async (messageId, emoji) => {
        try {
            const response = await axios.post(`${API_BASE}/messages/${messageId}/reaction`, {
                reaction: emoji
            });
            return response.data.data;
        } catch (error) {
            console.error('Failed to toggle reaction:', error);
            throw error;
        }
    };

    const getReactionsAPI = async (messageId) => {
        try {
            const response = await axios.get(`${API_BASE}/messages/${messageId}/reaction`);
            return response.data.data;
        } catch (error) {
            console.error('Failed to fetch reactions:', error);
            throw error;
        }
    };

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

            lastConversationFetch.value = null;
            return response.data.data;
        } catch (error) {
            console.error('Failed to create group:', error);
            throw error;
        }
    };

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

    const removeAdminAPI = async (conversationId, userIds) => {
        try {
            const response = await axios.post(`${API_BASE}/group/${conversationId}/admins/remove`, {
                member_ids: userIds
            });
            return response.data;
        } catch (error) {
            console.error('Failed to remove admin:', error);
            throw error;
        }
    };

    const leaveGroupAPI = async (conversationId) => {
        try {
            await axios.post(`${API_BASE}/group/${conversationId}/leave`);
            messageCache.delete(conversationId);
            lastConversationFetch.value = null;
        } catch (error) {
            console.error('Failed to leave group:', error);
            throw error;
        }
    };

    const updateGroupInfoAPI = async (conversationId, data) => {
        try {
            const formData = new FormData();

            if (data.name) {
                formData.append('name', data.name);
            }

            if (data.group) {
                Object.keys(data.group).forEach(key => {
                    let value = data.group[key];
                    if (value === null || value === undefined) return;
                    if (typeof value === 'boolean') {
                        value = value ? 1 : 0;
                    }
                    formData.append(`group[${key}]`, value);
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

    const toggleBlockAPI = async (userId) => {
        try {
            const response = await axios.post(`${API_BASE}/users/${userId}/block-toggle`);
            return response.data;
        } catch (error) {
            console.error('Failed to toggle block:', error);
            throw error;
        }
    };

    const toggleRestrictAPI = async (userId) => {
        try {
            const response = await axios.post(`${API_BASE}/users/${userId}/restrict-toggle`);
            return response.data;
        } catch (error) {
            console.error('Failed to toggle restrict:', error);
            throw error;
        }
    };

    const deleteConversationAPI = async (conversationId) => {
        try {
            await axios.delete(`${API_BASE}/conversations/${conversationId}`);
            messageCache.delete(conversationId);
            lastConversationFetch.value = null;
        } catch (error) {
            console.error('Failed to delete conversation:', error);
            throw error;
        }
    };

    const deleteGroupAPI = async (conversationId) => {
        try {
            await axios.delete(`${API_BASE}/group/${conversationId}/delete-group`);
            messageCache.delete(conversationId);
            lastConversationFetch.value = null;
        } catch (error) {
            console.error('Failed to delete group:', error);
            throw error;
        }
    };

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

    const updateGroupNameAPI = async (conversationId, name) => {
        try {
            const response = await axios.post(`${API_BASE}/group/${conversationId}/update`, {
                name: name
            });
            return response.data.data;
        } catch (error) {
            console.error('Failed to update description:', error);
            throw error;
        }
    };

    const fetchPendingMembers = async (conversationId) => {
        try {
            const response = await axios.get(`${API_BASE}/group/${conversationId}/members`);
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

    const forwardMessageAPI = async (messageId, conversationIds) => {
        try {
            const response = await axios.post(
                `${API_BASE}/messages/${messageId}/forward`,
                { conversation_ids: conversationIds }
            );
            return response.data.data;
        } catch (error) {
            console.error('Failed to forward message:', error);
            throw error;
        }
    };

    // ==================== HELPER FUNCTIONS ====================

    const formatTime = (datetime) => {
        const date = parseLocalDateTime(datetime);
        if (!date) return 'Just now';

        const diff = Date.now() - date.getTime();

        if (diff < 60_000) return 'Just now';
        if (diff < 3_600_000) return `${Math.floor(diff / 60_000)}m`;
        if (diff < 86_400_000) return `${Math.floor(diff / 3_600_000)}h`;

        return date.toLocaleTimeString('en-US', {
            hour: 'numeric',
            minute: '2-digit',
            hour12: true,
        });
    };

    const parseLocalDateTime = (datetime) => {
        if (!datetime) return null;
        if (datetime instanceof Date) return datetime;
        if (typeof datetime !== 'string') return null;

        const iso = datetime.replace(' ', 'T');
        const date = new Date(iso);

        return isNaN(date.getTime()) ? null : date;
    };

    const formatReactions = (reactions) => {
        if (!reactions || !reactions.reactions) return [];

        return Object.entries(reactions.reactions).map(([emoji, count]) => ({
            emoji,
            count
        }));
    };

    const formatAttachment = (attachment) => {
        return {
            id: attachment.id,
            type: attachment.type,
            url: attachment.path,
            name: attachment.name || 'file',
            size: attachment.size || 0
        };
    };

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

    const buildGroupedReactions = (list) => {
        const map = {};

        list.forEach(item => {
            if (!map[item.reaction]) {
                map[item.reaction] = {
                    emoji: item.reaction,
                    count: 0
                };
            }
            map[item.reaction].count++;
        });

        return Object.values(map);
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

    const updateConversationPreview = (conversationId, message) => {
        const index = conversations.value.findIndex(c => c.id === conversationId);
        if (index === -1) return;

        const conv = conversations.value[index];

        conversations.value[index] = {
            ...conv,
            lastMessage: buildLastMessagePreview(message),
            lastMessageTime: formatTime(message.created_at || new Date())
        };

        // Move to top
        const [movedConv] = conversations.value.splice(index, 1);
        conversations.value.unshift(movedConv);
    };

    const clearCache = () => {
        messageCache.clear();
        conversationCache.value = [];
        lastConversationFetch.value = null;
        processedMessageIds.clear(); // ADD THIS LINE
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

        return activeConversation.value.isOnline
            ? 'Online'
            : activeConversation.value.receiver.last_seen
                ? 'Offline • ' + activeConversation.value.receiver.last_seen
                : 'Offline';
    });

    const getConversationAvatar = computed(() => {
        if (!activeConversation.value) return '';
        return activeConversation.value.avatar || activeConversation.value.members?.[0]?.avatar || '';
    });

    // ==================== UI METHODS ====================

    const selectConversation = async (conversation) => {
        activeConversation.value = conversation;

        messagePagination.value = {
            currentPage: 1,
            lastPage: 1,
            hasMore: true,
            loading: false
        };

        processedMessageIds.clear();
        await fetchMessages(conversation.id);

        messages.value.forEach(msg => processedMessageIds.add(msg.id));
        // Subscribe to conversation channel
        subscribeToConversation(conversation.id);

        nextTick(() => scrollToBottom());

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

    const handleStartChatUserSelect = async (user) => {
        try {
            const existingConv = conversations.value.find(
                c => c.type === 'private' && c.receiver?.id === user.id
            );

            if (existingConv) {
                selectConversation(existingConv);
            } else {
                const newConv = await startPrivateConversationAPI(user.id);
                selectConversation(newConv);
            }

            modals.value.startChat = false;
            startChatUsers.value = [];

        } catch (error) {
            console.error('Failed to start chat:', error);
            alert('Failed to start chat. Please try again.');
        }
    };

    const handleSendMessage = async (filesFromInput = null) => {

        console.log("called ==============");
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

                sentMsg = await sendMessageWithFilesAPI(formData);
                console.log("sentMsg============");
                console.log(sentMsg);
                processedMessageIds.add(sentMsg.id);

                // Optimistic UI update (will be replaced by WebSocket event)
                // const newMsg = {
                //     id: sentMsg.id,
                //     text: sentMsg.message,
                //     isMine: true,
                //     isPinned: sentMsg.is_pinned,
                //     time: formatTime(sentMsg.created_at),
                //     status: 'sent',
                //     senderName: 'You',
                //     senderAvatar: '',
                //     reactions: [],
                //     isDeleted: false,
                //     isEdited: false,
                //     messageType: sentMsg.message_type || 'text',
                //     replyTo: replyingTo.value ? {
                //         senderName: replyingTo.value.senderName,
                //         text: replyingTo.value.text
                //     } : null,
                //     forwardFrom: sentMsg.forward ? {
                //         id: sentMsg.forward.id,
                //         senderName: sentMsg.forward.sender.name,
                //         text: sentMsg.forward.message
                //     } : null,
                //     attachments: formatAttachments(sentMsg.attachments),
                //     seenBy: []
                // };

                // // Only add if not already exists (WebSocket might have beaten us)
                // if (!messages.value.some(m => m.id === newMsg.id)) {
                //     messages.value.push(newMsg);
                // }

                // const cached = messageCache.get(activeConversation.value.id);
                // if (!cached) {
                //     messageCache.set(activeConversation.value.id, {
                //         messages: [newMsg],
                //         currentPage: 1,
                //         lastPage: 1,
                //         hasMore: false,
                //         loading: false
                //     });
                // } else {
                //     if (!cached.messages.some(m => m.id === newMsg.id)) {
                //         cached.messages.push(newMsg);
                //     }
                // }

                replyingTo.value = null;
                updateConversationPreview(activeConversation.value.id, sentMsg);
            }

            newMessage.value = '';
            selectedFiles.value = [];

            nextTick(() => {
                scrollToBottom();
            });

        } catch (error) {
            if (error.response) {
                console.error('Response error:', error.response.data);
            }
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
        try {
            const response = await forwardMessageAPI(message.id, conversationIds);

            response.forEach((sentMsg) => {
                const newMsg = {
                    id: sentMsg.id,
                    text: sentMsg.message,
                    isMine: true,
                    isPinned: false,
                    time: formatTime(sentMsg.created_at),
                    status: 'sent',
                    senderName: 'You',
                    senderAvatar: '',
                    reactions: [],
                    isDeleted: false,
                    isEdited: false,
                    messageType: sentMsg.message_type || 'text',
                    replyTo: null,
                    forwardFrom: sentMsg.forward?.id ? {
                        id: sentMsg.forward.id,
                        senderName: sentMsg.forward.sender?.name || '',
                        text: sentMsg.forward.message || '',
                    } : null,
                    attachments: formatAttachments(sentMsg.attachments),
                    seenBy: [],
                };

                if (sentMsg.conversation_id === activeConversation.value?.id) {
                    if (!messages.value.some((m) => m.id === newMsg.id)) {
                        messages.value.push(newMsg);
                    }

                    nextTick(() => {
                        scrollToBottom();
                    });
                }

                let cached = messageCache.get(sentMsg.conversation_id);
                if (!cached) {
                    cached = {
                        messages: [],
                        currentPage: 1,
                        lastPage: 1,
                        hasMore: true,
                        loading: false,
                    };
                    messageCache.set(sentMsg.conversation_id, cached);
                }

                if (!cached.messages.some((m) => m.id === newMsg.id)) {
                    cached.messages.push(newMsg);
                }

                updateConversationPreview(sentMsg.conversation_id, sentMsg);
            });

            modals.value.forwardMessage = false;
        } catch (error) {
            console.error('Forward failed:', error);
        }
    };

    const handleTogglePin = async (message) => {
        const previousState = message.isPinned;

        try {
            const index = messages.value.findIndex(m => m.id === message.id);

            if (index !== -1) {
                messages.value[index] = {
                    ...messages.value[index],
                    isPinned: !previousState
                };
            }

            await togglePinMessageAPI(message.id);

            const cached = messageCache.get(activeConversation.value?.id);
            if (cached) {
                const cachedIndex = cached.messages.findIndex(m => m.id === message.id);
                if (cachedIndex !== -1) {
                    cached.messages[cachedIndex] = {
                        ...cached.messages[cachedIndex],
                        isPinned: !previousState
                    };
                }
            }

            if (activeConversation.value) {
                await fetchPinnedMessages(activeConversation.value.id);
            }

        } catch (error) {
            console.error('Failed to toggle pin:', error);

            const index = messages.value.findIndex(m => m.id === message.id);
            if (index !== -1) {
                messages.value[index] = {
                    ...messages.value[index],
                    isPinned: previousState
                };
            }

            alert('Failed to pin/unpin message');
        }
    };

    const closePinnedBar = () => {
        showPinnedBar.value = false;
    };

    const openPinnedBar = () => {
        if (pinnedMessages.value.length > 0) {
            showPinnedBar.value = true;
        }
    };

    const showDeleteMenu = (message) => {
        messageToDelete.value = message;
        modals.value.deleteMessage = true;
    };

    const deleteMessageForMe = async () => {
        try {
            await deleteMessageForMeAPI([messageToDelete.value.id]);
            messages.value = messages.value.filter(m => m.id !== messageToDelete.value.id);

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

    const openReactionModal = async ({ message, reactions }) => {
        reactionModalData.value = {
            visible: true,
            messageId: message.id,
            reactions: reactions || [],
            users: []
        };
        modals.value.reaction = true;
    };

    const handleReactionFetch = async ({ messageId, callback, errorCallback }) => {
        try {
            const response = await getReactionsAPI(messageId);

            const allUsers = [];

            if (response.grouped) {
                Object.keys(response.grouped).forEach(emoji => {
                    const emojiData = response.grouped[emoji];

                    emojiData.users.forEach(user => {
                        allUsers.push({
                            id: user.user_id,
                            name: user.name || 'Unknown',
                            avatar: user.avatar_path || generateAvatar(user.name || 'User'),
                            reaction: emoji
                        });
                    });
                });
            }

            callback(allUsers);
        } catch (error) {
            console.error('Failed to fetch reactions:', error);
            errorCallback(error);
        }
    };

    const closeReactionModal = () => {
        modals.value.reaction = false;
        reactionModalData.value = {
            visible: false,
            messageId: null,
            reactions: [],
            users: []
        };
    };

    const openMessageDetails = (message) => {
        if (message.isDeleted) return;
        selectedMessageDetails.value = message;
        modals.value.messageDetails = true;
    };

    const openStartChatModal = async () => {
        modals.value.startChat = true;

        startChatUsers.value = [];
        startChatPagination.value = {
            currentPage: 1,
            lastPage: 1,
            hasMore: true,
            loading: false,
        };

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

    const openCreateGroupModal = async () => {
        modals.value.createGroup = true;
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
                createdBy: newGroup.created_by,
                createdAt: newGroup.created_at,
                members: newGroup.participants || [],
                settings: newGroup.group_setting || null,
                isMuted: false,
                receiver: null,
                is_admin: newGroup.is_admin || false,
                role: newGroup.role,
                canSendMessage: newGroup.can_send_message,
            });

            selectConversation(conversations.value[0]);
            closeModal('createGroup');
        } catch (error) {
            console.error('Failed to create group:', error);
        }
    };

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

            closeModal('addMember');

        } catch (err) {
            console.error('Error adding members:', err);
        }
    };

    const makeAdmin = async (member) => {
        if (!confirm('Are you sure you want to make this admin?')) return;
        try {
            await addAdminAPI(activeConversation.value.id, [member.id]);

            const index1 = activeConversation.value.members.findIndex(m => m.id === member.id);
            if (index1 !== -1) {
                activeConversation.value.members[index1] = {
                    ...activeConversation.value.members[index1],
                    role: 'admin'
                };
            }

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
        if (!confirm('Are you sure you want to remove this admin?')) return;
        try {
            await removeAdminAPI(activeConversation.value.id, [member.id]);

            const index1 = activeConversation.value.members.findIndex(m => m.id === member.id);
            if (index1 !== -1) {
                activeConversation.value.members[index1] = {
                    ...activeConversation.value.members[index1],
                    role: 'member'
                };
            }

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

    const removeMember = async (member) => {
        if (!confirm(`Remove ${member.name} from the group?`)) return;

        try {
            const res = await removeMemberAPI(activeConversation.value.id, [member.id]);

            const removedMembers = res?.data?.original?.data?.members || [];

            removedMembers.forEach(u => {
                groupMembers.value = groupMembers.value.filter(m => m.id !== u.id);
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

    const handleAddReaction = async ({ messageId, emoji }) => {
        try {
            const reactions = await toggleReactionAPI(messageId, emoji);

            const message = messages.value.find(m => m.id === messageId);
            if (!message) return;

            message.reactionList = reactions;
            message.reactions = buildGroupedReactions(reactions);

        } catch (error) {
            console.error("Failed to toggle reaction:", error);
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

    const handleSendVoice = async (audioBlob, duration) => {
        if (!activeConversation.value) return;

        const audioUrl = URL.createObjectURL(audioBlob);

        const voiceMsg = {
            id: Date.now(),
            text: 'Voice message',
            isMine: true,
            isPinned: false,
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

    const handleTabChange = async (tab) => {
        activeRightTab.value = tab;

        if (!activeConversation.value) return;

        const conversationId = activeConversation.value.id;
        if (tab === 'members' && activeConversation.value.type === 'group') {
            if (groupMembers.value.length === 0) {
                await fetchGroupMembers();
            }
        }
    };

    const handleToggleBlock = async (conversationId) => {
        try {
            const conv = conversations.value.find(c => c.id === conversationId);
            if (!conv || !conv.receiver) return;

            await toggleBlockAPI(conv.receiver.id);

            conv.isBlocked = !conv.isBlocked;
            conv.blockedByMe = !conv.blockedByMe;
            conv.blockedByThem = !conv.blockedByThem;

            if (activeConversation.value?.id === conversationId) {
                activeConversation.value.isBlocked = conv.isBlocked;
                activeConversation.value.blockedByMe = conv.blockedByMe;
                activeConversation.value.blockedByThem = !conv.blockedByThem;
            }
        } catch (error) {
            console.error('Failed to toggle block:', error);
            alert('Failed to update block status');
        }
    };

    const handleToggleMute = () => {
        modals.value.mute = true;
    };

    const handleMuteAction = async (minutes) => {
        try {
            if (!activeConversation.value) return;

            await muteGroupAPI(activeConversation.value.id, minutes);

            if (minutes === 0) {
                activeConversation.value.isMuted = false;
                activeConversation.value.mutedUntil = null;
            } else {
                activeConversation.value.isMuted = true;
                if (minutes === -1) {
                    activeConversation.value.mutedUntil = 'forever';
                } else {
                    const mutedUntil = new Date();
                    mutedUntil.setMinutes(mutedUntil.getMinutes() + minutes);
                    activeConversation.value.mutedUntil = mutedUntil.toISOString();
                }
            }

            const conv = conversations.value.find(c => c.id === activeConversation.value.id);
            if (conv) {
                conv.isMuted = activeConversation.value.isMuted;
                conv.mutedUntil = activeConversation.value.mutedUntil;
            }

            modals.value.mute = false;

        } catch (error) {
            console.error('Failed to mute/unmute:', error);
            alert('Failed to update notification settings');
        }
    };

    const handleDeleteConversation = async (conversationId) => {
        if (!confirm('Are you sure you want to delete this conversation?')) return;
        try {
            await deleteConversationAPI(conversationId);

            conversations.value = conversations.value.filter(c => c.id !== conversationId);

            if (activeConversation.value?.id === conversationId) {
                activeConversation.value = null;
                showRightPanel.value = false;
            }
        } catch (error) {
            console.error('Failed to delete conversation:', error);
            alert('Failed to delete conversation');
        }
    };

    const handleDeleteGroup = async (conversationId) => {
        if (!confirm('Are you sure you want to delete this group?')) return;
        try {
            await deleteGroupAPI(conversationId);

            conversations.value = conversations.value.filter(c => c.id !== conversationId);

            if (activeConversation.value?.id === conversationId) {
                activeConversation.value = null;
                showRightPanel.value = false;
            }
        } catch (error) {
            console.error('Failed to delete group:', error);
            alert('Failed to delete group');
        }
    };

    const handleUpdateAvatar = async (file) => {
        try {
            if (!activeConversation.value || activeConversation.value.type !== 'group') return;

            const updated = await updateGroupAvatarAPI(activeConversation.value.id, file);

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

    const handleUpdateDescription = async (description) => {
        try {
            if (!activeConversation.value || activeConversation.value.type !== 'group') return;

            await updateGroupDescriptionAPI(activeConversation.value.id, description);

            if (activeConversation.value.settings) {
                activeConversation.value.settings.description = description;
            }
        } catch (error) {
            console.error('Failed to update description:', error);
            alert('Failed to update group description');
        }
    };

    const handleUpdateName = async (name) => {
        try {
            if (!activeConversation.value || activeConversation.value.type !== 'group') return;

            await updateGroupNameAPI(activeConversation.value.id, name);

            if (activeConversation.value) {
                activeConversation.value.name = name;
            }
        } catch (error) {
            console.error('Failed to update name:', error);
            alert('Failed to update group name');
        }
    };

    const approveMember = async (userId) => {
        try {
            if (!activeConversation.value) return;

            await approveMemberAPI(activeConversation.value.id, userId);

            pendingMembers.value = pendingMembers.value.filter(m => m.id !== userId);

            await fetchGroupMembers();
        } catch (error) {
            console.error('Failed to approve member:', error);
            alert('Failed to approve member');
        }
    };

    const rejectMember = async (userId) => {
        try {
            if (!activeConversation.value) return;

            await rejectMemberAPI(activeConversation.value.id, userId);

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

    const scrollToBottom = () => {
        if (messageContainer.value) {
            messageContainer.value.scrollTop = messageContainer.value.scrollHeight;
        }
    };

    // ==================== LIFECYCLE ====================

    onMounted(async () => {
        await fetchConversations();
        await fetchOnlineUsers();

        if (window.innerWidth >= 768 && conversations.value.length > 0) {
            selectConversation(conversations.value[0]);
        }

        // Subscribe to user's personal channel
        subscribeToUserChannel();
        subscribeToGlobalPresence();
    });

    onBeforeUnmount(() => {
        if (userChannel) {
            window.Echo.leave(`user.${getCurrentUserId()}`);
        }

        if (globalPresenceChannel) {
            window.Echo.leave('online'); // Add this
        }

        conversationChannels.forEach((channel, conversationId) => {
            window.Echo.leave(`conversation.${conversationId}`);
        });

        conversationChannels.clear();
        clearTimeout(typingDebounce);
    });
    return {
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

        mediaLibrary,
        mediaLibraryLoading,
        handleOpenMediaLibrary,

        reactionModalData,
        openReactionModal,
        closeReactionModal,
        handleReactionFetch,

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
        handleMuteAction,
        handleDeleteConversation,
        handleDeleteGroup,
        handleUpdateAvatar,
        handleUpdateDescription,
        handleUpdateName,
        fetchPendingMembers,
        approveMember,
        rejectMember,

        pinnedMessages,
        pinnedMessagesLoading,
        showPinnedBar,
        fetchPinnedMessages,
        handleTogglePin,
        closePinnedBar,
        openPinnedBar,

        closeModal,
        scrollToBottom,

        // WebSocket
        handleTypingChange,
        clearCache,
    };
}