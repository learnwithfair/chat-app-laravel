<?php
namespace App\Services;

use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging\ApnsConfig;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FcmNotification;

class PushNotificationService
{
    protected Messaging $messaging;

    // Dependency injection with config/firebase.php
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

        $message = CloudMessage::new ()
            ->withNotification(FcmNotification::create($title, $body))
            ->withData(array_merge($data, [
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            ]))
            ->withAndroidConfig(
                AndroidConfig::fromArray(['priority' => 'high'])
            )
            ->withApnsConfig(
                ApnsConfig::fromArray([
                    'headers' => ['apns-priority' => '10'],
                    'payload' => ['aps' => ['sound' => 'default']],
                ])
            );

        $report = $this->messaging->sendMulticast($message, $tokens);

        $successCount = $report->successes()->count();
        $failureCount = $report->failures()->count();

        foreach ($report->failures()->getItems() as $failure) {
            Log::error('FCM failure: ' . $failure->error()->getMessage());
        }

        return [
            'success'      => $successCount > 0,
            'successCount' => $successCount,
            'failureCount' => $failureCount,
            'failures'     => $report->failures(),
        ];
    }

    /**
     * Send to a topic broadcast push (e.g. send to all Rank A users, or a "new drop live" notification)
     */
    public function sendToTopic(string $topic, string $title, string $body, array $data = []): array
    {
        $notification = FcmNotification::create($title, $body);

        $message = CloudMessage::new ()
            ->withNotification($notification)
            ->withData($data)
            ->withChangedTarget('topic', $topic);

        $this->messaging->send($message);

        return ['success' => true];
    }
}
