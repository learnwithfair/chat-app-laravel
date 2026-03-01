<?php

namespace RhishiKesh\QuickTalk\Console;

use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;

trait InstallPusherStack
{
    /**
     * Require the Pusher SDK via Composer.
     *
     * @return bool
     */
    protected function requirePusher()
    {
        $process = new Process(['composer', 'require', 'pusher/pusher-php-server'], base_path());
        $process->setTimeout(null);

        $process->run(function ($type, $output) {
            $this->output->write($output);
        });

        if (! $process->isSuccessful()) {
            $this->error('Failed to install Pusher.');
            return false;
        }

        return true;
    }

    /**
     * Setup Echo JS in the frontend.
     *
     * @return bool
     */
    protected function setupPusherEchoJS()
    {
        $filesystem = new Filesystem();

        $appJsPath = resource_path('js/app.js');
        $bootstrapJsPath = resource_path('js/bootstrap.js');
        $echoJsPath = resource_path('js/echo.js');

        // ---------- 1. Ensure app.js exists ----------
        if (! $filesystem->exists($appJsPath)) {
            $filesystem->put($appJsPath, "// Auto-generated app.js\n");
            $this->info('Created app.js file.');
        }

        // ---------- 2. Ensure import './echo' exists ----------
        $appJsContent = $filesystem->get($appJsPath);

        if (! str_contains($appJsContent, "import './echo';")) {
            if ($filesystem->exists($bootstrapJsPath)) {
                $bootstrapContent = $filesystem->get($bootstrapJsPath);

                if (str_contains($bootstrapContent, "import './echo';")) {
                    $this->info('Echo already imported in bootstrap.js.');
                } else {
                    $filesystem->append($appJsPath, "\nimport './echo';\n");
                    $this->info("Added import './echo' to app.js.");
                }
            } else {
                $filesystem->append($appJsPath, "\nimport './echo';\n");
                $this->info("Added import './echo' to app.js.");
            }
        } else {
            $this->info("Echo already imported in app.js.");
        }

        // ---------- 3. Prepare Pusher template ----------
        $pusherTemplate = <<<JS
        import Echo from 'laravel-echo';
        import Pusher from 'pusher-js';
        window.Pusher = Pusher;

        window.Echo = new Echo({
            broadcaster: 'pusher',
            key: import.meta.env.VITE_PUSHER_APP_KEY,
            cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
            forceTLS: true,
        });
        JS;

        // ---------- 4. Handle echo.js ----------
        if (! $filesystem->exists($echoJsPath)) {
            $filesystem->put($echoJsPath, $pusherTemplate);
            $this->info('Created echo.js with Pusher config.');
            return true;
        }

        $echoJsContent = $filesystem->get($echoJsPath);

        // If already Pusher → do nothing
        if (str_contains($echoJsContent, "broadcaster: 'pusher'")) {
            $this->info('Echo already configured for Pusher.');
            return true;
        }

        // If Reverb → replace with Pusher
        if (str_contains($echoJsContent, "broadcaster: 'reverb'")) {
            $filesystem->put($echoJsPath, $pusherTemplate);
            $this->warn('Reverb config found. Replaced with Pusher config.');
            return true;
        }

        // If neither → overwrite with Pusher
        $filesystem->put($echoJsPath, $pusherTemplate);
        $this->info('No broadcaster found. Added Pusher config.');

        return true;
    }

    /**
     * Publish the necessary files for chatting functionality.
     *
     * @return void
     */
    protected function publishPusherChattingFiles(): void
    {
        $files = new Filesystem();

        // ---------- Publish config, routes, seeders ----------
        $map = [

            __DIR__ . '/../../stubs/api/config/chat.php'
            => config_path('chat.php'),

            __DIR__ . '/../../stubs/api/config/cors.php'
            => config_path('cors.php'),

            __DIR__ . '/../../stubs/api/routes/chat.php'
            => base_path('routes/chat.php'),

            __DIR__ . '/../../stubs/api/database/seeders/UserSeeder.php'
            => database_path('seeders/UserSeeder.php'),

        ];

        foreach ($map as $from => $to) {

            if (! $files->exists($from)) {
                $this->error("Source missing: $from");
                continue;
            }

            if ($files->exists($to)) {
                $this->warn(basename($to) . ' exists — skipped.');
                continue;
            }

            $files->ensureDirectoryExists(dirname($to));
            $files->copy($from, $to);

            $this->info('Published: ' . basename($to));
        }

        // ---------- Publish migrations ----------
        $migrationPath = __DIR__ . '/../../stubs/api/database/migrations';
        $destination = database_path('migrations');

        $today = Carbon::now()->format('Y_m_d');

        foreach (File::files($migrationPath) as $file) {

            $originalName = $file->getFilename();

            preg_match('/^\d{4}_\d{2}_\d{2}_(\d{6}_.+)/', $originalName, $matches);

            if (!isset($matches[1])) {
                $this->warn("Skipped invalid migration: $originalName");
                continue;
            }

            $newFilename = $today . '_' . $matches[1];
            $targetPath  = $destination . '/' . $newFilename;

            if (File::exists($targetPath)) {
                $this->warn("$newFilename already exists — skipped.");
                continue;
            }

            File::copy($file->getRealPath(), $targetPath);

            $this->info('Published: ' . $newFilename);
        }

        // ---------- Publish app files ----------
        $base = __DIR__ . '/../../stubs/api/app';

        $this->publishPusherDirectory("$base/Enum", app_path('Enum'));
        $this->publishPusherDirectory("$base/Events", app_path('Events'));
        $this->publishPusherDirectory("$base/Models", app_path('Models'));
        $this->publishPusherDirectory("$base/Notifications", app_path('Notifications'));
        $this->publishPusherDirectory("$base/Services", app_path('Services'));
        $this->publishPusherDirectory("$base/Traits", app_path('Traits'));

        $apiControllerPath = app_path('Http/Controllers/Api');

        $this->publishPusherDirectory("$base/Http/Chat", $apiControllerPath . '/Chat');

        $this->publishPusherEnvVariables();
        $this->publishPusherChannelsFile();

        $this->info('QuickTalk app files published successfully');
    }

