
-   Run Command:

```bash
composer install
npm install
php artisan migrate
php artisan migrate:fresh --seed
php artisan serve
npm run dev

composer dump-autoload

npm install @tailwindcss/vite --save-dev

```



# Real-Time Chat (Laravel 12 + Vue 3 + Inertia + Tailwind + Reverb)

Version: 1.0
Scope: Implementation guide and future maintenance reference for a WhatsApp-Web style chat (left list + right pane, realtime, typing, media, emoji).

---

## 1) Overview

This chat uses:

* **Backend:** Laravel 12, Eloquent, Broadcasting, **Laravel Reverb** (first-party WebSocket server).
* **Frontend:** Vue 3 + Inertia, Vite, Tailwind, shadcn/ui.
* **Realtime:** Laravel Echo with the **reverb** broadcaster.
* **Storage:** Public disk for attachments (images, videos, files).

The UI is a single page:

* Left: conversation list with search, timestamp, and unread badge.
* Right: messages pane with bubbles, day separators, typing indicator, emoji picker, and media upload.
* New chat modal: search by name/email or start directly by email.

---

## 2) Features

* One-page, WhatsApp-like layout (no page reload when selecting chats).
* New chat modal: search user or type full email and start a conversation.
* Realtime delivery via presence channels; typing indicators via Echo whispers.
* Unread counters and “read all” per conversation.
* Media attachments (images, videos, documents) with Storage URLs.
* Minimal emoji picker; Enter to send.
* Clean Tailwind components.

---

## 3) Environment

Add to `.env`:

```env
BROADCAST_DRIVER=reverb

REVERB_APP_ID=app-id
REVERB_APP_KEY=app-key
REVERB_APP_SECRET=app-secret

REVERB_HOST=127.0.0.1
REVERB_SCHEME=http
REVERB_PORT=8080

VITE_REVERB_APP_KEY=${REVERB_APP_KEY}
VITE_REVERB_HOST=${REVERB_HOST}
VITE_REVERB_PORT=${REVERB_PORT}
VITE_REVERB_SCHEME=${REVERB_SCHEME}
```

---

## 4) Setup Commands

```bash
composer require laravel/reverb
php artisan reverb:install

npm i laravel-echo@^1.16 pusher-js

php artisan make:migration create_message_attachments_table
# paste the migration for message_attachments (see Schema section) then:
php artisan migrate

php artisan storage:link

php artisan serve
php artisan reverb:start
npm run dev
```

---

## 5) Echo Client (frontend)

**`resources/js/echo.ts`**

```ts
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import axios from 'axios'

declare global {
  interface Window { Pusher: typeof Pusher; Echo: Echo; axios: typeof axios }
}

window.Pusher = Pusher
window.axios = axios
axios.defaults.withCredentials = true

const wsHost = import.meta.env.VITE_REVERB_HOST ?? window.location.hostname
const port = Number(import.meta.env.VITE_REVERB_PORT ?? (location.protocol === 'https:' ? 443 : 80))
const forceTLS = (import.meta.env.VITE_REVERB_SCHEME ?? location.protocol.replace(':','')) === 'https'

window.Echo = new Echo({
  broadcaster: 'reverb',
  key: import.meta.env.VITE_REVERB_APP_KEY,
  wsHost,
  wsPort: port,
  wssPort: port,
  forceTLS,
  enabledTransports: ['ws', 'wss'],
})
```

Import once in your app entry:

```ts
// resources/js/app.ts
import './echo'
```

---

## 6) Database Schema (summary)

* **conversations**: `id, name, is_group, timestamps`
* **conversation\_user**: `id, conversation_id, user_id, timestamps`
* **messages**: `id, conversation_id, user_id, body, timestamps`
* **message\_reads**: `id, message_id, user_id, read_at`
* **message\_attachments**: `id, message_id, path, mime, type, size, original_name, timestamps`

Migration for attachments (create if not already):

```php
Schema::create('message_attachments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('message_id')->constrained()->cascadeOnDelete();
    $table->string('path');
    $table->string('mime', 100)->nullable();
    $table->string('type', 20)->nullable(); // image | video | file
    $table->unsignedBigInteger('size')->nullable();
    $table->string('original_name')->nullable();
    $table->timestamps();
});
```

---

## 7) Backend Files (paths)

* `routes/web.php`
* `routes/channels.php`
* `app/Events/MessageCreated.php`
* `app/Models/Conversation.php`
* `app/Models/Message.php`
* `app/Models/MessageRead.php`
* `app/Models/MessageAttachment.php`
* `app/Policies/ConversationPolicy.php`
* `app/Http/Controllers/Chat/ConversationController.php`
* `app/Http/Controllers/Chat/MessageController.php`
* `app/Http/Controllers/Chat/UserSearchController.php`
* `database/migrations/*_create_*` (all chat tables above)

**Key notes**

* `ConversationController@index`: returns conversation list with `lastMessage`, `last_at`, and `unread` count for sidebar.
* `ConversationController@store`: accepts `user_id` or `email`, finds or creates a conversation, and returns a **list item JSON** for immediate insertion into the sidebar.
* `MessageController@index`: returns paginated messages JSON.
* `MessageController@store`: accepts `body` and `attachments[]` (multipart), stores files to `public` disk, marks sender read, broadcasts `MessageCreated` to presence channel, and returns the created message JSON (including `attachments` array).
* `MessageController@readAll`: marks unread messages in the conversation as read for the current user.
* `MessageCreated` event: `ShouldBroadcastNow`, includes message, sender, and `attachments` with `Storage::url()`.

**Broadcast auth (routes/channels.php)**: presence channel `presence.chat.{conversation}` authorizes only conversation members and returns minimal user info.

