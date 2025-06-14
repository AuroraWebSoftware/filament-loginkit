<?php

namespace AuroraWebSoftware\FilamentLoginKit\Listeners;

use AuroraWebSoftware\FilamentLoginKit\Enums\TwoFactorType;
use AuroraWebSoftware\FilamentLoginKit\Notifications\SendOTP;
use AuroraWebSoftware\FilamentLoginKit\Notifications\SmsLoginNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Laravel\Fortify\Events\TwoFactorAuthenticationChallenged;
use Laravel\Fortify\Events\TwoFactorAuthenticationEnabled;

class SendTwoFactorCodeListener
{
    /**
     * Fortify 2FA event’lerini yakala ve
     * kullanıcının two_factor_type alanına göre
     * kod üret + bildir.
     *
     * @param TwoFactorAuthenticationChallenged|TwoFactorAuthenticationEnabled $event
     */
    public function handle(
        TwoFactorAuthenticationChallenged|TwoFactorAuthenticationEnabled $event
    ): void {
        $user = $event->user;

        // Authenticator seçiliyse kod göndermeyiz.
        if ($user->two_factor_type === TwoFactorType::authenticator) {
            return;
        }

        // 🔐 Kod üret
        $len       = config('filament-loginkit.sms.code_length', 6);
        $plainCode = str_pad(random_int(0, (10 ** $len) - 1), $len, '0', STR_PAD_LEFT);

        $user->forceFill([
            'two_factor_code'       => Hash::make($plainCode),
            'two_factor_expires_at' => now()->addMinutes(config('filament-loginkit.sms.code_ttl', 5)),
        ])->save();

        // Bildirim tipini seç
        $notification = $user->two_factor_type === TwoFactorType::sms
            ? new SmsLoginNotification($plainCode)
            : new SendOTP($plainCode);

        try {
            if (config('filament-loginkit.enabled_features.sms_queue')) {
                $user->notify($notification);
            } else {
                $user->notifyNow($notification);
            }
        } catch (\Throwable $e) {
            Log::error('2FA kodu gönderilemedi', ['msg' => $e->getMessage()]);
        }
    }
}
