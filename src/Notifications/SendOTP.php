<?php

namespace AuroraWebSoftware\FilamentLoginKit\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SendOTP extends Notification implements ShouldBeEncrypted, ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public $tries;

    public $backoff;

    public $uniqueFor;

    public function __construct(
        public string $code
    ) {
        $this->onQueue(config('filament-loginkit.email_queue', 'filament-loginkit'));
        $this->tries = config('filament-loginkit.notification_max_tries', 1);
        $this->backoff = config('filament-loginkit.notification_backoff', 0);
        $this->uniqueFor = 600;
    }

    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('filament-loginkit::filament-loginkit.email.subject'))
            ->view('filament-loginkit::emails.two-factor-code', [
                'code' => $this->code,
                'user' => $notifiable,
            ]);
    }

    public function uniqueId()
    {
        return 'mail-' . $this->code . '-' . $this->getKey();
    }

    public function failed($exception)
    {
        \Log::error('[SendOTP] notification failed: ' . $exception->getMessage());
    }
}
