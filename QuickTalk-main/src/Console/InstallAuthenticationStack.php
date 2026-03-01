<?php

namespace RhishiKesh\QuickTalk\Console;

use Illuminate\Filesystem\Filesystem;

trait InstallAuthenticationStack
{
    /**
     * Publish the authentication-related files.
     *
     * @return void
     */
    private function publishAuthenticationFiles()
    {
        $files = new Filesystem();

        // ---------- Publish routes ----------
        $map = [

            __DIR__ . '/../../stubs/api/routes/chat_auth.php'
            => base_path('routes/chat_auth.php'),
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

        // ---------- Publish controllers ----------
        $base = __DIR__ . '/../../stubs/api/app';
        $apiControllerPath = app_path('Http/Controllers/Api');

        $this->publishAuthenticationDirectory("$base/Http/Auth", $apiControllerPath . '/Auth');

        $this->publishAuthenticationFile(
            "$base/Http/SocialAuthController.php",
            $apiControllerPath . '/SocialAuthController.php'
        );

        $this->publishAuthenticationFile(
            "$base/Http/UserController.php",
            $apiControllerPath . '/UserController.php'
        );

        $this->info('Authentication files published successfully.');
    }

    /**
     * Publish a single file with checks.
     *
     * @param string $from Source file path
     */
    protected function publishAuthenticationFile(string $from, string $to): void
    {
        $files = new Filesystem();

        if (! $files->exists($from)) {
            $this->error("Source file missing: $from");
            return;
        }

        if ($files->exists($to)) {
            $this->warn(basename($to) . ' exists — skipped.');
            return;
        }

        $files->ensureDirectoryExists(dirname($to));
        $files->copy($from, $to);

        $this->info('Published: ' . basename($to));
    }

    /**
     * Publish all files from a directory with checks.
     *
     * @param string $from Source directory path
     * @param string $to Target directory path
     */
    protected function publishAuthenticationDirectory(string $from, string $to): void
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
     * Install the QuickTalk authentication stack.
     *
     * @return void
     */
    protected function installAuthenticationStack()
    {
        $this->info('Installing QuickTalk authentication stack...');

        $this->info('Installing Sanctum...');
        $this->call('install:api');

        $this->info('Setting up authentication routes...');
        $this->publishAuthenticationFiles();

        $this->info('QuickTalk authentication stack installation complete.');
    }
}
