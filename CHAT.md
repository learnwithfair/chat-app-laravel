# Laravel Chat System Documentation

## Table of Contents

1. [Overview](#overview)
2. [Packages & Dependencies](#packages--dependencies)
3. [Environment Setup](#environment-setup)
4. [Folder Structure](#folder-structure)
5. [Database Schema](#database-schema)
6. [Models & Relationships](#models--relationships)
7. [Repository-Service-Action Pattern](#repository-service-action-pattern)
8. [API Routes](#api-routes)
9. [Messages & Features](#messages--features)
10. [Reactions](#reactions)
11. [Group Management](#group-management)
12. [Events & Broadcasting](#events--broadcasting)
13. [Real-Time Features](#real-time-features)
14. [Workflow](#workflow)
15. [Notes](#notes)

---

## Overview

This Chat System is a professional, real-time messaging platform for **private and group conversations** with the following features:

- Private & group chats
- Message attachments & replies
- Message status: `sent`, `delivered`, `seen`
- Message deletion (for self / for everyone)
- Reactions per message
- Typing indicators & online presence
- Group management (add/remove members, leave, mute, update info)
- Real-time updates using Laravel Broadcasting

---

## Packages & Dependencies

- `laravel/framework` >= 9.x
- `laravel/sanctum` for API authentication
- `pusher/pusher-php-server` or `beyondcode/laravel-websockets` for real-time broadcasting
- `laravel/echo` on frontend
- `guzzlehttp/guzzle` for HTTP requests if needed

---

## Environment Setup

In `.env`, configure:

```env
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=your-app-id
PUSHER_APP_KEY=your-app-key
PUSHER_APP_SECRET=your-app-secret
PUSHER_HOST=127.0.0.1
PUSHER_PORT=6001
PUSHER_SCHEME=http
PUSHER_APP_CLUSTER=mt1
```

* Run migrations: `php artisan migrate`
* Optional: Seed data for testing participants
* Run queue workers for broadcasting events: `php artisan queue:work`

---

## Folder Structure

```
app/
├─ Actions/Chat/             # Action classes for business logic
├─ Events/                   # Real-time events
├─ Http/Controllers/Api/Chat # API controllers
├─ Models/                   # Eloquent models
├─ Repositories/Chat/        # Database queries encapsulated
├─ Services/Chat/            # Service layer for orchestration
└─ Rules/Chat/               # Optional: custom validation rules
```

**Pattern:** `Controller -> Service -> Repository -> Action -> Model`

* Controllers: handle HTTP requests and validation
* Services: orchestrate multiple actions and repository calls
* Repositories: handle database queries
* Actions: small reusable logic units
* Events: broadcasting to frontend
* Resources: format API responses

---

## Database Schema

### Conversations

* `id`, `type` (`private` / `group`), `name`, `created_at`, `updated_at`
* Relationships: participants, messages, group settings

### Conversation Participants

* `id`, `conversation_id`, `user_id`, `role` (`admin` / `member` / `super_admin`)
* `is_muted`, `last_read_message_id`, timestamps

### Messages

* `id`, `sender_id`, `receiver_id` (nullable), `conversation_id`
* `reply_to_message_id` (nullable)
* `message`, `message_type`
* `is_deleted_for_everyone`, timestamps
* Relationships: attachments, reactions, read_by

### Message Attachments

* `id`, `message_id`, `path`, `type`, `size`

### Message Reactions

* `id`, `message_id`, `user_id`, `type`

### Message Statuses

* `id`, `message_id`, `user_id`, `status` (`sent`, `delivered`, `seen`)

### Message Deletions

* `id`, `message_id`, `user_id` (for "delete for me")

### Group Settings

* `conversation_id`, `avatar`, `description`, `type` (`public` / `private`)
* Permissions: send messages, add/remove participants, change group info
* `admins_must_approve_new_members`, timestamps

---

## Models & Relationships

* **Conversation**

  * hasMany `messages`
  * hasMany `participants`
  * hasOne `groupSettings`
* **Message**

  * belongsTo `conversation`
  * belongsTo `sender` (User)
  * belongsTo `replyTo` (Message)
  * hasMany `attachments`
  * hasMany `reactions`
  * belongsToMany `read_by` (User)
* **ConversationParticipant**

  * belongsTo `user`
  * belongsTo `conversation`

---

## Repository-Service-Action Pattern

* **Repository**: low-level database queries
  Example: `ConversationRepository`, `MessageRepository`
* **Service**: orchestrates repositories and actions
  Example: `ChatService`
* **Action**: reusable logic units
  Example: `CreateConversationAction`, `MarkMessageReadAction`

---

## API Routes

### Conversations

| Method | Route                              | Description                          |
| ------ | ---------------------------------- | ------------------------------------ |
| GET    | `/v1/conversations`                | List conversations with unread count |
| POST   | `/v1/conversations/group`          | Create group conversation            |
| DELETE | `/v1/conversations/{conversation}` | Delete conversation (self)           |

### Messages

| Method | Route                              | Description                                   |
| ------ | ---------------------------------- | --------------------------------------------- |
| GET    | `/v1/messages/{conversation}`      | Get conversation messages                     |
| POST   | `/v1/messages`                     | Send message (supports attachments & replies) |
| DELETE | `/v1/messages/delete-for-me`       | Delete messages for self                      |
| POST   | `/v1/messages/delete-for-everyone` | Delete messages for all participants          |
| POST   | `/v1/messages/seen/{conversation}` | Mark messages as read                         |

### Reactions

| Method | Route                             | Description     |
| ------ | --------------------------------- | --------------- |
| POST   | `/v1/messages/{message}/reaction` | Toggle reaction |

### Group Management

| Method | Route                                     | Description         |
| ------ | ----------------------------------------- | ------------------- |
| POST   | `/v1/group/{conversation}/members/add`    | Add members         |
| POST   | `/v1/group/{conversation}/members/remove` | Remove members      |
| POST   | `/v1/group/{conversation}/mute`           | Mute / unmute group |
| POST   | `/v1/group/{conversation}/leave`          | Leave group         |
| POST   | `/v1/group/{conversation}/update`         | Update group info   |
| DELETE | `/v1/group/{conversation}`                | Delete group        |

---

## Messages & Features

* Supports **multiple attachments** per message
* Supports **replying** to a message
* **Edit & delete** messages with status tracking
* **Soft deletion** ensures history for other participants
* **Message status**: sent, delivered, seen
* **Unread message count** tracked via `last_read_message_id` per participant

---

## Reactions

* Users can react to messages with types (`like`, `love`, `haha`, etc.)
* Reactions are **grouped and counted**
* Real-time updates broadcast via `MessageReactionEvent`

---

## Group Management

* Add/Remove members, Leave group
* Mute/unmute group
* Update group info and permissions
* Automatic system messages for all member actions
* Real-time notifications broadcast via `ConversationEvent`

---

## Events & Broadcasting

### Key Events

1. `MessageEvent` – messages sent, edited, deleted, or reacted
2. `ConversationEvent` – conversation updates (added, removed, left, updated, deleted)
3. `UserStatusEvent` – typing & online/offline status

### Channels

* `conversation.{conversationId}` – messages, reactions
* `group-chat.{conversationId}` – group messages
* `user.{userId}` – conversation updates
* `chat.users` – presence for typing/online status

---

## Real-Time Features

* Typing indicator (`UserTypingEvent`)
* Online/offline indicator (`UserStatusEvent`)
* Reactions broadcast in real-time
* Conversation list updates for unread counts

---

## Workflow

1. **Private conversation**:

   * If exists, send message
   * If not, create conversation + send first message
2. **Group conversation**:

   * Validate participants
   * Send messages to all active members
3. **Adding/Removing members**:

   * Update `conversation_participants`
   * Create system message
   * Broadcast `ConversationEvent`
4. **Deleting messages**:

   * For self: record in `message_deletions`
   * For everyone: update `is_deleted_for_everyone` and set message text to `"Unsent"`
5. **Marking as read**:

   * Update `last_read_message_id` per participant
   * Broadcast `ConversationEvent` for unread count

---

## Notes

* Follows **Repository → Service → Action** pattern for maintainability
* Soft deletes preserve message history for other participants
* `last_read_message_id` per participant ensures accurate unread counts
* Group conversations have `role`-based permissions
* Events are **generic and reusable** to avoid duplicate code

---



