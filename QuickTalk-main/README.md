# QuickTalk, Laravel Real-Time Chat Package

<p>QuickTalk is a powerful, production-ready real-time chat system for Laravel applications.
It provides private messaging, group chat, typing indicators, reactions, attachments, notifications, and broadcasting support using Reverb or Pusher.</p>

### ✨Features
- 💬 One-to-one messaging
- 👥 Group conversations
- ⚡ Real-time updates (Reverb / Pusher)
- ✍️ Typing indicators
- 😀 Message reactions
- 📎 File attachments
- 🔔 Notifications
- 🟢 Online status tracking
- 🔐 Sanctum API authentication support
- 🧩 Easy installation command
- 🏗️ Clean architecture

### 📋 Requirements
- PHP 8.2+
- Laravel 11^
- MySQL
- Laravel Sanctum
- Broadcasting driver (Reverb or Pusher)

### 😎Core Contributor

-   <a href="https://github.com/rhishi-kesh" target="_blank">Rhishi kesh</a>

### 🚀 Installation
Install via Composer:
```php
composer require rhishi-kesh/quick-talk
```
Run the installer:
```php
php artisan install:quicktalk
```
Follow the interactive prompts to choose:
- Broadcasting stack (Reverb / Pusher)
- Authentication setup (optional)

### ⚙️ Manual Setup (Required)
1️⃣ Add Avatar Column to Users Table <br>
Add this to your users migration:
```php
$table->string('avatar')->nullable();
```
2️⃣ Add Seeder to DatabaseSeeder <br>
Open:
```php
database/seeders/DatabaseSeeder.php
```
Add:
```php
$this->call(UserSeeder::class);
```
Then run:
```php
php artisan migrate
php artisan db:seed
```
3️⃣ Create Storage Symlink <br>
Required for serving uploaded images:
```php
php artisan storage:link
```
4️⃣ Register Chat Routes <br>
Open bootstrap/app.php and add:
```php
->withRouting(
    api: __DIR__ . '/../routes/api.php',
    channels: __DIR__ . '/../routes/channels.php',
    then: function () {
        Route::middleware('api')
            ->prefix('api')
            ->group(base_path('routes/chat_auth.php'));

        Route::middleware('api')
            ->prefix('api')
            ->group(base_path('routes/chat.php'));
    }
)
```
5️⃣ Enable Broadcasting Routes <br>
Still in bootstrap/app.php, add:
```php
->withBroadcasting(__DIR__ . '/../routes/channels.php', ['prefix' => 'api', 'middleware' => ['auth:sanctum']],)
```
6️⃣ User Model Configuration <br>
Update your App\Models\User model <br>
Required Traits
```php
use Laravel\Sanctum\HasApiTokens;

use HasApiTokens;
```
Required Imports
```php
use Illuminate\Support\Str;
```
Fillable Fields
```php
protected $fillable = [
    'name',
    'email',
    'password',
    'avatar',
];
```
Helper: Initials
```php
public function initials(): string
{
    return Str::of($this->name)
        ->explode(' ')
        ->take(2)
        ->map(fn($word) => Str::substr($word, 0, 1))
        ->implode('');
}
```
Chat Relationships
```php
// Messages sent by this user
    public function sentMessages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    // Messages received by this user (for direct/private chats only)
    public function receivedMessages()
    {
        return $this->hasMany(Message::class, 'receiver_id');
    }

    // Conversations where user is a participant
    public function participants()
    {
        return $this->morphMany(Participant::class, 'participant');
    }

    // Conversations where user is a participant (through Participant model)
    public function conversations()
    {
        return $this->hasManyThrough(
            Conversation::class,
            Participant::class,
            'participant_id', // Foreign key on Participant table
            'id',             // Local key on Conversation table
            'id',             // Local key on User table
            'conversation_id' // Foreign key on Participant table
        )->where('participant_type', self::class);
    }

    // Message reactions made by this user
    public function messageReactions()
    {
        return $this->hasMany(MessageReaction::class);
    }

    // Read/delivery status records
    public function messageStatuses()
    {
        return $this->hasMany(MessageStatus::class);
    }

    // Firebase tokens for push notifications
    public function firebaseTokens()
    {
        return $this->hasOne(FirebaseToken::class);
    }
```
### 📡 Broadcasting Setup <br>
Ensure broadcasting is configured.
<h6>For Reverb</h6>
Follow Laravel Reverb setup instructions.
<h6>For Pusher</h6>
Set credentials

```php
PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_APP_CLUSTER=
```
### ▶️ Start Project Server
Use bellow commends
```php
php artisan serve
```
```php
npm run dev
```
```php
php artisan reverb:start
```
### 📌 Setup Echo Js on your frontend
In your frontend, configure Laravel Echo to connect to WebSockets.
```js
<script src="https://cdn-script.com/ajax/libs/jquery/3.7.1/jquery.min.js" type="text/javascript"></script>
<script>
    $(document).ready(function() {
        Echo.private('chat-channel.' + 1).listen('.message.event', (e) => {
            console.log('Message Receive:', e);
        })

        Echo.private('conversation-channel.' + 1).listen('.conversation.event', (e) => {
            console.log('Conversation and Unread Message count:', e);
        })

        Echo.join('online-status-channel')
            .here(users => {
                console.log('Active Users:', [users]);
            })
            .joining(user => {
                console.log('User Joined:', [user]);
            })
            .leaving(user => {
                console.log('User Left:', [user]);
            });

        const typingInput = document.getElementById('typingInput');
        const typingImage = document.getElementById('typingImage');

        let typingTimeout;

        // Send typing event when user types
        typingInput.addEventListener('input', function () {
            Echo.private('typing-indicator-channel.1')
                .whisper('typing', { user: 'RKB' });

            console.log('Typing Indicator Sent');
        });

        // Listen for typing event from others
        Echo.private('typing-indicator-channel.1')
            .listenForWhisper('typing', (e) => {
                console.log('Typing Indicator Received:', e.user);

                // Show typing image
                typingImage.style.display = 'block';

                // Clear previous timeout if user keeps typing
                clearTimeout(typingTimeout);

                // Hide typing image 2 seconds after last typing event
                typingTimeout = setTimeout(() => {
                    typingImage.style.display = 'none';
                }, 1000);
            });

    });
</script>
```
### 📁 API Routes
You Can Explore the API documentation from Here (https://documenter.getpostman.com/view/39612169/2sB34kDeK1)
### 🤝 Contributing
- Contributions are welcome!
- Feel free to submit issues and pull requests.
### 📄 License
MIT License
