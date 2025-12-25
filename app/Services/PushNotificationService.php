<?php

namespace App\Services;

use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FcmNotification;

class PushNotificationService
{
    protected Messaging $messaging;

    public function __construct(Messaging $messaging)
    {
        $this->messaging = $messaging;
    }

    /**
     * Send to device tokens
     */
    public function sendToTokens(array $tokens, string $title, string $body, array $data = []): array
    {
        if (empty($tokens)) {
            return ['success' => false, 'message' => 'No tokens'];
        }

        $notification = FcmNotification::create($title, $body);

        $message = CloudMessage::new()
            ->withNotification($notification)
            ->withData($data);

        $report = $this->messaging->sendMulticast($message, $tokens);

        $successCount = $report->successes()->count();
        $failureCount = $report->failures()->count();

        return [
            'success'       => $successCount > 0,
            'successCount'  => $successCount,
            'failureCount'  => $failureCount,
            'failures'      => $report->failures(),
        ];
    }    

    /**
     * Send to a topic broadcast push (e.g. send to all Rank A users, or a "new drop live" notification)
     */
    public function sendToTopic(string $topic, string $title, string $body, array $data = []): array
    {
        $notification = FcmNotification::create($title, $body);

        $message = CloudMessage::new()
            ->withNotification($notification)
            ->withData($data)
            ->withChangedTarget('topic', $topic);

        $this->messaging->send($message);

        return ['success' => true];
    }
}
