# Chat System - API Reference & Postman Test Guide

## Base Configuration

```
Base URL      : http://localhost:8000/api/v1
Content-Type  : application/json
Accept        : application/json
Auth Method   : Session Cookie (Laravel Sanctum / Session)
```

### Postman Environment Variables

Set these in your Postman environment:

| Variable | Value |
|---|---|
| `base_url` | `http://localhost:8000/api/v1` |
| `conversation_id` | `1` |
| `message_id` | `1` |
| `user_id` | `2` |
| `group_id` | `1` |

### Authentication Note

This application uses session-based authentication via Laravel Inertia. Before calling any API endpoint, ensure:

1. You are logged in via the browser or have a valid session cookie.
2. Enable **"Send cookies"** in Postman settings.
3. Set `axios.defaults.withCredentials = true` (already configured in `echo.js`).

For API-only testing with Sanctum token, add header:
```
Authorization: Bearer {your_token}
```

---

## 1. Conversation Endpoints

---

### 1.1 List All Conversations

Retrieve all conversations for the authenticated user with unread counts and last message preview.

**Request**
```
GET {{base_url}}/conversations
```

**Query Parameters**
| Parameter | Type | Required | Description |
|---|---|---|---|
| `page` | integer | No | Page number (default: 1) |
| `per_page` | integer | No | Items per page (default: 30) |
| `query` | string | No | Search keyword |

**Sample Request**
```
GET http://localhost:8000/api/v1/conversations?page=1&per_page=30
```

**Sample Response (200)**
```json
{
    "data": [
        {
            "id": 1,
            "type": "private",
            "name": null,
            "receiver": {
                "id": 2,
                "name": "Jane Smith",
                "email": "jane@example.com",
                "avatar_path": "storage/avatars/user2.jpg",
                "is_online": true,
                "last_seen": "2026-02-17 10:30:00"
            },
            "last_message": {
                "id": 45,
                "message": "Hey, how are you?",
                "message_type": "text",
                "sender": {
                    "id": 2,
                    "name": "Jane Smith",
                    "avatar_path": "storage/avatars/user2.jpg"
                },
                "attachments": [],
                "created_at": "2026-02-17T10:25:00.000000Z"
            },
            "unread_count": 3,
            "is_muted": false,
            "is_blocked": false,
            "blocked": {
                "by_me": false,
                "by_them": false
            },
            "can_send_message": true,
            "role": "member",
            "is_admin": false,
            "created_at": "2026-02-10T08:00:00.000000Z"
        },
        {
            "id": 2,
            "type": "group",
            "name": "Project Team",
            "receiver": null,
            "group_setting": {
                "avatar": "storage/groups/team.jpg",
                "description": "Main project discussion",
                "type": "private",
                "invite_link": "abc123xyz",
                "can_members_send_messages": true,
                "can_members_add_participants": false,
                "admins_must_approve_new_members": false
            },
            "last_message": {
                "id": 46,
                "message": "Meeting at 3pm",
                "message_type": "text",
                "sender": {
                    "id": 3,
                    "name": "Bob Jones",
                    "avatar_path": null
                },
                "attachments": [],
                "created_at": "2026-02-17T10:28:00.000000Z"
            },
            "participants": [
                {"id": 1, "name": "John Doe"},
                {"id": 2, "name": "Jane Smith"},
                {"id": 3, "name": "Bob Jones"}
            ],
            "unread_count": 0,
            "is_muted": false,
            "is_blocked": false,
            "can_send_message": true,
            "role": "admin",
            "is_admin": true,
            "invite_link": "http://localhost:8000/api/v1/accept-invite/abc123xyz",
            "created_at": "2026-02-05T09:00:00.000000Z"
        }
    ],
    "meta": {
        "current_page": 1,
        "last_page": 1,
        "per_page": 30,
        "total": 2
    }
}
```

---

### 1.2 Create Group Conversation

Create a new group conversation.

**Request**
```
POST {{base_url}}/conversations
Content-Type: application/json
```

**Body**
```json
{
    "type": "group",
    "name": "Development Team",
    "participants": [2, 3, 4],
    "group": {
        "description": "Backend and frontend team",
        "type": "private"
    }
}
```