    /**
     * Publish the channels.php file with necessary channel definitions.
     *
     * @return void
     */
    protected function publishPusherChannelsFile(): void
    {
        $files = new Filesystem();
        $path = base_path('routes/channels.php');

        $code = <<<'PHP'

        /*
        |--------------------------------------------------------------------------
        | QuickTalk Channels
        |--------------------------------------------------------------------------
        */

        Broadcast::channel('conversation-channel.{participantId}', function ($user, $participantId) {
            return (int) $user->id === (int) $participantId;
        });

        Broadcast::channel('chat-channel.{conversationId}', function ($user, $conversationId) {
            return Conversation::where('id', $conversationId)
                ->whereHas('participants', function ($q) use ($user) {
                    $q->where('participant_id', $user->id);
                })
                ->exists();
        });

        Broadcast::channel('online-status-channel', function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'avatar' => $user->avatar ?? null,
            ];
        });

        Broadcast::channel('typing-indicator-channel.{conversationId}', function ($user, $conversationId) {
            return Conversation::where('id', $conversationId)
                ->whereHas('participants', function ($q) use ($user) {
                    $q->where('participant_id', $user->id);
                })
                ->exists();
        });

        PHP;

        if (! $files->exists($path)) {

            $content = <<<PHP
                <?php

                use App\Models\Conversation;
                use Illuminate\Support\Facades\Broadcast;

                $code
                PHP;

            $files->ensureDirectoryExists(dirname($path));
            $files->put($path, $content);

            $this->info('channels.php created with QuickTalk channels.');
            return;
        }

        $existing = $files->get($path);

        // Prevent duplicate insertion
        if (str_contains($existing, 'conversation-channel.{participantId}')) {
            $this->warn('QuickTalk channels already exist — skipped.');
            return;
        }

        // Ensure required imports exist
        if (! str_contains($existing, 'use Illuminate\Support\Facades\Broadcast;')) {
            $existing = preg_replace(
                '/<\?php\s*/',
                "<?php\n\nuse Illuminate\\Support\\Facades\\Broadcast;",
                $existing,
                1
            );
        }

        if (! str_contains($existing, 'use App\Models\Conversation;')) {
            $existing = preg_replace(
                '/use Illuminate\\\\Support\\\\Facades\\\\Broadcast;.*\n/',
                "$0use App\\Models\\Conversation;\n",
                $existing,
                1
            );
        }

        // Append QuickTalk channels at end
        $existing .= "\n" . $code;

        $files->put($path, $existing);

        $this->info('QuickTalk channels appended to channels.php.');
    }

    /**
     * Add necessary environment variables to .env file if they don't exist.
     *
     * @return void
     */
    protected function publishPusherEnvVariables(): void
    {
        $envPath = base_path('.env');

        $env = file_get_contents($envPath);

        if (! str_contains($env, 'GROUP_PARTICIPATE_LIMIT')) {
            file_put_contents($envPath, "\nGROUP_PARTICIPATE_LIMIT=50", FILE_APPEND);
        }

        if (! str_contains($env, 'ATTACHMENT_SIZE_LIMIT')) {
            file_put_contents($envPath, "\nATTACHMENT_SIZE_LIMIT=10", FILE_APPEND);
        }

        $this->info('.env variables added.');
    }

    /**
     * Publish all files from a directory with checks.
     *
     * @param string $from Source directory path
     * @param string $to Target directory path
     */
    protected function publishPusherDirectory(string $from, string $to): void
    {
        $files = new Filesystem();

        if (! $files->isDirectory($from)) {
            return;
        }

        foreach ($files->allFiles($from) as $file) {

            $target = $to . DIRECTORY_SEPARATOR . $file->getRelativePathname();

            if ($files->exists($target)) {
                $this->warn($file->getRelativePathname() . ' exists — skipped.');
                continue;
            }

            $files->ensureDirectoryExists(dirname($target));
            $files->copy($file->getRealPath(), $target);

            $this->info('Published: ' . $file->getRelativePathname());
        }
    }

    /**
     * Install the QuickTalk Pusher stack.
     *
     * @return void
     */
    protected function installPusherStack()
    {
        $this->info('Installing QuickTalk Pusher stack...');

        $this->info('Installing Broadcasting...');
        $this->call('install:broadcasting');

        $this->info('Installing Pusher...');
        $this->requirePusher();

        $this->info('Setting up Echo JS...');
        $this->setupPusherEchoJS();

        $this->info('Setting up chat routes...');
        $this->publishPusherChattingFiles();

        $this->info('QuickTalk Pusher stack installation complete.');
    }
}
