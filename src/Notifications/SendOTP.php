<?php

namespace AuroraWebSoftware\FilamentLoginKit\Notifications;

use AuroraWebSoftware\FilamentLoginKit\Mail\TwoFactorCodeMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Notification;

class SendOTP extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $code
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(mixed $notifiable): Mailable
    {
        $emailView = 'filament-loginkit::emails.two-factor-code';

        return (new TwoFactorCodeMail($this->code))
            ->to($notifiable->email)
            ->view($emailView);
    }
}
