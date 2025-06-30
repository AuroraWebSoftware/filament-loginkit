<?php

namespace AuroraWebSoftware\FilamentLoginKit\Listeners;

use AuroraWebSoftware\FilamentLoginKit\Notifications\SendOTP;
use AuroraWebSoftware\FilamentLoginKit\Notifications\SmsLoginNotification;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\Events\TwoFactorAuthenticationChallenged;
use Laravel\Fortify\Events\TwoFactorAuthenticationEnabled;

class SendTwoFactorCodeListener
{
    public function handle(
        TwoFactorAuthenticationChallenged|TwoFactorAuthenticationEnabled $event
    ): void {
        $user   = $event->user;
        $type   = strtolower(trim($user->two_factor_type));
        $locale = session('locale') ?? ($user->locale ?? null) ?? config('app.locale');

        if ($type === 'authenticator') {
            return;
        }

        $expiresAt = $user->two_factor_expires_at;

        if ($expiresAt && $expiresAt->isFuture()) {
            return;
        }

        $len        = config('filament-loginkit.sms.code_length', 6);
        $ttlMinutes = config('filament-loginkit.sms.code_ttl', 5);
        $code       = str_pad(random_int(0, (10 ** $len) - 1), $len, '0', STR_PAD_LEFT);

        $user->forceFill([
            'two_factor_code'       => Hash::make($code),
            'two_factor_expires_at' => now()->addMinutes($ttlMinutes),
        ])->save();

        $notification = $type === 'sms'
            ? (new SmsLoginNotification($code, null, null, $locale))->locale($locale)
            : (new SendOTP($code))->locale($locale);

        App::setLocale($locale);

        config('filament-loginkit.queue_notifications', true)
            ? $user->notify($notification)
            : $user->notifyNow($notification);
    }
}
