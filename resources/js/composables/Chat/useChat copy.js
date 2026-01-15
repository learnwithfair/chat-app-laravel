import { ref, computed, onMounted, nextTick, toRaw } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { generateAvatar } from '../../Utils/Chat/avatarHelper';

export function useChat() {
    // State
    const conversations = ref([]);
    const messages = ref([]);
    // const onlineUsers = ref([]);
    // const availableUsers = ref([]);
    const onlineUsers = ref([
        { id: 10, name: 'Alex', avatar: 'https://i.pravatar.cc/150?img=11', isOnline: true },
        { id: 11, name: 'Sam', avatar: 'https://i.pravatar.cc/150?img=12', isOnline: true },
        { id: 12, name: 'Jordan', avatar: 'https://i.pravatar.cc/150?img=13', isOnline: true }
    ]);

    const availableUsers = ref([
        { id: 20, name: 'Chris Evans', avatar: 'https://i.pravatar.cc/150?img=33' },
        { id: 21, name: 'Emma Stone', avatar: 'https://i.pravatar.cc/150?img=34' },
        { id: 22, name: 'Ryan Gosling', avatar: 'https://i.pravatar.cc/150?img=35' }
    ]);
    const activeConversation = ref(null);
    const searchQuery = ref('');
    const activeTab = ref('all');
    const activeRightTab = ref('members');
    const showRightPanel = ref(false);
    const newMessage = ref('');
    const replyingTo = ref(null);
    const editingMessage = ref(null);
    const loading = ref(false);
    const messagesLoading = ref(false);

    const typingUsers = ref({});
    let typingTimeout = null;

    // Modal states
    const modals = ref({
        createGroup: false,
        addMember: false,
        reaction: false,
        seenBy: false,
        messageDetails: false,
        deleteMessage: false,
        forwardMessage: false
    });

    const selectedReactionUsers = ref([]);
    const currentSeenBy = ref([]);
    const selectedMessageDetails = ref(null);
    const messageToDelete = ref(null);
    const messageToForward = ref(null);
    const messageContainer = ref(null);

    // API Base URL
    const API_BASE = '/api/v1';

    // ==================== API CALLS ====================

    // Fetch conversations

    const fetchConversations = async (query = null) => {
        loading.value = true;
        try {
            const params = {};
            if (query) params.query = query;

            const response = await axios.get(`${API_BASE}/conversations`, { params });

            conversations.value = response.data.data.data.map(conv => {
                const name = conv.type === 'private' ? conv.receiver?.name : conv.name;
                const avatarPath = conv.type === 'private' ? conv.receiver?.avatar_path ?? generateAvatar(conv.receiver?.name) : conv.group_setting?.avatar ?? generateAvatar(conv.name);

                return {
                    id: conv.id,
                    type: conv.type,
                    name: name || 'Unknown',
                    avatar: avatarPath,
                    lastMessage: conv.last_message?.message || '',
                    lastMessageTime: conv.last_message?.created_at ? formatTime(conv.last_message.created_at) : '',
                    unreadCount: conv.unread_count || 0,
                    isOnline: conv.receiver?.is_online || false,
                    isBlocked: conv.is_blocked || false,
                    created_by: conv.is_admin ? 1 : 0,
                    members: conv.participants || [],
                    settings: conv.group_setting || null,
                    isMuted: conv.is_muted || false,
                    receiver: conv.receiver || null
                };
            });
        } catch (error) {
            console.error('Failed to fetch conversations:', error);
        } finally {
            loading.value = false;
        }
    };

    // Fetch messages for a conversation
    const fetchMessages = async (conversationId) => {
        messagesLoading.value = true;
        try {
            const response = await axios.get(`${API_BASE}/messages/${conversationId}`);

            messages.value = response.data.data.map(msg => ({
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
                replyTo: msg.reply ? {
                    senderName: msg.reply.sender.name,
                    text: msg.reply.message
                } : null,
                file: msg.attachments?.length > 0 ? formatAttachment(msg.attachments[0]) : null,
                seenBy: msg.statuses?.filter(s => s.status === 'seen').map(s => ({
                    id: s.user_id,
                    name: s.user?.name || 'Unknown',
                    avatar: s.user?.avatar_path || generateAvatar(s.user?.name || 'User'),
                    seenAt: formatTime(s.created_at || msg.created_at)
                })) || []
            }));

            // Mark as seen
            await markMessagesAsSeen(conversationId);
        } catch (error) {
            console.error('Failed to fetch messages:', error);
        } finally {
            messagesLoading.value = false;
        }
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
                avatar: conv.receiver?.avatar_path || '',
                lastMessage: '',
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
            console.log(response.data);
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
                participants: groupData.members, // array of user IDs
                group: {
                    description: groupData.description,
                    type: groupData.type
                }
            });
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
                user_ids: memberIds
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
                user_ids: memberIds
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
                user_ids: userIds
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
                user_ids: userIds
            });
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
        } catch (error) {
            console.error('Failed to leave group:', error);
            throw error;
        }
    };

    // Update group info
    const updateGroupInfoAPI = async (conversationId, data) => {
        try {
            const formData = new FormData();
            formData.append('name', data.name);

            if (data.group) {
                Object.keys(data.group).forEach(key => {
                    if (data.group[key] !== null && data.group[key] !== undefined) {
                        formData.append(`group[${key}]`, data.group[key]);
                    }
                });
            }

            const response = await axios.post(`${API_BASE}/group/${conversationId}/update`, formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
            return response.data.data;
        } catch (error) {
            console.error('Failed to update group:', error);
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
        } catch (error) {
            console.error('Failed to delete conversation:', error);
            throw error;
        }
    };

    // ==================== HELPER FUNCTIONS ====================

    const formatTime = (datetime) => {
        const date = new Date(datetime);
        const now = new Date();
        const diff = now - date;

        // Less than 1 minute
        if (diff < 60000) return 'Just now';
        // Less than 1 hour
        if (diff < 3600000) return `${Math.floor(diff / 60000)}m`;
        // Less than 1 day
        if (diff < 86400000) return `${Math.floor(diff / 3600000)}h`;
        // Less than 1 week
        if (diff < 604800000) return `${Math.floor(diff / 86400000)}d`;

        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
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
            type: attachment.type,
            url: attachment.url,
            name: attachment.name
        };
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
        // Get from your auth store or window object
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
                // Safe name check
                const nameMatch = rawConv.name ? rawConv.name.toLowerCase().includes(query) : false;
                // Safe lastMessage check
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

    const handleSendMessage = async () => {
        if (!newMessage.value.trim() || !activeConversation.value) return;

        const messageText = newMessage.value;
        const replyToId = replyingTo.value?.id || null;

        try {
            if (editingMessage.value) {
                // Update existing message
                await updateMessageAPI(editingMessage.value.id, messageText);

                const msg = messages.value.find(m => m.id === editingMessage.value.id);
                if (msg) {
                    msg.text = messageText;
                    msg.isEdited = true;
                }
                editingMessage.value = null;
            } else {
                // Send new message
                const sentMsg = await sendMessageAPI(activeConversation.value.id, {
                    text: messageText,
                    replyToId
                });

                // Add to messages array
                messages.value.push({
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
                    replyTo: replyingTo.value ? {
                        senderName: replyingTo.value.senderName,
                        text: replyingTo.value.text
                    } : null,
                    file: null,
                    seenBy: []
                });

                replyingTo.value = null;
            }

            newMessage.value = '';
            nextTick(() => {
                scrollToBottom();
            });
        } catch (error) {
            console.error('Failed to send message:', error);
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
        // Implement forward logic with API
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
    const openCreateGroupModal = () => {
        modals.value.createGroup = true;
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
                avatar: null,
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

    const openAddMemberModal = () => {
        modals.value.addMember = true;
    };

    const addMembersToGroup = async (userIds) => {
        if (!activeConversation.value || activeConversation.value.type !== 'group') return;

        try {
            await addMembersToGroupAPI(activeConversation.value.id, userIds);

            // Refresh conversation or add members locally
            userIds.forEach((userId) => {
                const user = availableUsers.value.find(u => u.id === userId);
                if (user) {
                    activeConversation.value.members.push({
                        ...user,
                        role: 'Member'
                    });
                }
            });

            closeModal('addMember');
        } catch (error) {
            console.error('Failed to add members:', error);
        }
    };

    const makeAdmin = async (member) => {
        try {
            await addAdminAPI(activeConversation.value.id, [member.id]);
            const m = activeConversation.value.members.find(mem => mem.id === member.id);
            if (m) m.role = 'admin';
        } catch (error) {
            console.error('Failed to make admin:', error);
        }
    };

    const removeAdmin = async (member) => {
        try {
            await removeAdminAPI(activeConversation.value.id, [member.id]);
            const m = activeConversation.value.members.find(mem => mem.id === member.id);
            if (m) m.role = 'member';
        } catch (error) {
            console.error('Failed to remove admin:', error);
        }
    };

    const removeMember = async (member) => {
        if (!confirm(`Remove ${member.name} from the group?`)) return;

        try {
            await removeMemberAPI(activeConversation.value.id, [member.id]);
            activeConversation.value.members = activeConversation.value.members.filter(m => m.id !== member.id);
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
            const updated = await updateGroupInfoAPI(activeConversation.value.id, settings);
            activeConversation.value.settings = { ...activeConversation.value.settings, ...updated.group_setting };
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

        // Modal Management
        closeModal,
        scrollToBottom
    };
};