**Sample Response (201)**
```json
{
    "data": {
        "id": 5,
        "type": "group",
        "name": "Development Team",
        "created_by": 1,
        "participants": [
            {"id": 1, "name": "John Doe", "role": "super_admin"},
            {"id": 2, "name": "Jane Smith", "role": "member"},
            {"id": 3, "name": "Bob Jones", "role": "member"},
            {"id": 4, "name": "Alice Brown", "role": "member"}
        ],
        "group_setting": {
            "avatar": null,
            "description": "Backend and frontend team",
            "type": "private",
            "can_members_send_messages": true,
            "can_members_add_participants": false,
            "admins_must_approve_new_members": false
        },
        "is_admin": true,
        "role": "super_admin",
        "can_send_message": true,
        "created_at": "2026-02-17T10:30:00.000000Z"
    }
}
```

---

### 1.3 Start Private Conversation

Start a new private conversation with a user or retrieve an existing one.

**Request**
```
POST {{base_url}}/conversations/private
Content-Type: application/json
```

**Body**
```json
{
    "receiver_id": 3
}
```

**Sample Response (200)**
```json
{
    "data": {
        "id": 8,
        "type": "private",
        "name": null,
        "receiver": {
            "id": 3,
            "name": "Bob Jones",
            "email": "bob@example.com",
            "avatar_path": null,
            "is_online": false,
            "last_seen": "2026-02-17T09:00:00.000000Z"
        },
        "last_message": null,
        "unread_count": 0,
        "is_muted": false,
        "is_blocked": false,
        "blocked": {
            "by_me": false,
            "by_them": false
        },
        "can_send_message": true,
        "role": "member",
        "is_admin": false,
        "created_at": "2026-02-17T10:31:00.000000Z"
    }
}
```

---

### 1.4 Delete Conversation

Delete a conversation for the current user only.

**Request**
```
DELETE {{base_url}}/conversations/1
```

**Sample Response (200)**
```json
{
    "message": "Conversation deleted successfully."
}
```

---

### 1.5 Get Media Library

Retrieve all media (images, videos, audio, files, links) shared in a conversation.

**Request**
```
GET {{base_url}}/conversations/1/media
```

**Sample Response (200)**
```json
{
    "data": {
        "media": [
            {
                "id": 10,
                "type": "image",
                "path": "http://localhost:8000/storage/attachments/img1.jpg",
                "name": "photo.jpg",
                "size": 204800,
                "created_at": "2026-02-15T10:00:00.000000Z"
            },
            {
                "id": 11,
                "type": "video",
                "path": "http://localhost:8000/storage/attachments/video1.mp4",
                "name": "clip.mp4",
                "size": 5242880,
                "created_at": "2026-02-16T11:00:00.000000Z"
            }
        ],
        "audio": [
            {
                "id": 12,
                "type": "audio",
                "path": "http://localhost:8000/storage/attachments/voice.ogg",
                "name": "voice-message.ogg",
                "size": 102400,
                "created_at": "2026-02-16T12:00:00.000000Z"
            }
        ],
        "files": [
            {
                "id": 13,
                "type": "file",
                "path": "http://localhost:8000/storage/attachments/doc.pdf",
                "name": "report.pdf",
                "size": 1048576,
                "created_at": "2026-02-14T09:00:00.000000Z"
            }
        ],
        "links": [
            {
                "message_id": 40,
                "url": "https://github.com/example/repo",
                "created_at": "2026-02-13T08:00:00.000000Z"
            }
        ]
    }
}
```

---

## 2. Message Endpoints

---

### 2.1 Send Text Message

Send a plain text message to a conversation.

**Request**
```
POST {{base_url}}/messages
Content-Type: multipart/form-data
```

**Body (form-data)**
| Key | Value | Type |
|---|---|---|
| `conversation_id` | `1` | Text |
| `message` | `Hello, how is everyone doing today?` | Text |
| `message_type` | `text` | Text |

