<?php
namespace App\Jobs;

use App\Events\ConversationEvent;
use App\Models\Conversation;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UnmuteConversationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 30;

    /**
     * Participant ID
     *
     * @var int
     */
    protected $participantId;

    /**
     * User ID
     *
     * @var int
     */
    protected $userId;

    /**
     * Conversation ID
     *
     * @var int
     */
    protected $conversationId;

    // Constructor
    public function __construct($participantId, $userId, $conversationId)
    {
        $this->participantId  = $participantId;
        $this->userId         = $userId;
        $this->conversationId = $conversationId;
    }

    // Unmute the conversation
    public function handle(): void
    {
        try {
            // Unmute the conversation
            $updated = DB::table('conversation_participants')
                ->where('id', $this->participantId)
                ->where('is_muted', true) // Double-check still muted
                ->update(['is_muted' => false, 'muted_until' => null, 'updated_at' => Carbon::now()]);

            if ($updated) {
                $conversation = Conversation::find($this->conversationId);

                if ($conversation) {
                    broadcast(new ConversationEvent(
                        $conversation,
                        'unmuted',
                        $this->userId,
                        ['participant_id' => $this->participantId, 'is_muted' => false]
                    ));
                }

            }

        } catch (\Exception $e) {
            Log::error('Failed to unmute conversation', [
                'participant_id'  => $this->participantId,
                'user_id'         => $this->userId,
                'conversation_id' => $this->conversationId,
                'error'           => $e->getMessage(),
            ]);

            // Re-throw to trigger retry
            throw $e;
        }
    }

    // Handle failure
    public function failed(\Throwable $exception): void
    {
        Log::error('Unmute job failed permanently', [
            'participant_id'  => $this->participantId,
            'user_id'         => $this->userId,
            'conversation_id' => $this->conversationId,
            'error'           => $exception->getMessage(),
        ]);
    }
}
