<?php

namespace AuroraWebSoftware\FilamentLoginKit\Notifications;

use AuroraWebSoftware\FilamentLoginKit\Channels\SmsChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\App;

class SmsLoginNotification extends Notification implements ShouldBeEncrypted, ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public $tries;

    public $backoff;

    public $uniqueFor;

    protected string $code;

    protected ?string $message;

    protected ?string $templateKey;

    public $locale;

    public function __construct(string $code, ?string $message = null, ?string $templateKey = null, ?string $locale = null)
    {
        $this->code = $code;
        $this->message = $message;
        $this->templateKey = $templateKey ?? 'default';
        $this->locale = $locale;

        if (config('filament-loginkit.queue_notifications', true)) {
            $this->onQueue(config('filament-loginkit.sms_queue', 'filament-loginkit'));
        }
        $this->onQueue(config('filament-loginkit.email_queue', 'filament-loginkit'));
        $this->tries = config('filament-loginkit.notification_max_tries', 1);
        $this->backoff = config('filament-loginkit.notification_backoff', 0);
        $this->uniqueFor = 600;
    }

    public function via($notifiable)
    {
        return [SmsChannel::class];
    }

    public function shouldQueue()
    {
        return config('filament-loginkit.queue_notifications', true);
    }

    public function toSms($notifiable)
    {
        $locale = $this->locale
            ?? ($notifiable->locale ?? App::getLocale());

        $template = trans("filament-loginkit::filament-loginkit.{$this->templateKey}", [], $locale);

        return str_replace(
            [':code', ':message'],
            [$this->code, $this->message ?? ''],
            $template
        );
    }

    public function uniqueId()
    {
        return 'sms-' . $this->code . '-' . $this->getKey();
    }

    public function failed($exception)
    {
        \Log::error('[SmsLoginNotification] notification failed: ' . $exception->getMessage());
    }
}