**Sample Response (201)**
```json
{
    "data": {
        "id": 50,
        "conversation_id": 1,
        "message": "Hello, how is everyone doing today?",
        "message_type": "text",
        "is_pinned": false,
        "is_deleted_for_everyone": false,
        "is_mine": true,
        "sender": {
            "id": 1,
            "name": "John Doe",
            "avatar_path": null
        },
        "reply": null,
        "forward": null,
        "attachments": [],
        "reactions": {
            "reactions": {}
        },
        "statuses": [],
        "created_at": "2026-02-17T10:35:00.000000Z"
    }
}
```

---

### 2.2 Send Message with Reply

Reply to an existing message.

**Request**
```
POST {{base_url}}/messages
Content-Type: multipart/form-data
```

**Body (form-data)**
| Key | Value | Type |
|---|---|---|
| `conversation_id` | `1` | Text |
| `message` | `Sounds great! See you then.` | Text |
| `message_type` | `text` | Text |
| `reply_to_message_id` | `46` | Text |

**Sample Response (201)**
```json
{
    "data": {
        "id": 51,
        "conversation_id": 1,
        "message": "Sounds great! See you then.",
        "message_type": "text",
        "is_mine": true,
        "sender": {
            "id": 1,
            "name": "John Doe",
            "avatar_path": null
        },
        "reply": {
            "id": 46,
            "message": "Meeting at 3pm",
            "sender": {
                "id": 3,
                "name": "Bob Jones",
                "avatar_path": null
            }
        },
        "attachments": [],
        "created_at": "2026-02-17T10:36:00.000000Z"
    }
}
```

---

### 2.3 Send Message with Single Image

**Request**
```
POST {{base_url}}/messages
Content-Type: multipart/form-data
```

**Body (form-data)**
| Key | Value | Type |
|---|---|---|
| `conversation_id` | `1` | Text |
| `message` | `Check this out!` | Text |
| `message_type` | `image` | Text |
| `attachments[0][path]` | (select image file) | File |

**Sample Response (201)**
```json
{
    "data": {
        "id": 52,
        "conversation_id": 1,
        "message": "Check this out!",
        "message_type": "image",
        "is_mine": true,
        "sender": {
            "id": 1,
            "name": "John Doe",
            "avatar_path": null
        },
        "attachments": [
            {
                "id": 15,
                "type": "image",
                "path": "http://localhost:8000/storage/attachments/1234567890.jpg",
                "name": "photo.jpg",
                "size": 204800
            }
        ],
        "created_at": "2026-02-17T10:37:00.000000Z"
    }
}
```

---

### 2.4 Send Message with Multiple Attachments

**Request**
```
POST {{base_url}}/messages
Content-Type: multipart/form-data
```

**Body (form-data)**
| Key | Value | Type |
|---|---|---|
| `conversation_id` | `1` | Text |
| `message` | `Here are the project files` | Text |
| `message_type` | `multiple` | Text |
| `attachments[0][path]` | (select file 1) | File |
| `attachments[1][path]` | (select file 2) | File |

---

### 2.5 Get Conversation Messages

Retrieve paginated messages for a specific conversation.

**Request**
```
GET {{base_url}}/messages/1?page=1&per_page=20
```

**Query Parameters**
| Parameter | Type | Required | Description |
|---|---|---|---|
| `page` | integer | No | Page number |
| `per_page` | integer | No | Messages per page |

**Sample Response (200)**
```json
{
    "data": [
        {
            "id": 50,
            "message": "Hello, how is everyone doing today?",
            "message_type": "text",
            "is_pinned": false,
            "is_deleted_for_everyone": false,
            "is_mine": false,
            "sender": {
                "id": 2,
                "name": "Jane Smith",
                "avatar_path": "storage/avatars/user2.jpg"
            },
            "reply": null,
            "forward": null,
            "attachments": [],
            "reactions": {
                "reactions": {
                    "❤️": 2,
                    "👍": 1
                }
            },
            "statuses": [
                {
                    "user_id": 1,
                    "status": "seen",
                    "name": "John Doe",
                    "avatar_path": null,
                    "created_at": "2026-02-17T10:36:00.000000Z"
                }
            ],
            "created_at": "2026-02-17T10:35:00.000000Z"
        }
    ],
    "meta": {
        "current_page": 1,
        "last_page": 3,
        "per_page": 20,
        "total": 50
    }
}
```