**Policy**: `ConversationPolicy@view` ensures only members can access a conversation.

---

## 8) Frontend Files (paths)

* `resources/js/pages/Chat/Index.vue` — one page layout with left list + right pane; manages selection state.
* `resources/js/components/chat/ConversationList.vue` — sidebar search and list; emits `select(id)`.
* `resources/js/components/chat/ConversationItem.vue` — avatar, name, preview, time, unread badge.
* `resources/js/components/chat/NewChatModal.vue` — search or email; emits `created(conversationItem)`.
* `resources/js/components/chat/ChatPane.vue` — loads messages, joins/leaves channel, typing, send media + emoji, read-all.
* `resources/js/components/chat/MessageList.vue` — day separators and rendering list.
* `resources/js/components/chat/MessageBubble.vue` — message bubble UI with attachments rendering.
* `resources/js/components/chat/MessageInput.vue` — composer with emoji picker and attachments.
* `resources/js/components/chat/EmojiPicker.vue` — small dependency-free picker.
* `resources/js/components/ui/badge.vue` — unread badge.

---

## 9) HTTP / API Endpoints

* `GET  /chat` → Inertia page with conversation list.
* `GET  /chat/{conversation}/messages` → JSON paginated messages for that conversation.
* `POST /chat/{conversation}/messages` → Create message (text and/or attachments).
* `POST /chat/{conversation}/read-all` → Mark all unread messages as read for current user.
* `POST /chat` → Create/find conversation (`user_id` or `email`), returns **conversation item JSON** for sidebar.
* `GET  /chat-users?q=...` → Search users by name/email (JSON).

---

## 10) How It Works (workflow)

1. **Load /chat**
   The page fetches all conversations for the authenticated user with latest preview, timestamp, and unread count.

2. **Select conversation**
   Clicking a conversation sets `selected` in the page state and passes it to `<ChatPane :conversation="selected" />`.

3. **Join realtime**
   ChatPane loads messages via `GET /chat/{id}/messages`, then joins `presence.chat.{id}` using Echo. It stores the channel name and calls `Echo.leave()` when switching.

4. **Typing**
   On text input, ChatPane whispers `{ userId }` to the presence channel. Other clients show “Typing…” for three seconds.

5. **Send**
   ChatPane sends `multipart/form-data` with `body` and optional `attachments[]` to `POST /chat/{id}/messages`.
   Sender immediately appends the response to the list; other clients receive the broadcast `MessageCreated`.

6. **Read status**
   On opening a conversation or receiving a message, ChatPane calls `POST /chat/{id}/read-all`. Unread badges drop to zero.

7. **Start new chat**
   NewChatModal posts `{ user_id }` or `{ email }` to `POST /chat`. The server returns a ready list item; the page inserts it at the top and sets it as selected so ChatPane opens immediately.

---

## 11) Nginx (example) for Reverb

```nginx
map $http_upgrade $connection_upgrade { default upgrade; '' close; }

location /reverb {
  proxy_pass http://127.0.0.1:8080;
  proxy_set_header Host $host;
  proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
  proxy_set_header X-Forwarded-Proto $scheme;
  proxy_http_version 1.1;
  proxy_set_header Upgrade $http_upgrade;
  proxy_set_header Connection $connection_upgrade;
}
```

In production set `REVERB_SCHEME=https` and `REVERB_HOST` to your domain. Ensure the firewall allows the WebSocket port or proxy it as above.

---

## 12) Testing Checklist

* Two users can create a direct chat via NewChatModal by selecting a user or typing a full email.
* Typing indicator appears on the other user’s screen while typing.
* Messages appear in real time for both sides; sender sees own message immediately.
* Unread badge drops to zero when opening a conversation (read-all).
* Image, video, and document attachments upload and display or link correctly.
* Switching conversations leaves the previous channel and joins the new one without errors.

---

## 13) Troubleshooting

* **“Options object must provide a cluster.”**
  Use `broadcaster: 'reverb'` in `echo.ts` (not `pusher`).

* **Sender does not see own messages until refresh.**
  Append the POST response locally on the sender side; keep `->toOthers()` on broadcast.

* **`presence.leave is not a function`.**
  Leave channels via `window.Echo.leave(channelName)`; store the string channel name.

* **Attachments not visible.**
  Run `php artisan storage:link`; make sure `Storage::url()` is used and public disk is configured.

* **TypeScript template errors (`property X does not exist on type {}`).**
  Use Option API and explicitly `return` template bindings, or strongly type props and returned state.

* **WebSocket not connecting in production.**
  Verify proxy Upgrade/Connection headers, correct `REVERB_HOST/SCHEME/PORT`, and open ports.

---

## 14) Extensibility

* **Delivery/read ticks:** Map `message_reads` to message status and render single/double/blue ticks.
* **Group chats:** Allow multi-select in NewChatModal and save a group name.
* **Message actions:** Edit/delete with policies and broadcast update events.
* **Search:** Index messages for full-text search.
* **Attachments:** Add thumbnails or server-side image transforms.

---

## 15) Daily Commands (local dev)

```bash
php artisan serve
php artisan reverb:start
npm run dev
```

That’s the complete, copy-pasteable implementation guide for your current chat. If you want a branded PDF version or need me to add delivery/read ticks next, say the word.



## Push notificaton
- php artisan queue:work --tries=3
- composer require kreait/laravel-firebase
- php artisan vendor:publish --provider="Kreait\Laravel\Firebase\ServiceProvider"


````bash
Note (production): Use Firebase Admin SDK (service account). I can provide kreait/laravel-firebase instructions if you want secure, robust v1 calls.
````
