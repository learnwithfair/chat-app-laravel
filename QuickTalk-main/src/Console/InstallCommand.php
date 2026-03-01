<?php

namespace RhishiKesh\QuickTalk\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use function Laravel\Prompts\select;

class InstallCommand extends Command implements PromptsForMissingInput
{
    use InstallReverbStack, InstallPusherStack, InstallAuthenticationStack;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */

    protected $signature = 'install:quicktalk {stack : The stack to install (reverb, pusher)} {auth : Whether to also install QuickTalk authentication (yes, no)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install the QuickTalk chat package';

    /**
     * Execute the console command.
     *
     * @return int|null
     */
    public function handle()
    {
        $stack = $this->argument('stack');
        if ($stack === 'reverb') {
            if ($this->argument('auth') === 'yes') {
                $this->installAuthenticationStack();
            }
            return $this->installReverbStack();
        } else if ($stack === 'pusher') {
            if ($this->argument('auth') === 'yes') {
                $this->installAuthenticationStack();
            }
            return $this->installPusherStack();
        }

        $this->components->error('Invalid stack. Supported stacks are [reverb], [pusher].');

        return 1;
    }

    /**
     * Prompt for missing input arguments using the returned questions.
     *
     * @return array
     */
    protected function promptForMissingArgumentsUsing()
    {
        return [
            'stack' => fn() => select(
                label: 'Which QuickTalk stack would you like to install?',
                options: [
                    'reverb' => 'Setup Reverb',
                    'pusher' => 'Setup Pusher',
                ],
                scroll: 2,
            ),
            'auth' => fn() => select(
                label: 'Do you also need QuickTalk authentication?',
                options: [
                    'yes' => 'Yes, install QuickTalk authentication',
                    'no'  => 'No, just install the chat stack',
                ],
                scroll: 2,
            ),
        ];
    }
}