---

### 2.6 Edit Message

Update the text content of an existing message.

**Request**
```
PUT {{base_url}}/messages/50
Content-Type: application/json
```

**Body**
```json
{
    "message": "Hello everyone! How is the project going?"
}
```

**Sample Response (200)**
```json
{
    "data": {
        "id": 50,
        "conversation_id": 1,
        "message": "Hello everyone! How is the project going?",
        "is_edited": true,
        "updated_at": "2026-02-17T10:40:00.000000Z"
    }
}
```

---

### 2.7 Delete Messages for Me

Remove messages from the current user's view only. Other participants still see the messages.

**Request**
```
DELETE {{base_url}}/messages/delete-for-me
Content-Type: application/json
```

**Body**
```json
{
    "message_ids": [48, 49, 50]
}
```

**Sample Response (200)**
```json
{
    "message": "Messages deleted for you."
}
```

---

### 2.8 Delete Messages for Everyone (Unsend)

Unsend messages for all participants. Message text is replaced with "This message was deleted".

**Request**
```
DELETE {{base_url}}/messages/delete-for-everyone
Content-Type: application/json
```

**Body**
```json
{
    "message_ids": [50]
}
```

**Sample Response (200)**
```json
{
    "message": "Messages unsent for everyone."
}
```

---

### 2.9 Mark Messages as Seen (Open Conversation)

Mark all unread messages as seen when a user opens a conversation.

**Request**
```
GET {{base_url}}/messages/seen/1
```

**Sample Response (200)**
```json
{
    "message": "Messages marked as seen."
}
```

---

### 2.10 Mark Messages as Delivered

Mark messages as delivered for a conversation.

**Request**
```
GET {{base_url}}/messages/delivered/1
```

**Sample Response (200)**
```json
{
    "message": "Messages marked as delivered."
}
```

---

### 2.11 Mark Specific Messages as Seen

**Request**
```
POST {{base_url}}/messages/mark-seen
Content-Type: application/json
```

**Body**
```json
{
    "message_ids": [48, 49, 50],
    "conversation_id": 1
}
```

**Sample Response (200)**
```json
{
    "message": "Messages marked as seen."
}
```

---

### 2.12 Forward Message

Forward a message to one or multiple conversations.

**Request**
```
POST {{base_url}}/messages/50/forward
Content-Type: application/json
```

**Body**
```json
{
    "conversation_ids": [2, 3, 5]
}
```

**Sample Response (201)**
```json
{
    "data": [
        {
            "id": 55,
            "conversation_id": 2,
            "message": "Hello everyone! How is the project going?",
            "message_type": "text",
            "is_mine": true,
            "forward": {
                "id": 50,
                "message": "Hello everyone! How is the project going?",
                "sender": {
                    "id": 2,
                    "name": "Jane Smith"
                }
            },
            "created_at": "2026-02-17T10:45:00.000000Z"
        },
        {
            "id": 56,
            "conversation_id": 3,
            "message": "Hello everyone! How is the project going?",
            "message_type": "text",
            "is_mine": true,
            "forward": {
                "id": 50,
                "message": "Hello everyone! How is the project going?",
                "sender": {
                    "id": 2,
                    "name": "Jane Smith"
                }
            },
            "created_at": "2026-02-17T10:45:00.000000Z"
        }
    ]
}
```

---

### 2.13 Toggle Pin Message

Pin or unpin a message in a conversation.

**Request**
```
POST {{base_url}}/messages/50/toggle-pin
```

**Sample Response (200) - Pinned**
```json
{
    "data": {
        "id": 50,
        "is_pinned": true,
        "message": "Message pinned successfully."
    }
}
```

**Sample Response (200) - Unpinned**
```json
{
    "data": {
        "id": 50,
        "is_pinned": false,
        "message": "Message unpinned successfully."
    }
}
```

---

### 2.14 Get Pinned Messages

Retrieve all pinned messages in a conversation.

