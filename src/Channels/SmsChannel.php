<?php

namespace AuroraWebSoftware\FilamentLoginKit\Channels;

use Illuminate\Notifications\Notification;

class SmsChannel
{
    public function send($notifiable, Notification $notification)
    {
        $message = $notification->toSms($notifiable);

        $serviceClass = config('filament-loginkit.sms_service_class');
        $smsService = app($serviceClass);

        $smsService->send($notifiable->phone_number, $message);
    }
}
