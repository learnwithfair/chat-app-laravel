import { ref, computed, onMounted, nextTick } from 'vue';
import { router } from '@inertiajs/vue3';

export function useChat() {
    // State
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
    ]);

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
            text: 'I\'m doing great! Thanks for asking.',
            isMine: true,
            time: '10:32 AM',
            status: 'delivered',
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
            text: 'Did you get my previous message?',
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
            text: 'I\'m doing great! Thanks for asking.',
            isMine: true,
            time: '10:32 AM',
            status: 'sent',
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
            id: 5,
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
            id: 6,
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
            id: 7,
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
            id: 8,
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
            id: 9,
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
            id: 10,
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
        // {
        //     id: 11,
        //     text: 'I also have this document that might help.',
        //     isMine: false,
        //     time: '10:47 AM',
        //     status: 'delivered',
        //     senderName: 'John Doe',
        //     senderAvatar: 'https://i.pravatar.cc/150?img=1',
        //     reactions: [],
        //     isDeleted: false,
        //     isEdited: false,
        //     replyTo: null,
        //     file: {
        //         type: 'document',
        //         url: '#',
        //         name: 'project-requirements.pdf'
        //     },
        //     seenBy: null
        // },
        {
            id: 12,
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

    const typingUsers = ref({}); // { conversationId: ['User 1', 'User 2'] }
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

    // Computed
    const filteredConversations = computed(() => {
        let filtered = conversations.value;

        if (activeTab.value !== 'all') {
            filtered = filtered.filter(conv => conv.type === activeTab.value);
        }

        if (searchQuery.value) {
            const query = searchQuery.value.toLowerCase();
            filtered = filtered.filter(conv => {
                return conv.name.toLowerCase().includes(query) ||
                    conv.lastMessage.toLowerCase().includes(query);
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

    // Methods
    const selectConversation = (conversation) => {
        activeConversation.value = conversation;
        nextTick(() => {
            scrollToBottom();
        });
    };

    const closeChatOnMobile = () => {
        activeConversation.value = null;
    };

    const startPrivateChat = (user) => {
        const existing = conversations.value.find(c => c.type === 'private' && c.name === user.name);
        if (existing) {
            selectConversation(existing);
            return;
        }

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

    const handleSendMessage = () => {
        if (!newMessage.value.trim()) return;

        if (editingMessage.value) {
            const msg = messages.value.find(m => m.id === editingMessage.value.id);
            if (msg) {
                msg.text = newMessage.value;
                msg.isEdited = true;
            }
            editingMessage.value = null;
        } else {
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

            setTimeout(() => {
                newMsg.status = 'delivered';
            }, 1000);

            setTimeout(() => {
                newMsg.status = 'seen';
                newMsg.seenBy = [{ id: 1, name: 'John Doe', avatar: 'https://i.pravatar.cc/150?img=1', seenAt: 'Just now' }];
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

    const forwardMessage = (message) => {
        messageToForward.value = message;
        modals.value.forwardMessage = true;
    };

    const handleForwardMessage = ({ message, conversationIds }) => {
        console.log('Forwarding message to:', conversationIds);
        // Implement forward logic
    };

    const showDeleteMenu = (message) => {
        messageToDelete.value = message;
        modals.value.deleteMessage = true;
    };

    const deleteMessageForMe = () => {
        messages.value = messages.value.filter(m => m.id !== messageToDelete.value.id);
        closeModal('deleteMessage');
    };

    const deleteMessageForEveryone = () => {
        const msg = messages.value.find(m => m.id === messageToDelete.value.id);
        if (msg) {
            msg.isDeleted = true;
            msg.text = 'This message was deleted';
        }
        closeModal('deleteMessage');
    };

    const openReactionModal = ({ message, reaction }) => {
        selectedReactionUsers.value = [
            { id: 1, name: 'Alice Johnson', avatar: 'https://i.pravatar.cc/150?img=20', reaction: reaction.emoji },
            { id: 2, name: 'Bob Smith', avatar: 'https://i.pravatar.cc/150?img=21', reaction: reaction.emoji }
        ];
        modals.value.reaction = true;
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
        console.log('Search in conversation');
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

    const createGroup = ({ name, description, type, members }) => {
        const newGroup = {
            id: Date.now(),
            type: 'group',
            name,
            avatar: null,
            lastMessage: 'Group created',
            lastMessageTime: 'Just now',
            unreadCount: 0,
            isOnline: false,
            isBlocked: false,
            created_by: 1,
            members: members.map(userId => {
                const user = availableUsers.value.find(u => u.id === userId);
                return { ...user, role: 'Member' };
            }),
            settings: {
                description,
                type,
                allow_members_to_send_messages: true,
                allow_members_to_add_remove_participants: false,
                allow_members_to_change_group_info: false,
                admins_must_approve_new_members: true
            }
        };

        conversations.value.unshift(newGroup);
        selectConversation(newGroup);
        closeModal('createGroup');
    };

    const openAddMemberModal = () => {
        modals.value.addMember = true;
    };

    const addMembersToGroup = (userIds) => {
        if (!activeConversation.value || activeConversation.value.type !== 'group') return;

        userIds.forEach((userId) => {
            const alreadyMember = activeConversation.value.members.some(
                member => member.id === userId
            );

            if (!alreadyMember) {
                const user = availableUsers.value.find(u => u.id === userId);

                if (user) {
                    activeConversation.value.members.push({
                        ...user,
                        role: 'Member',
                    });
                }
            }
        });
        closeModal('addMember');
    };


    const makeAdmin = (member) => {
        const m = activeConversation.value.members.find(mem => mem.id === member.id);
        if (m) m.role = 'Admin';
    };

    const removeAdmin = (member) => {
        const m = activeConversation.value.members.find(mem => mem.id === member.id);
        if (m) m.role = 'Member';
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

    const updateGroupSettings = (settings) => {
        if (activeConversation.value && activeConversation.value.type === 'group') {
            activeConversation.value.settings = { ...activeConversation.value.settings, ...settings };
            console.log('Group settings updated:', settings);
        }
    };

    const closeModal = (modalName) => {
        modals.value[modalName] = false;
        if (modalName === 'deleteMessage') messageToDelete.value = null;
        if (modalName === 'forwardMessage') messageToForward.value = null;
        if (modalName === 'messageDetails') selectedMessageDetails.value = null;
    };

    // Add reaction handler
    const handleAddReaction = ({ messageId, emoji }) => {
        const message = messages.value.find(m => m.id === messageId);
        if (!message) return;

        // Initialize reactions array if it doesn't exist
        if (!message.reactions) {
            message.reactions = [];
        }

        // Check if this emoji already exists in reactions
        const existingReaction = message.reactions.find(r => r.emoji === emoji);

        if (existingReaction) {
            // Check if current user already reacted with this emoji
            // In real app, you'd check against user ID
            // For now, we'll just increment the count
            existingReaction.count++;
        } else {
            // Add new reaction
            message.reactions.push({
                emoji: emoji,
                count: 1,
                users: [
                    {
                        id: 1, // Current user ID
                        name: 'You',
                        avatar: 'https://i.pravatar.cc/150?img=50'
                    }
                ]
            });
        }

        // In real app, make API call here:
        // await axios.post(`/api/messages/${messageId}/reaction`, { emoji });

        console.log('Reaction added:', { messageId, emoji });
    };

    const handleRemoveReaction = ({ messageId, emoji }) => {
        const message = messages.value.find(m => m.id === messageId);
        if (!message || !message.reactions) return;

        const reactionIndex = message.reactions.findIndex(r => r.emoji === emoji);
        if (reactionIndex === -1) return;

        const reaction = message.reactions[reactionIndex];

        if (reaction.count > 1) {
            reaction.count--;
        } else {
            // Remove reaction if count is 1
            message.reactions.splice(reactionIndex, 1);
        }

        // In real app, make API call here:
        // await axios.delete(`/api/messages/${messageId}/reaction`, { data: { emoji } });

        console.log('Reaction removed:', { messageId, emoji });
    };

    // ==================== VOICE MESSAGE HANDLER ====================
    const handleSendVoice = async (audioBlob, duration) => {
        if (!activeConversation.value) return;

        // Create a URL for the audio blob (for immediate playback)
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

        // Clear reply if exists
        if (replyingTo.value) {
            replyingTo.value = null;
        }

        // Scroll to bottom
        nextTick(() => {
            scrollToBottom();
        });

        // Simulate status updates (remove in production)
        setTimeout(() => {
            voiceMsg.status = 'delivered';
        }, 1000);

        setTimeout(() => {
            voiceMsg.status = 'seen';
            voiceMsg.seenBy = activeConversation.value.type === 'group'
                ? activeConversation.value.members.slice(0, 2).map(m => ({
                    id: m.id,
                    name: m.name,
                    avatar: m.avatar,
                    seenAt: 'Just now'
                }))
                : [{
                    id: 1,
                    name: activeConversation.value.name,
                    avatar: activeConversation.value.avatar,
                    seenAt: 'Just now'
                }];
        }, 2000);

        // Upload to server
        await uploadVoiceMessage(audioBlob, voiceMsg, activeConversation.value.id);
    };

    // Upload voice message to server
    const uploadVoiceMessage = async (audioBlob, message, conversationId) => {
        const formData = new FormData();
        formData.append('audio', audioBlob, 'voice-message.webm');
        formData.append('conversation_id', conversationId);
        formData.append('duration', message.file.duration);

        if (message.replyTo) {
            formData.append('reply_to_id', message.replyTo.id);
        }

        try {
            // Uncomment when backend is ready
            // const response = await axios.post('/api/v1/messages/voice', formData, {
            //   headers: { 'Content-Type': 'multipart/form-data' }
            // });

            // Update message with server response
            // const msg = messages.value.find(m => m.id === message.id);
            // if (msg && response.data) {
            //   msg.id = response.data.id;
            //   msg.file.url = response.data.file_url;
            //   msg.status = 'sent';
            // }

            console.log('Voice message uploaded:', {
                conversationId,
                duration: message.file.duration,
                size: audioBlob.size
            });

        } catch (error) {
            console.error('Failed to upload voice message:', error);

            // Mark message as failed
            const msg = messages.value.find(m => m.id === message.id);
            if (msg) {
                msg.status = 'failed';
            }

            // Optionally show error notification
            // toast.error('Failed to send voice message');
        }
    };

    // Listen for typing events from others
    const listenForTyping = (conversationId) => {
        // Example with Pusher/Echo
        // Echo.private(`conversation.${conversationId}`)
        //   .listenForWhisper('typing', (e) => {
        //     if (!typingUsers.value[conversationId]) {
        //       typingUsers.value[conversationId] = [];
        //     }
        //     
        //     const existingIndex = typingUsers.value[conversationId]
        //       .findIndex(u => u.id === e.user_id);
        //     
        //     if (existingIndex === -1) {
        //       typingUsers.value[conversationId].push({
        //         id: e.user_id,
        //         name: e.user_name,
        //         avatar: e.user_avatar
        //       });
        //     }
        //   })
        //   .listenForWhisper('stop-typing', (e) => {
        //     if (typingUsers.value[conversationId]) {
        //       typingUsers.value[conversationId] = typingUsers.value[conversationId]
        //         .filter(u => u.id !== e.user_id);
        //     }
        //   });

        // MOCK for testing (remove in production)
        setTimeout(() => {
            typingUsers.value[conversationId] = [
                { id: 1, name: 'John Doe', avatar: 'https://i.pravatar.cc/150?img=1' }
            ];

            setTimeout(() => {
                typingUsers.value[conversationId] = [];
            }, 3000);
        }, 2000);
    };



    const scrollToBottom = () => {
        if (messageContainer.value) {
            messageContainer.value.scrollTop = messageContainer.value.scrollHeight;
        }
    };

    onMounted(() => {
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
}