**Request**
```
GET {{base_url}}/messages/1/pined-messages
```

**Sample Response (200)**
```json
{
    "data": [
        {
            "id": 50,
            "message": "Hello everyone! How is the project going?",
            "message_type": "text",
            "is_pinned": true,
            "is_mine": false,
            "sender": {
                "id": 2,
                "name": "Jane Smith",
                "avatar_path": "storage/avatars/user2.jpg"
            },
            "attachments": [],
            "created_at": "2026-02-17T10:35:00.000000Z"
        }
    ]
}
```

---

## 3. Reaction Endpoints

---

### 3.1 Toggle Reaction

Add a reaction to a message. If the same reaction already exists from the user, it will be removed.

**Request**
```
POST {{base_url}}/messages/50/reaction
Content-Type: application/json
```

**Body**
```json
{
    "reaction": "❤️"
}
```

**Sample Response (200) - Reaction Added**
```json
{
    "data": [
        {
            "id": 1,
            "message_id": 50,
            "user_id": 1,
            "reaction": "❤️",
            "created_at": "2026-02-17T10:50:00.000000Z"
        }
    ]
}
```

**Available Reaction Types:**
```
❤️   😂   👍   😮   😢   😡
```

---

### 3.2 Get Message Reactions

Get all reactions for a specific message grouped by type.

**Request**
```
GET {{base_url}}/messages/50/reaction
```

**Sample Response (200)**
```json
{
    "data": {
        "total": 4,
        "grouped": {
            "❤️": {
                "count": 3,
                "users": [
                    {
                        "user_id": 1,
                        "name": "John Doe",
                        "avatar_path": null
                    },
                    {
                        "user_id": 2,
                        "name": "Jane Smith",
                        "avatar_path": "storage/avatars/user2.jpg"
                    },
                    {
                        "user_id": 3,
                        "name": "Bob Jones",
                        "avatar_path": null
                    }
                ]
            },
            "👍": {
                "count": 1,
                "users": [
                    {
                        "user_id": 4,
                        "name": "Alice Brown",
                        "avatar_path": null
                    }
                ]
            }
        }
    }
}
```

---

## 4. Group Management Endpoints

---

### 4.1 Update Group Info

Update group name, description, avatar, or permission settings.

**Request**
```
POST {{base_url}}/group/2/update
Content-Type: multipart/form-data
```

**Body (form-data) - Text Fields Only**
| Key | Value | Type |
|---|---|---|
| `name` | `Development Team 2026` | Text |
| `group[description]` | `Updated team channel for all devs` | Text |
| `group[type]` | `private` | Text |
| `group[can_members_send_messages]` | `1` | Text |
| `group[can_members_add_participants]` | `0` | Text |
| `group[can_members_edit_group_info]` | `0` | Text |
| `group[admins_must_approve_new_members]` | `0` | Text |

**Body (form-data) - With Avatar**
| Key | Value | Type |
|---|---|---|
| `name` | `Development Team 2026` | Text |
| `group[description]` | `Updated team channel` | Text |
| `group[avatar]` | (select image file) | File |

**Sample Response (200)**
```json
{
    "data": {
        "id": 2,
        "type": "group",
        "name": "Development Team 2026",
        "group_setting": {
            "avatar": "http://localhost:8000/storage/groups/newavatar.jpg",
            "description": "Updated team channel for all devs",
            "type": "private",
            "can_members_send_messages": true,
            "can_members_add_participants": false,
            "can_members_edit_group_info": false,
            "admins_must_approve_new_members": false
        },
        "updated_at": "2026-02-17T11:00:00.000000Z"
    }
}
```

---

### 4.2 Add Members to Group

Add one or more users to a group conversation.

**Request**
```
POST {{base_url}}/group/2/members/add
Content-Type: application/json
```

**Body**
```json
{
    "member_ids": [5, 6, 7]
}
```

**Sample Response (200)**
```json
{
    "data": {
        "members": [
            {
                "id": 5,
                "name": "Charlie Davis",
                "role": "member",
                "avatar": null
            },
            {
                "id": 6,
                "name": "Diana Evans",
                "role": "member",
                "avatar": null
            }
        ]
    },
    "message": "Members added successfully."
}
```

