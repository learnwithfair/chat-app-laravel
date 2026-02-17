# Chat System Documentation

[![Youtube][youtube-shield]][youtube-url]
[![Facebook][facebook-shield]][facebook-url]
[![Instagram][instagram-shield]][instagram-url]
[![LinkedIn][linkedin-shield]][linkedin-url]

Thanks for visiting my GitHub account!

## Project Overview

A professional real-time chat application built with Laravel 12 and Vue 3, featuring private and group conversations, message reactions, file attachments, typing indicators, and comprehensive group management capabilities.

### Backend

|                                                    ||
| :------------------------------------------------: | :------------------------------------------------: |
|                 Backend Structure                  |  Frontend Structure          |           
| ![Backend Structure](/screenshort/backend-structure.png) |![Frontend Structure](/screenshort/frontend-structure.png) |


### Frontend APP

|                                                    ||
| :------------------------------------------------: | :------------------------------------------------: |
|                Frontend Preview-1                  |  Frontend Preview-2         |           
| ![Preview-1](/screenshort/chat-1.png) |![Preview-2](/screenshort/chat-2.png) |


## Table of Contents

1. [Project Overview](#project-overview)
2. [System Architecture](#system-architecture)
3. [Technology Stack](#technology-stack)
4. [Installation & Setup](#installation--setup)
5. [Backend Structure](#backend-structure)
6. [Frontend Structure](#frontend-structure)
7. [API Endpoints](#api-endpoints)
8. [Real-Time Events](#real-time-events)
9. [WebSocket Channels](#websocket-channels)
10. [Database Schema](#database-schema)
11. [Feature Implementation](#feature-implementation)
12. [Production Deployment](#production-deployment)
13. [Testing](#testing)
14. [Troubleshooting](#troubleshooting)

---


### Key Features

- Private and group conversations
- Real-time message delivery with Laravel Reverb
- Message reactions and replies
- File attachments (images, videos, documents, audio)
- Message status tracking (sent, delivered, seen)
- Typing indicators and online presence
- Group management (create, add/remove members, admin roles)
- Message pinning and forwarding
- User blocking and restrictions
- Media library per conversation
- Search functionality
- Mute notifications

---

## System Architecture

### Architecture Pattern

The application follows a layered architecture pattern:

```
Controller → Service → Repository → Action → Model
```

- **Controllers**: Handle HTTP requests and validation
- **Services**: Orchestrate business logic and coordinate multiple actions
- **Repositories**: Encapsulate database queries
- **Actions**: Small, reusable units of business logic
- **Models**: Eloquent models with relationships
- **Events**: Real-time broadcasting to frontend

### Directory Structure

```
app/
├── Actions/
│   └── Chat/
│       ├── CreateConversationAction.php
│       ├── SendMessageAction.php
│       └── MarkMessageReadAction.php
│
├── Services/
│   └── Chat/
│       └── ChatService.php
│
├── Repositories/
│   └── Chat/
│       ├── ConversationRepository.php
│       ├── MessageRepository.php
│       └── ParticipantRepository.php
│
├── Http/
│   └── Controllers/
│       └── Api/
│           └── V1/
│               └── Chat/
│                   ├── ConversationController.php
│                   ├── MessageController.php
│                   ├── GroupController.php
│                   ├── ReactionController.php
│                   └── UserBlockController.php
│
├── Events/
│   ├── ConversationEvent.php
│   └── MessageEvent.php
│
└── Models/
    ├── Conversation.php
    ├── Message.php
    ├── ConversationParticipant.php
    ├── MessageStatus.php
    ├── MessageReaction.php
    ├── MessageAttachment.php
    └── GroupSetting.php
```

---

## Technology Stack

### Backend

- **Framework**: Laravel 12
- **Authentication**: Laravel Sanctum (session-based)
- **Broadcasting**: Laravel Reverb (WebSocket server)
- **Database**: MySQL/PostgreSQL
- **Storage**: Local/S3 for file attachments

### Frontend

- **Framework**: Vue 3 (Composition API)
- **Router**: Inertia.js
- **Styling**: Tailwind CSS
- **UI Components**: shadcn/ui
- **Real-time**: Laravel Echo + Pusher JS
- **Build Tool**: Vite

---

## Installation & Setup

### Prerequisites

- PHP 8.2 or higher
- Composer 2.x
- Node.js 18.x or higher
- MySQL 8.0 or PostgreSQL 13+
- Redis (optional, for queue management)

### Step 1: Clone and Install Dependencies

```bash
git clone <repository-url>
cd chat-app-laravel

composer install
npm install
```

### Step 2: Environment Configuration

Create `.env` file from example:

```bash
cp .env.example .env
```

Configure the following in `.env`:

```env
APP_NAME="Chat Application"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=chat_app
DB_USERNAME=root
DB_PASSWORD=

BROADCAST_DRIVER=reverb

REVERB_APP_ID=app-id
REVERB_APP_KEY=app-key
REVERB_APP_SECRET=app-secret
REVERB_HOST=127.0.0.1
REVERB_SCHEME=http
REVERB_PORT=8080

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"

INVITE_URL=http://chat-app-laravel.test/api/v1/accept-invite
```

### Step 3: Database Setup

```bash
php artisan key:generate
php artisan migrate
php artisan storage:link
npm install axios
```

Optional: Seed test data

```bash
php artisan db:seed
```

### Step 4: Install Laravel Reverb

```bash
composer require laravel/reverb
php artisan reverb:install
```

### Step 5: Start Development Servers

Open three terminal windows:

**Terminal 1: Laravel Server**
```bash
php artisan serve
```

**Terminal 2: Reverb WebSocket Server**
```bash
php artisan reverb:start --debug
```

**Terminal 3: Vite Development Server**
```bash
npm run dev
```

### Step 6: Optional - Queue Worker (for background jobs)

```bash
php artisan queue:work
```

### Access Application

Navigate to `http://localhost:8000` in your browser.

---

## Backend Structure

### Controllers

All API controllers are located in `app/Http/Controllers/Api/V1/Chat/`:

- **ConversationController**: Manages conversation listing, creation, and deletion
- **MessageController**: Handles message CRUD operations, delivery, and read status
- **GroupController**: Group management, member operations, admin roles
- **ReactionController**: Message reactions (add/remove/list)
- **UserBlockController**: User blocking, restricting, and online status

### Services

**ChatService** (`app/Services/Chat/ChatService.php`):
- Orchestrates complex operations
- Coordinates multiple repositories and actions
- Handles transaction management

### Repositories

- **ConversationRepository**: Database queries for conversations
- **MessageRepository**: Message retrieval, filtering, pagination
- **ParticipantRepository**: Participant management queries

### Actions

Reusable business logic units:

- **CreateConversationAction**: Creates private or group conversations
- **SendMessageAction**: Processes and stores messages
- **MarkMessageReadAction**: Updates message read status

### Models & Relationships

**Conversation Model:**
```php
hasMany -> messages
hasMany -> participants
hasOne -> groupSettings
```

**Message Model:**
```php
belongsTo -> conversation
belongsTo -> sender (User)
belongsTo -> replyTo (Message)
hasMany -> attachments
hasMany -> reactions
belongsToMany -> readBy (User)
```

**ConversationParticipant Model:**
```php
belongsTo -> user
belongsTo -> conversation
```

---

## Frontend Structure

### Component Organization

```
resources/js/
├── Pages/
│   └── Chat/
│       └── Index.vue              // Main container page
│
├── Components/
│   └── Chat/
│       ├── Sidebar/
│       │   ├── ConversationList.vue
│       │   ├── ConversationItem.vue
│       │   ├── OnlineUsers.vue
│       │   └── SearchBar.vue
│       │
│       ├── ChatArea/
│       │   ├── ChatHeader.vue
│       │   ├── MessageList.vue
│       │   ├── MessageItem.vue
│       │   ├── MessageActions.vue
│       │   ├── MessageStatus.vue
│       │   ├── MessageReactions.vue
│       │   ├── MessageInput.vue
│       │   ├── ReplyPreview.vue
│       │   └── EditPreview.vue
│       │
│       ├── RightPanel/
│       │   ├── ConversationInfo.vue
│       │   ├── GroupMembers.vue
│       │   ├── GroupSettings.vue
│       │   ├── SettingSwitch.vue
│       │   ├── MediaGallery.vue
│       │   ├── FilesList.vue
│       │   └── LinksList.vue
│       │
│       └── Modals/
│           ├── CreateGroupModal.vue
│           ├── AddMemberModal.vue
│           ├── ReactionModal.vue
│           ├── SeenByModal.vue
│           ├── MessageDetailsModal.vue
│           ├── DeleteMessageModal.vue
│           └── ForwardMessageModal.vue
│
└── Composables/
    └── Chat/
        └── useChat.js             // Main composable with all logic
```

### Echo Configuration

**File**: `resources/js/echo.js`

```javascript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import axios from 'axios';

window.Pusher = Pusher;
window.axios = axios;
axios.defaults.withCredentials = true;

const wsHost = import.meta.env.VITE_REVERB_HOST ?? window.location.hostname;
const port = Number(import.meta.env.VITE_REVERB_PORT ?? (location.protocol === 'https:' ? 443 : 80));
const forceTLS = (import.meta.env.VITE_REVERB_SCHEME ?? location.protocol.replace(':', '')) === 'https';

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost,
    wsPort: port,
    wssPort: port,
    forceTLS,
    enabledTransports: ['ws', 'wss'],
});
```

Import once in `resources/js/app.js`:

```javascript
import './echo';
```

---

## API Endpoints

### Base URL

All API endpoints are prefixed with `/api/v1` and require authentication.

**Authentication Middleware**: `auth`, `verified`, `last_seen`

### Conversations

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/conversations` | List all conversations with pagination |
| POST | `/conversations` | Create a new group conversation |
| POST | `/conversations/private` | Start or retrieve private conversation |
| DELETE | `/conversations/{conversation}` | Delete conversation for current user |
| GET | `/conversations/{conversation}/media` | Get media library (images, videos, files, links) |

**Request Example (Create Group):**
```json
POST /api/v1/conversations
{
    "type": "group",
    "name": "Project Team",
    "participants": [2, 3, 4],
    "group": {
        "description": "Team discussion",
        "type": "private"
    }
}
```

**Response:**
```json
{
    "data": {
        "id": 15,
        "type": "group",
        "name": "Project Team",
        "created_at": "2026-02-17T10:30:00.000000Z",
        "participants": [...],
        "group_setting": {...}
    }
}
```

### Messages

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/messages` | Send a new message (text/attachments) |
| GET | `/messages/{conversation}/pined-messages` | Get all pinned messages |
| PUT | `/messages/{message}` | Edit message text |
| DELETE | `/messages/delete-for-me` | Delete messages for current user only |
| DELETE | `/messages/delete-for-everyone` | Unsend messages for all participants |
| GET | `/messages/seen/{conversation}` | Mark all messages as seen when opening conversation |
| GET | `/messages/delivered/{conversation}` | Mark messages as delivered |
| POST | `/messages/mark-seen` | Mark specific messages as seen |
| POST | `/messages/{message}/forward` | Forward message to other conversations |
| POST | `/messages/{message}/toggle-pin` | Pin or unpin a message |

**Request Example (Send Message):**
```json
POST /api/v1/messages
Content-Type: multipart/form-data

{
    "conversation_id": 5,
    "message": "Hello team!",
    "message_type": "text",
    "reply_to_message_id": 123,
    "attachments[0][path]": <file>
}
```

**Message Types:**
- `text`: Plain text message
- `image`: Single image attachment
- `video`: Single video attachment
- `audio`: Single audio/voice message
- `file`: Single document/file
- `multiple`: Multiple attachments

### Reactions

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/messages/{message}/reaction` | Toggle reaction on message |
| GET | `/messages/{message}/reaction` | Get all reactions for message |

**Request Example:**
```json
POST /api/v1/messages/123/reaction
{
    "reaction": "❤️"
}
```

**Response (Get Reactions):**
```json
{
    "data": {
        "grouped": {
            "❤️": {
                "count": 3,
                "users": [
                    {"user_id": 1, "name": "John", "avatar_path": "..."},
                    {"user_id": 2, "name": "Jane", "avatar_path": "..."}
                ]
            },
            "👍": {
                "count": 1,
                "users": [...]
            }
        }
    }
}
```

### Group Management

All group endpoints are prefixed with `/group/{conversation}`:

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/group/{conversation}/update` | Update group name, description, avatar, settings |
| POST | `/group/{conversation}/members/add` | Add members to group |
| POST | `/group/{conversation}/members/remove` | Remove members from group |
| GET | `/group/{conversation}/members` | List all group members with pagination |
| POST | `/group/{conversation}/admins/add` | Promote members to admin |
| POST | `/group/{conversation}/admins/remove` | Demote admins to member |
| POST | `/group/{conversation}/mute` | Mute or unmute group notifications |
| POST | `/group/{conversation}/leave` | Leave group |
| DELETE | `/group/{conversation}/delete-group` | Delete entire group (admin only) |
| POST | `/group/{conversation}/regenerate-invite` | Generate new invite link |
| GET | `/accept-invite/{token}` | Accept group invitation via link |

**Request Example (Update Group):**
```json
POST /api/v1/group/5/update
Content-Type: multipart/form-data

{
    "name": "Updated Team Name",
    "group[description]": "New description",
    "group[avatar]": <file>,
    "group[type]": "private",
    "group[can_members_send_messages]": 1,
    "group[can_members_add_participants]": 0
}
```

**Request Example (Mute Group):**
```json
POST /api/v1/group/5/mute
{
    "minutes": 60    // 0 = unmute, -1 = forever, or specific minutes
}
```

### User Management

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/online-users` | Get list of currently online users |
| GET | `/available-users` | Search users for starting conversation |
| POST | `/users/{user}/block-toggle` | Block or unblock user |
| POST | `/users/{user}/restrict-toggle` | Restrict or unrestrict user |

**Query Parameters (Available Users):**
```
GET /api/v1/available-users?search=john&page=1&per_page=20
```

---

## Real-Time Events

The application uses two main event classes for real-time communication.

### ConversationEvent

**Location**: `app/Events/ConversationEvent.php`

**Purpose**: Broadcasts conversation-level updates to users or group members.

**Action Types:**
- `added`: New conversation created for user
- `removed`: User removed from conversation
- `left`: User left the group
- `updated`: Conversation info updated (name, avatar, settings)
- `deleted`: Conversation deleted
- `blocked`: User blocked by another user
- `unblocked`: User unblocked
- `unmuted`: Conversation unmuted
- `member_added`: New member joined group
- `member_left`: Member left group
- `admin_added`: Member promoted to admin
- `admin_removed`: Admin demoted to member

**Broadcasting Logic:**
- If `targetUserId` is set: broadcasts to `user.{userId}` private channel
- If `targetUserId` is null: broadcasts to `conversation.{conversationId}` presence channel

**Event Structure:**
```php
new ConversationEvent(
    conversation: $conversation,
    action: 'member_added',
    targetUserId: 5,              // or null for group broadcast
    meta: ['new_member_name' => 'John Doe']
);
```

**Broadcast Payload:**
```json
{
    "action": "member_added",
    "conversation": {
        "id": 15,
        "name": "Team Chat",
        "type": "group",
        "avatar": "path/to/avatar.jpg",
        "meta": {
            "new_member_name": "John Doe",
            "last_message": {...},
            "unread_count": 5
        }
    }
}
```

### MessageEvent

**Location**: `app/Events/MessageEvent.php`

**Purpose**: Broadcasts message-level updates to conversation participants.

**Event Types:**
- `sent`: New message sent
- `updated`: Message edited
- `deleted_for_everyone`: Message unsent
- `reaction`: Reaction added/removed
- `delivered`: Message delivered to recipient
- `seen`: Message seen by recipient
- `pinned`: Message pinned
- `unpinned`: Message unpinned

**Broadcasting**: Always broadcasts to `conversation.{conversationId}` presence channel.

**Event Structure:**
```php
broadcast(new MessageEvent(
    type: 'sent',
    conversationId: 15,
    payload: [
        'id' => 456,
        'message' => 'Hello world',
        'sender' => [...],
        'attachments' => [...],
        'created_at' => '2026-02-17T10:30:00Z'
    ]
));
```

**Broadcast Payload:**
```json
{
    "type": "sent",
    "payload": {
        "id": 456,
        "message": "Hello world",
        "sender": {
            "id": 1,
            "name": "John Doe",
            "avatar_path": "..."
        },
        "attachments": [...],
        "created_at": "2026-02-17T10:30:00.000000Z"
    }
}
```

### Frontend Event Handling

**subscribeToGlobalPresence:**

```javascript
window.Echo.join('online')
            .here((users) => {
                console.log('Online users:', users);

            })
            .joining((user) => {
                console.log('User came online:', user);

            })
            .leaving((user) => {
                console.log('User went offline:', user);
            });
```


**Subscribe to User Channel:**
```javascript
window.Echo.private(`user.${userId}`)
    .listen('.ConversationEvent', (event) => {
        console.log('Conversation update:', event.action);
        handleConversationEvent(event);
    });
```

**Subscribe to Conversation Channel:**
```javascript
window.Echo.join(`conversation.${conversationId}`)
    .listen('.ConversationEvent', (event) => {
          console.log('ConversationEvent received (group channel):', event);
          handleConversationEvent(event);
    })
    .here((users) => {
        console.log('Users in conversation:', users);
    })
    .joining((user) => {
        console.log('User joined:', user);
    })
    .leaving((user) => {
        console.log('User left:', user);
    });
```


**Subscribe to Conversation:**
```javascript
window.Echo.join(`conversation.${conversationId}`)
    .listen('.MessageEvent', (event) => {
        console.log('Message event:', event.type);
        handleMessageEvent(event);
    })
    .listenForWhisper('typing', (e) => {
      console.log('Typing event:', e);
    })
    .here((users) => {
        console.log('Users currently in conversation:', users);
    })
    .joining((user) => {
       console.log('User joined:', user);
    })
    .leaving((user) => {
        console.log('User left:', user);
    });
```

---

## WebSocket Channels

**Location**: `routes/channels.php`

### Channel Definitions

**1. Global Online Presence**
```php
Broadcast::channel('online', function ($user) {
    return [
        'id' => $user->id,
        'name' => $user->name,
        'avatar_path' => $user->avatar_path,
    ];
});
```

**Usage**: Track all online users globally.

**2. User Private Channel**
```php
Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});
```

**Usage**: Personal notifications (conversation added, blocked, etc.)

**3. Conversation Presence Channel**
```php
Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    $conversation = Conversation::where('id', $conversationId)
        ->whereHas('participants', function ($q) use ($user) {
            $q->where('user_id', $user->id)->active();
        })
        ->first();

    if (!$conversation) {
        return false;
    }

    return [
        'id' => $user->id,
        'name' => $user->name,
        'avatar_path' => $user->avatar_path,
    ];
});
```

**Usage**: Real-time messages, reactions, typing indicators, online status within a conversation.

### Channel Authorization

All channels require authentication. Laravel automatically handles authorization checks via the channel callback functions.

---

## Database Schema

### Core Tables

**conversations**
- `id` (primary key)
- `name` (nullable for private chats)
- `type` (enum: 'private', 'group')
- `created_by` (foreign key to users)
- `timestamps`

**conversation_participants**
- `id` (primary key)
- `conversation_id` (foreign key)
- `user_id` (foreign key)
- `role` (enum: 'super_admin', 'admin', 'member')
- `is_muted` (boolean)
- `muted_until` (timestamp, nullable)
- `last_read_message_id` (foreign key, nullable)
- `is_active` (boolean, default true)
- `timestamps`

**messages**
- `id` (primary key)
- `conversation_id` (foreign key)
- `sender_id` (foreign key to users)
- `receiver_id` (foreign key to users, nullable)
- `reply_to_message_id` (foreign key, nullable)
- `forward_from_message_id` (foreign key, nullable)
- `message` (text)
- `message_type` (enum: 'text', 'image', 'video', 'audio', 'file', 'multiple', 'system')
- `is_pinned` (boolean, default false)
- `is_deleted_for_everyone` (boolean, default false)
- `timestamps`

**message_attachments**
- `id` (primary key)
- `message_id` (foreign key)
- `path` (string)
- `mime` (string)
- `type` (enum: 'image', 'video', 'audio', 'file')
- `size` (bigint)
- `original_name` (string)
- `timestamps`

**message_reactions**
- `id` (primary key)
- `message_id` (foreign key)
- `user_id` (foreign key)
- `reaction` (string)
- `timestamps`
- Unique constraint: `(message_id, user_id)`

**message_statuses**
- `id` (primary key)
- `message_id` (foreign key)
- `user_id` (foreign key)
- `status` (enum: 'sent', 'delivered', 'seen')
- `timestamps`

**message_deletions**
- `id` (primary key)
- `message_id` (foreign key)
- `user_id` (foreign key)
- `timestamps`

**group_settings**
- `conversation_id` (primary key, foreign key)
- `avatar` (string, nullable)
- `description` (text, nullable)
- `type` (enum: 'public', 'private')
- `invite_link` (string, nullable, unique)
- `can_members_send_messages` (boolean, default true)
- `can_members_add_participants` (boolean, default false)
- `can_members_edit_group_info` (boolean, default false)
- `admins_must_approve_new_members` (boolean, default false)
- `timestamps`

**user_blocks**
- `id` (primary key)
- `blocker_id` (foreign key to users)
- `blocked_id` (foreign key to users)
- `is_restricted` (boolean, default false)
- `timestamps`

---

## Response

### Conversations Response
```json

```

### Messages Response
```json

```


## Feature Implementation

### Message Status Flow

1. **Sent**: Message created in database
2. **Delivered**: Recipient fetches messages OR receives via WebSocket
3. **Seen**: Recipient opens conversation (calls `/messages/seen/{conversation}`)

### Message Deletion

**Delete for Me:**
- Record stored in `message_deletions` table
- Message hidden from user's view only
- Other participants still see the message

**Delete for Everyone (Unsend):**
- Sets `is_deleted_for_everyone = true`
- Changes `message_type` to 'system'
- Updates message text to "This message was deleted"
- All participants see the deletion

### Typing Indicator

Uses Laravel Echo whispers on presence channels:

**Frontend (Sender):**
```javascript
channel.whisper('typing', {
    userId: authUser.id,
    userName: authUser.name,
    isTyping: true
});
```

**Frontend (Receiver):**
```javascript
channel.listenForWhisper('typing', (e) => {
    if (e.isTyping) {
        showTypingIndicator(e.userName);
        setTimeout(() => hideTypingIndicator(), 3000);
    }
});
```

### Online Presence

**Join Global Presence:**
```javascript
window.Echo.join('online')
    .here((users) => {
        onlineUsers.value = users;
    })
    .joining((user) => {
        onlineUsers.value.push(user);
    })
    .leaving((user) => {
        onlineUsers.value = onlineUsers.value.filter(u => u.id !== user.id);
    });
```

**Per-Conversation Presence:**
- Each conversation has its own presence channel
- Shows who is currently viewing the conversation
- Updates online status indicators in real-time

### File Upload

**Maximum File Size**: Configure in `php.ini`:
```ini
upload_max_filesize = 50M
post_max_size = 50M
```

**Supported Types**:
- Images: jpg, jpeg, png, gif, webp
- Videos: mp4, webm, mov
- Audio: mp3, wav, ogg, webm
- Documents: pdf, doc, docx, xls, xlsx, txt

**Storage Path**: `storage/app/public/attachments/`

### Message Caching (Frontend)

The `useChat.js` composable implements intelligent caching:

```javascript
const messageCache = new Map();
const conversationCache = ref([]);
const CACHE_DURATION = 30000; // 30 seconds
```

**Benefits:**
- Reduces API calls
- Faster conversation switching
- Optimistic UI updates
- Seamless real-time integration

---

## Production Deployment

### Environment Configuration

**Production `.env` settings:**

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

BROADCAST_DRIVER=reverb

REVERB_APP_ID=production-app-id
REVERB_APP_KEY=production-app-key
REVERB_APP_SECRET=production-app-secret
REVERB_HOST=yourdomain.com
REVERB_SCHEME=https
REVERB_PORT=443

QUEUE_CONNECTION=redis
CACHE_DRIVER=redis
SESSION_DRIVER=redis
```

### Build Assets

```bash
npm run build
```

### Optimize Laravel

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

### Supervisor Configuration (Queue Worker)

**File**: `/etc/supervisor/conf.d/chat-worker.conf`

```ini
[program:chat-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/your/app/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/your/app/storage/logs/worker.log
```

Reload supervisor:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start chat-worker:*
```

### Reverb WebSocket Server (Production)

Run Reverb as a service using systemd:

**File**: `/etc/systemd/system/reverb.service`

```ini
[Unit]
Description=Laravel Reverb WebSocket Server
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/path/to/your/app
ExecStart=/usr/bin/php /path/to/your/app/artisan reverb:start
Restart=always
RestartSec=3

[Install]
WantedBy=multi-user.target
```

Enable and start:
```bash
sudo systemctl enable reverb
sudo systemctl start reverb
sudo systemctl status reverb
```

### Nginx Configuration

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name yourdomain.com;

    ssl_certificate /path/to/ssl/cert.pem;
    ssl_certificate_key /path/to/ssl/key.pem;

    root /path/to/your/app/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Reverb WebSocket Proxy
    location /reverb {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "Upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

### SSL Certificate

Use Let's Encrypt for free SSL:

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d yourdomain.com
```

---

## Testing

### Run Tests

```bash
php artisan test
```

### Feature Test Example

**File**: `tests/Feature/Chat/MessageTest.php`

```php
public function test_user_can_send_message()
{
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create();
    $conversation->participants()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)
        ->postJson('/api/v1/messages', [
            'conversation_id' => $conversation->id,
            'message' => 'Hello',
            'message_type' => 'text'
        ]);

    $response->assertStatus(201);
    $this->assertDatabaseHas('messages', [
        'conversation_id' => $conversation->id,
        'message' => 'Hello'
    ]);
}
```

### Manual Testing Checklist

- [ ] User registration and login
- [ ] Start private conversation
- [ ] Send text message
- [ ] Send message with attachment
- [ ] Reply to message
- [ ] Edit message
- [ ] Delete message (for me / for everyone)
- [ ] Add reaction
- [ ] Create group
- [ ] Add members to group
- [ ] Remove member from group
- [ ] Promote to admin
- [ ] Leave group
- [ ] Block user
- [ ] Mute conversation
- [ ] Pin message
- [ ] Forward message
- [ ] Real-time message delivery
- [ ] Typing indicator
- [ ] Online presence
- [ ] Message status (sent/delivered/seen)

---

## Troubleshooting

### WebSocket Connection Issues

**Problem**: Echo cannot connect to Reverb server

**Solution**:
1. Verify Reverb is running: `php artisan reverb:start --debug`
2. Check `.env` configuration matches between backend and frontend
3. Ensure firewall allows WebSocket port (8080)
4. Check browser console for connection errors

**Problem**: "Options object must provide a cluster"

**Solution**: Use `broadcaster: 'reverb'` (not `pusher`) in Echo configuration.

### Message Not Appearing in Real-Time

**Checklist**:
1. Verify user is subscribed to correct channel
2. Check browser console for event reception
3. Confirm backend is broadcasting events (check logs)
4. Verify `ShouldBroadcastNow` interface is used
5. Check channel authorization in `routes/channels.php`

### File Upload Failures

**Problem**: Files larger than 2MB fail

**Solution**: Increase upload limits in `php.ini`:
```ini
upload_max_filesize = 50M
post_max_size = 50M
max_execution_time = 300
```

Restart PHP-FPM:
```bash
sudo systemctl restart php8.2-fpm
```

### Duplicate Messages

**Problem**: Same message appears multiple times

**Solution**: Implement message deduplication using Set:

```javascript
const processedMessageIds = new Set();

if (processedMessageIds.has(message.id)) {
    return; // Skip duplicate
}
processedMessageIds.add(message.id);
```

### Queue Jobs Not Processing

**Problem**: Events not broadcasting, notifications not sent

**Solution**:
1. Start queue worker: `php artisan queue:work`
2. Check failed jobs: `php artisan queue:failed`
3. Retry failed jobs: `php artisan queue:retry all`
4. Configure supervisor for production (see deployment section)

### Database Connection Timeout

**Problem**: "SQLSTATE[HY000]: General error: 2006 MySQL server has gone away"

**Solution**: Increase MySQL timeouts in `config/database.php`:

```php
'mysql' => [
    'options' => [
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_TIMEOUT => 30,
    ],
],
```

### Storage Link Not Working

**Problem**: Uploaded files return 404

**Solution**:
```bash
php artisan storage:link
```

Verify symbolic link exists:
```bash
ls -la public/storage
```

---

## Additional Resources

### Official Documentation

- [Laravel Documentation](https://laravel.com/docs)
- [Vue 3 Documentation](https://vuejs.org/guide/introduction.html)
- [Inertia.js Documentation](https://inertiajs.com/)
- [Laravel Broadcasting](https://laravel.com/docs/broadcasting)
- [Laravel Reverb](https://reverb.laravel.com)

### Performance Optimization

**Database Indexing:**
```sql
CREATE INDEX idx_messages_conversation ON messages(conversation_id, created_at);
CREATE INDEX idx_message_status ON message_statuses(message_id, user_id);
CREATE INDEX idx_participants ON conversation_participants(conversation_id, user_id);
```

**Laravel Telescope** (Development):
```bash
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

**Redis Caching:**
```php
// Cache conversation list
Cache::remember("user.{$userId}.conversations", 300, function() {
    return Conversation::with('participants', 'lastMessage')->get();
});
```

---

## Security Best Practices

1. **Input Validation**: Always validate and sanitize user input
2. **Authorization**: Use Laravel policies for resource access control
3. **XSS Prevention**: Vue 3 automatically escapes content
4. **CSRF Protection**: Enabled by default for web routes
5. **SQL Injection**: Use Eloquent ORM and parameterized queries
6. **File Upload Validation**: Restrict file types and sizes
7. **Rate Limiting**: Implement API rate limiting
8. **Secure WebSocket**: Use WSS (SSL) in production
9. **Environment Variables**: Never commit `.env` file
10. **Regular Updates**: Keep dependencies up to date

---

## License

This project is licensed under the MIT License.

---

## Support

For issues and questions:
- Create an issue on GitHub repository
- Contact development team at support@example.com

---

**Last Updated**: February 17, 2026  
**Version**: 1.0.0


## Follow Me

[<img src='https://cdn.jsdelivr.net/npm/simple-icons@3.0.1/icons/github.svg' alt='github' height='40'>](https://github.com/learnwithfair) [<img src='https://cdn.jsdelivr.net/npm/simple-icons@3.0.1/icons/facebook.svg' alt='facebook' height='40'>](https://www.facebook.com/learnwithfair/) [<img src='https://cdn.jsdelivr.net/npm/simple-icons@3.0.1/icons/instagram.svg' alt='instagram' height='40'>](https://www.instagram.com/learnwithfair/) [<img src='https://cdn.jsdelivr.net/npm/simple-icons@3.0.1/icons/twitter.svg' alt='twitter' height='40'>](https://www.twiter.com/learnwithfair/) [<img src='https://cdn.jsdelivr.net/npm/simple-icons@3.0.1/icons/youtube.svg' alt='YouTube' height='40'>](https://www.youtube.com/@learnwithfair)

 <!-- MARKDOWN LINKS & IMAGES  -->

[youtube-shield]: https://img.shields.io/badge/-Youtube-black.svg?style=flat-square&logo=youtube&color=555&logoColor=white
[youtube-url]: https://youtube.com/@learnwithfair
[facebook-shield]: https://img.shields.io/badge/-Facebook-black.svg?style=flat-square&logo=facebook&color=555&logoColor=white
[facebook-url]: https://facebook.com/learnwithfair
[instagram-shield]: https://img.shields.io/badge/-Instagram-black.svg?style=flat-square&logo=instagram&color=555&logoColor=white
[instagram-url]: https://instagram.com/learnwithfair
[linkedin-shield]: https://img.shields.io/badge/-LinkedIn-black.svg?style=flat-square&logo=linkedin&colorB=555
[linkedin-url]: https://linkedin.com/company/learnwithfair

#learnwithfair #rahtulrabbi #rahatul-rabbi #learn-with-fair