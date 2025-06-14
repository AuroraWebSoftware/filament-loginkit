<?php

namespace AuroraWebSoftware\FilamentLoginKit\Notifications;

use AuroraWebSoftware\FilamentLoginKit\Channels\SmsChannel;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\App;

class SmsLoginNotification extends Notification
{
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
    }

    public function via($notifiable)
    {
        return [SmsChannel::class];
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
}