---

### 4.3 Remove Members from Group

Remove one or more members from a group (admin only).

**Request**
```
POST {{base_url}}/group/2/members/remove
Content-Type: application/json
```

**Body**
```json
{
    "member_ids": [6]
}
```

**Sample Response (200)**
```json
{
    "data": {
        "original": {
            "data": {
                "members": [
                    {
                        "id": 6,
                        "name": "Diana Evans"
                    }
                ]
            }
        }
    },
    "message": "Members removed successfully."
}
```

---

### 4.4 Get Group Members

Get paginated list of all members in a group.

**Request**
```
GET {{base_url}}/group/2/members?page=1&per_page=20
```

**Sample Response (200)**
```json
{
    "data": [
        {
            "user": {
                "id": 1,
                "name": "John Doe",
                "email": "john@example.com",
                "avatar_path": null
            },
            "role": "super_admin",
            "is_muted": false,
            "created_at": "2026-02-05T09:00:00.000000Z"
        },
        {
            "user": {
                "id": 2,
                "name": "Jane Smith",
                "email": "jane@example.com",
                "avatar_path": "storage/avatars/user2.jpg"
            },
            "role": "admin",
            "is_muted": false,
            "created_at": "2026-02-05T09:00:00.000000Z"
        },
        {
            "user": {
                "id": 3,
                "name": "Bob Jones",
                "email": "bob@example.com",
                "avatar_path": null
            },
            "role": "member",
            "is_muted": false,
            "created_at": "2026-02-05T09:00:00.000000Z"
        }
    ],
    "meta": {
        "current_page": 1,
        "last_page": 1,
        "per_page": 20,
        "total": 3
    }
}
```

---

### 4.5 Add Admin

Promote one or more members to admin role.

**Request**
```
POST {{base_url}}/group/2/admins/add
Content-Type: application/json
```

**Body**
```json
{
    "member_ids": [3, 5]
}
```

**Sample Response (200)**
```json
{
    "message": "Admin role assigned successfully.",
    "data": {
        "promoted_users": [3, 5]
    }
}
```

---

### 4.6 Remove Admin

Demote admins back to regular member role.

**Request**
```
POST {{base_url}}/group/2/admins/remove
Content-Type: application/json
```

**Body**
```json
{
    "member_ids": [5]
}
```

**Sample Response (200)**
```json
{
    "message": "Admin role removed successfully.",
    "data": {
        "demoted_users": [5]
    }
}
```

---

### 4.7 Mute / Unmute Group

Mute group notifications for a specified duration.

**Request**
```
POST {{base_url}}/group/2/mute
Content-Type: application/json
```

**Body Options:**
```json
// Unmute
{ "minutes": 0 }

// Mute for 1 hour
{ "minutes": 60 }

// Mute for 8 hours
{ "minutes": 480 }

// Mute for 24 hours
{ "minutes": 1440 }

// Mute forever
{ "minutes": -1 }
```

**Sample Response (200)**
```json
{
    "message": "Group muted for 60 minutes.",
    "data": {
        "is_muted": true,
        "muted_until": "2026-02-17T11:35:00.000000Z"
    }
}
```

---

### 4.8 Leave Group

Leave a group conversation.

**Request**
```
POST {{base_url}}/group/2/leave
```

**Sample Response (200)**
```json
{
    "message": "You have left the group."
}
```

---

### 4.9 Delete Group

Permanently delete a group (super admin only).

**Request**
```
DELETE {{base_url}}/group/2/delete-group
```

**Sample Response (200)**
```json
{
    "message": "Group deleted successfully."
}
```

---

### 4.10 Regenerate Invite Link

Generate a new invite link for the group (invalidates the old one).

**Request**
```
POST {{base_url}}/group/2/regenerate-invite
```

**Sample Response (200)**
```json
{
    "data": {
        "invite_link": "http://localhost:8000/api/v1/accept-invite/newtoken789xyz",
        "token": "newtoken789xyz"
    }
}
```

---

### 4.11 Accept Group Invite

Join a group using an invite link token.

