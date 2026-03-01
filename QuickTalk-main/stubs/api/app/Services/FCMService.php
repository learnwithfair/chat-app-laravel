<?php

namespace App\Services;

use Kreait\Firebase\Factory;

class FCMService
{
    protected $messaging;

    public function __construct()
    {
        $factory = (new Factory)->withServiceAccount(config('services.firebase.credentials'));
        $this->messaging = $factory->createMessaging();
    }

    public function sendMessage($token, $title, $body, $attachments = [], $data = [])
    {
        $message = [
            'token' => $token,

            'notification' => [
                'title' => $title,
                'body' => $body ?? 'Photo',
                'image' => $messageSend->attachments['image'] ?? null,
            ],

            'data' => array_merge($data, [
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            ]),

            'android' => [
                'priority' => 'high',
            ],

            'apns' => [
                'headers' => [
                    'apns-priority' => '10',
                ],
                'payload' => [
                    'aps' => [
                        'content-available' => 1,
                    ],
                ]
            ]
        ];

        $this->messaging->send($message);
    }
}
