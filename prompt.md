## Web Chat System – Feature Summary (For UI/UX Design)

### 1. Overall Architecture

* Web version uses **Laravel (backend)** + **Vue 3 (frontend)** via **Inertia.js**
* Tailwind css
* Same **Service, Repository, Action, Event, Model** layer shared with mobile API
* Web has **separate routes & controllers**, but **business logic is reused**
* Real-time features powered by **Laravel Broadcasting (Pusher / WebSockets)**

---

## 2. Chat Layout (Messenger-Style)

### A. Main Layout

* Two-column layout:

  * **Left Sidebar:** Conversation List
  * **Right Panel:** Active Conversation (Messages)
  * Right side media, group seeting, group member etc shown functionality.
* Responsive layout:

  * Desktop: Sidebar + Chat Panel
  * Mobile web: Toggle between list and messages

---

## 3. Conversation List (Sidebar)

### Features:

* Show **all active conversations** of logged-in user
* Ordered by **last message time** (latest first)
* Each conversation item includes:

  * Conversation name (group name or user name)
  * Last message preview
  * Last message time
  * Unread message count
  * Online/offline indicator (only for participants)
  * Blocked indicator (disable message input if blocked)

### Conversation Types:

* **Private conversation**

  * Show other participant’s name & avatar
* **Group conversation**

  * Show group name & group avatar
  * Show up to 3 member avatars

### Search:

* Search input at top of sidebar
* Behaviour:

  * Private chat → search by **conversation name OR other user name**
  * Group chat → search by **group name only**

---

## 4. Online Status System

* Online indicator shown **only for users who have conversations with me**
* Powered by **presence channels per conversation**
* Sidebar updates online status in real-time
* No global “all users online” list

---

## 5. Message Area (Conversation View)

### Message List:

* Messages displayed chronologically
* Message bubble aligned:

  * Right → my messages
  * Left → others’ messages
* Support for:

  * Text messages
  * File attachments (image, video, document)
  * Reply-to message preview
  * Deleted message placeholder

---

## 6. Message Status System

Each message has:

* **Sent** → message created
* **Delivered** → receiver fetched messages
* **Seen** → receiver opened conversation

UI Indicators:

* Single tick → sent
* Double tick → delivered
* Colored double tick → seen

---

## 7. Reactions System

* Users can react to messages (❤️ 😂 👍 😡 😢 etc.)
* One reaction per user per message
* UI shows:

  * Grouped reactions with total count
  * Click to open reaction details
* Reaction detail modal shows:

  * User avatar
  * User name
  * Reaction type

---

## 8. Message Search (Inside Conversation)

* Search messages within active conversation
* Filters messages by text
* Highlights matched text (UI-only)
* Keeps chronological order

---

## 9. Group Chat Features

### Group Management:

* Create / update group
* Add members
* Remove members
* Promote to admin
* Remove admin role
* Leave group

### Group Roles:

* Super Admin
* Admin
* Member

### Group Controls:

* Mute/unmute group
* Only admins can manage members & settings

---

## 10. Block & Restrict System

### Block:

* Blocked users:

  * Cannot send messages to blocker
  * Conversation input disabled
  * Block status visible in UI

### Restrict:

* Restricted messages:

  * Sender can send
  * Receiver sees message as restricted
  * UI may hide notifications or blur messages

### Toggle System:

* Block / unblock
* Restrict / unrestrict
* Same button toggles state

---

## 11. Real-Time Events (Web)

* New message received
* Message edited / deleted
* Reaction added / removed
* User blocked / unblocked
* Online / offline
* Typing indicator (optional)

---

## 12. Static Data Phase (For Design)

During UI design phase:

* Use static JSON data for:

  * Conversations
  * Messages
  * Users
  * Reactions
  * Online status
* UI should be designed **exactly how final real data will appear**
* After design approval, replace static data with Inertia props

---

## 13. Folder Structure (Web)

### Controllers

```
app/Http/Controllers/Web/V1/Chat
├─ ChatController.php
├─ ConversationController.php
├─ MessageController.php
├─ GroupController.php
├─ ReactionController.php
├─ UserBlockController.php
```

### Frontend Pages

```
resources/js/Pages/Chat
├─ Index.vue            // Main chat layout
├─ Sidebar.vue          // Conversation list
├─ Conversation.vue     // Message area
├─ MessageItem.vue
├─ MessageInput.vue
├─ ReactionModal.vue
├─ GroupSettings.vue
```

---

## 14. Design Goal

* UI inspired by **Messenger / WhatsApp Web**
* Clean, minimal, fast
* Real-time feel
* Consistent UX for mobile & web

---