**Request**
```
GET {{base_url}}/accept-invite/abc123xyz
```

**Sample Response (200)**
```json
{
    "message": "You have joined the group.",
    "data": {
        "conversation_id": 2,
        "group_name": "Development Team 2026"
    }
}
```

---

## 5. User Management Endpoints

---

### 5.1 Get Online Users

Get list of users who are currently online.

**Request**
```
GET {{base_url}}/online-users
```

**Sample Response (200)**
```json
{
    "data": [
        {
            "id": 2,
            "name": "Jane Smith",
            "avatar_path": "storage/avatars/user2.jpg"
        },
        {
            "id": 4,
            "name": "Alice Brown",
            "avatar_path": null
        }
    ]
}
```

---

### 5.2 Get Available Users

Search for users to start a conversation or add to a group.

**Request**
```
GET {{base_url}}/available-users?search=john&page=1&per_page=20
```

**Query Parameters**
| Parameter | Type | Required | Description |
|---|---|---|---|
| `search` | string | No | Search by name or email |
| `page` | integer | No | Page number |
| `per_page` | integer | No | Items per page |

**Sample Response (200)**
```json
{
    "data": {
        "data": [
            {
                "id": 3,
                "name": "John Watson",
                "email": "watson@example.com",
                "avatar_path": null,
                "is_online": false
            },
            {
                "id": 7,
                "name": "Johnny Bravo",
                "email": "johnny@example.com",
                "avatar_path": "storage/avatars/user7.jpg",
                "is_online": true
            }
        ],
        "current_page": 1,
        "last_page": 1,
        "per_page": 20,
        "total": 2
    }
}
```

---

### 5.3 Toggle Block User

Block or unblock a user. Calling the same endpoint again will toggle the state.

**Request**
```
POST {{base_url}}/users/3/block-toggle
```

**Sample Response (200) - Blocked**
```json
{
    "data": {
        "blocked": true,
        "user_id": 3,
        "message": "User has been blocked."
    }
}
```

**Sample Response (200) - Unblocked**
```json
{
    "data": {
        "blocked": false,
        "user_id": 3,
        "message": "User has been unblocked."
    }
}
```

---

### 5.4 Toggle Restrict User

Restrict or unrestrict a user. Restricted messages are visible to sender but hidden from receiver.

**Request**
```
POST {{base_url}}/users/3/restrict-toggle
```

**Sample Response (200) - Restricted**
```json
{
    "data": {
        "restricted": true,
        "user_id": 3,
        "message": "User has been restricted."
    }
}
```

**Sample Response (200) - Unrestricted**
```json
{
    "data": {
        "restricted": false,
        "user_id": 3,
        "message": "User restriction removed."
    }
}
```

---

## 6. Error Responses

All endpoints return consistent error responses:

### 401 Unauthenticated
```json
{
    "message": "Unauthenticated."
}
```

### 403 Forbidden
```json
{
    "message": "You are not authorized to perform this action."
}
```

### 404 Not Found
```json
{
    "message": "Conversation not found."
}
```

### 422 Validation Error
```json
{
    "message": "The given data was invalid.",
    "errors": {
        "conversation_id": [
            "The conversation id field is required."
        ],
        "message": [
            "The message field must not be empty when no attachments are provided."
        ]
    }
}
```

### 500 Server Error
```json
{
    "message": "Internal server error."
}
```

---

## 7. Postman Collection Setup Guide

### Step 1: Create Collection

1. Open Postman and click **New Collection**.
2. Name it `Chat System API`.
3. Add description: `API endpoints for Laravel Chat System`.

### Step 2: Set Up Environment

1. Click **Environments** > **New**.
2. Name it `Chat - Local`.
3. Add the following variables:

| Variable | Initial Value | Current Value |
|---|---|---|
| `base_url` | `http://localhost:8000/api/v1` | `http://localhost:8000/api/v1` |
| `conversation_id` | `1` | `1` |
| `group_id` | `2` | `2` |
| `message_id` | `50` | `50` |
| `user_id` | `3` | `3` |

### Step 3: Configure Collection Authentication

