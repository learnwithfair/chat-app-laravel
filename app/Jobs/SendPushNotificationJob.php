<?php
namespace App\Jobs;

use App\Services\PushNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SendPushNotificationJob implements ShouldQueue
{
    use Dispatchable, Queueable, SerializesModels;

    public array $tokens;
    public int|null $userId;
    public string $title;
    public string $body;
    public array $data;
    public string|null $type;
    public bool $isDatabase;

    public function __construct(array $tokens, ?int $userId, string $title, string $body, array $data = [], ?string $type = null, bool $isDatabase = true)
    {
        $this->tokens     = $tokens;
        $this->userId     = $userId;
        $this->title      = $title;
        $this->body       = $body;
        $this->data       = $data;
        $this->type       = $type;
        $this->isDatabase = $isDatabase;
    }

    public function handle(PushNotificationService $pushService)
    {
        // store notification record(s)
        if ($this->userId && $this->isDatabase) {
            // AppNotification::create([
            //     'user_id'    => $this->userId,
            //     'type'       => $this->type,
            //     'title'      => $this->title,
            //     'body'       => $this->body,
            //     'is_read'    => false,
            //     'meta'       => $this->data,
            //     'created_at' => now(),
            // ]);
        }

        // send to FCM
        if (! empty($this->tokens)) {
            $pushService->sendToTokens($this->tokens, $this->title, $this->body, $this->data);
        }
    }
}
