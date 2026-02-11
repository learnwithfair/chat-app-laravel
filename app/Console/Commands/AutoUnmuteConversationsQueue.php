<?php
namespace App\Console\Commands;

use App\Jobs\UnmuteConversationJob;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AutoUnmuteConversationsQueue extends Command
{

    protected $signature = 'conversations:auto-unmute
                            {--chunk=500 : Number of records to fetch per batch}';

    protected $description = 'Queue auto-unmute jobs for very large datasets';

    public function handle()
    {
        $this->info(' Queuing auto-unmute jobs...');

        $chunkSize   = (int) $this->option('chunk') ?? 500;
        $totalQueued = 0;

        try {
            // Fetch expired mutes and queue jobs
            DB::table('conversation_participants')
                ->where('is_muted', true)
                ->whereNotNull('muted_until')
                ->where('muted_until', '<=', Carbon::now())
                ->select('id', 'user_id', 'conversation_id')
                ->chunkById($chunkSize, function ($expiredMutes) use (&$totalQueued) {
                    foreach ($expiredMutes as $participant) {
                        UnmuteConversationJob::dispatch(
                            $participant->id,
                            $participant->user_id,
                            $participant->conversation_id
                        );

                        $totalQueued++;
                    }

                    $this->line(" Queued {$expiredMutes->count()} jobs...");
                });

            if ($totalQueued === 0) {
                $this->info(' No conversations to unmute.');
            } else {
                $this->info('Jobs will be processed by queue workers.');
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error(' Error queuing jobs: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