1. Go to your collection settings.
2. Click the **Authorization** tab.
3. Select **No Auth** (session handled by cookies).
4. Go to **Pre-request Script** tab and add:
```javascript
// Optional: Log base URL for debugging
console.log('Base URL:', pm.environment.get('base_url'));
```

### Step 4: Enable Cookie Handling

1. Go to Postman **Settings** (gear icon).
2. Enable **Automatically follow redirects**.
3. Enable **Send cookies**.
4. Go to **Cookies** > Add domain: `localhost`.

### Step 5: Login First

Before testing any endpoint, log in via the browser at `http://localhost:8000/login`, then Postman will reuse the session cookie automatically.

### Step 6: Organize Requests into Folders

Create folders inside your collection:
--------------------------------------

Chat System API/
├── Conversations/
│   ├── List Conversations
│   ├── Create Group
│   ├── Start Private Chat
│   ├── Delete Conversation
│   └── Get Media Library
├── Messages/
│   ├── Send Text Message
│   ├── Send with Reply
│   ├── Send with Attachment
│   ├── Edit Message
│   ├── Delete for Me
│   ├── Delete for Everyone
│   ├── Mark as Seen
│   ├── Forward Message
│   └── Toggle Pin
├── Reactions/
│   ├── Toggle Reaction
│   └── Get Reactions
├── Groups/
│   ├── Update Group Info
│   ├── Add Members
│   ├── Remove Members
│   ├── Get Members
│   ├── Add Admin
│   ├── Remove Admin
│   ├── Mute Group
│   ├── Leave Group
│   ├── Delete Group
│   └── Regenerate Invite
└── Users/
    ├── Online Users
    ├── Available Users
    ├── Block Toggle
    └── Restrict Toggle
```

---

## 8. Quick Test Sequence

Run endpoints in this order for a complete end-to-end test:

** User Management Endpoints
-----------------------------
01.  GET  /online-users                           Get list of users who are currently online.
02.  GET  /available-users                        Find users to chat with
03. POST /users/{id}/block-toggle                 Toggle Block a user
04. POST /users/{id}/restrict-toggle              Toggle Restrict User

** Conversation Endpoints
-----------------------------
05.  GET  /conversations                          Verify conversation in list
06. POST /conversations                           Create group
07.  POST /conversations/private                  Start private conversation
08. DELETE /conversations/{id}                    Delete conversation
09. GET  /conversations/{id}/media                Check media library

** Message Endpoints
-----------------------------
10.  POST /messages                               Send first message
11.  POST /messages                               Send reply message
12.  GET /messages{id}                            Get Conversation Messages
13.  PUT  /messages/{id}                          Edit a message
14. DELETE /messages/delete-for-me                Delete for self
15. DELETE /messages/delete-for-everyone          Delete Messages for Everyone (Unsent)
16.  GET  /messages/seen/{conversation}           Mark messages as seen
17.  GET  /messages/delivered/{conversation}      Mark messages as delivered
18.  GET  /messages/mark-seen                     Mark Specific Messages as Seen
19. POST /messages/{id}/forward                   Forward message
20. POST /messages/{id}/toggle-pin                Pin a message
21. GET  /messages/{conversation}/pined-messages  Verify pinned message
22.  POST /messages/{id}/reaction                 Add a reaction
23.  GET  /messages/{id}/reaction                 Verify reaction

** Group Management Endpoints
-----------------------------
24. POST /group/{id}/update                       Update Group Info
25. POST /group/{id}/members/add                  Add members to group
26. POST /group/{id}/members/remove               Remove members to group
27. GET  /group/{id}/members                      Verify members
28. POST /group/{id}/admins/add                   Promote to admin
29. POST /group/{id}/admins/remove                Demote to admin
30. POST /group/{id}/mute                         Mute / Unmute group
31. POST /group/{id}/leave                        Leave group
32. POST /group/{id}/delete-group                 Permanently delete a group (super admin only)
33. POST /group/{id}/regenerate-invite            Generate a new invite link for the group
34. GET /accept-invite/{token}                    Join a group using an invite link token

```

---

*Last Updated: February 17, 2026 | Version: 1.0.0*