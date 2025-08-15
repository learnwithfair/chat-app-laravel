<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;

class CustomResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $token,
        public object $settings,
        public string $url
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->setDynamicMailConfig();

        return (new MailMessage)
            ->subject('Reset Password Notification')
            ->line('You are receiving this email because we received a password reset request for your account.')
            ->action('Reset Password', $this->url)
            ->line('This password reset link will expire in :count minutes.', ['count' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire')])
            ->line('If you did not request a password reset, no further action is required.');
    }

    protected function setDynamicMailConfig(): void
    {
        if ($this->settings->isNotEmpty() && $this->settings->has('mail_host')) {
            $password = '';
            try {
                $password = $this->settings->has('mail_password') ? Crypt::decryptString($this->settings['mail_password']) : null;
            } catch (\Exception $e) {
                // Fails silently
            }

            $config = [
                'transport'  => 'smtp',
                'host'       => $this->settings['mail_host'],
                'port'       => $this->settings['mail_port'],
                'encryption' => $this->settings['mail_encryption'] ?? 'tls',
                'username'   => $this->settings['mail_username'] ?? null,
                'password'   => $password,
            ];

            Config::set('mail.mailers.dynamic_smtp', $config);
            Config::set('mail.from', [
                'address' => $this->settings['mail_from_address'] ?? 'hello@example.com',
                'name' => $this->settings['mail_from_name'] ?? 'Example'
            ]);
            Config::set('mail.default', 'dynamic_smtp');
        }
    }
}