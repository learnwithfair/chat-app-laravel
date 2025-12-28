# Chat System - Professional Component Structure

## 📁 Folder Structure
resources/js/
├── Pages/
│   └── Chat/
│       └── Index.vue ✓ (Main container)
│
├── Components/
│   └── Chat/
│       ├── Sidebar/
│       │   ├── ConversationList.vue ✓
│       │   ├── ConversationItem.vue ✓
│       │   ├── OnlineUsers.vue ✓
│       │   └── SearchBar.vue ✓
│       │
│       ├── ChatArea/
│       │   ├── ChatHeader.vue ✓ (with search, call icons)
│       │   ├── MessageList.vue ✓
│       │   ├── MessageItem.vue ✓ (time inside, reactions right)
│       │   ├── MessageActions.vue ✓ (reply, edit, forward, delete)
│       │   ├── MessageStatus.vue ✓
│       │   ├── MessageReactions.vue ✓
│       │   ├── MessageInput.vue ✓
│       │   ├── ReplyPreview.vue ✓
│       │   └── EditPreview.vue ✓
│       │
│       ├── RightPanel/
│       │   ├── ConversationInfo.vue ✓
│       │   ├── GroupMembers.vue ✓
│       │   ├── GroupSettings.vue ✓ (WITH SWITCHES)
│       │   ├── SettingSwitch.vue ✓ (Reusable toggle)
│       │   ├── MediaGallery.vue ✓
│       │   ├── FilesList.vue ✓
│       │   └── LinksList.vue ✓
│       │
│       └── Modals/
│           ├── CreateGroupModal.vue ✓
│           ├── AddMemberModal.vue ✓
│           ├── ReactionModal.vue ✓
│           ├── SeenByModal.vue ✓
│           ├── MessageDetailsModal.vue ✓
│           ├── DeleteMessageModal.vue ✓
│           └── ForwardMessageModal.vue ✓ (NEW)
│
└── Composables/
    └── Chat/
        └── useChat.js ✓ (Main composable with all logic)