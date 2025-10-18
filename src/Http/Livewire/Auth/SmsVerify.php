<?php

namespace AuroraWebSoftware\FilamentLoginKit\Http\Livewire\Auth;

use App\Models\User;
use AuroraWebSoftware\FilamentLoginKit\Notifications\SmsLoginNotification;
use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Twilio\Rest\Client;

class SmsVerify extends Page
{
    protected static string|null|\BackedEnum $navigationIcon = null;

    protected string $view = 'filament-loginkit::auth.sms-verify';

    protected static string $layout = 'filament-panels::components.layout.simple';

    public function getHeading(): string
    {
        return '';
    }

    public ?string $phone_number = null;

    public string $sms_code = '';

    public int $maxSmsAttempts;

    public int $smsAttemptDecay;

    public int $countdown = 0;

    public bool $canResend = false;

    public string $loginType = 'sms';

    public function mount(): void
    {
        $panel = Filament::getPanel(session('flk_panel_id'));
        $panelPrimary = $panel?->getColors()['primary'] ?? Color::Amber;
        FilamentColor::register([
            'default' => is_string($panelPrimary) ? Color::hex($panelPrimary) : $panelPrimary,
            'primary' => is_string($panelPrimary) ? Color::hex($panelPrimary) : $panelPrimary,
        ]);

        $this->maxSmsAttempts = (int) config('filament-loginkit.sms.max_wrong_attempts');
        $this->smsAttemptDecay = (int) config('filament-loginkit.sms.wrong_attempt_decay');

        $this->phone_number = session('flk_sms_phone');
        $this->loginType = session('flk_login_type', 'sms');

        if (! $this->phone_number) {
            $this->redirect(Filament::getLoginUrl(), navigate: false);

            return;
        }

        $this->startCountdown();
    }

    public function verify()
    {
        $code = trim($this->sms_code);
        $loginSucceeded = false;
        $user = null;

        DB::transaction(function () use (&$loginSucceeded, &$user, $code) {
            $user = User::where('phone_number', $this->phone_number)
                ->where("{$this->loginType}_login_expires_at", '>', now())
                ->lockForUpdate()
                ->first();

            if (! $user || ! Hash::check($code, $user->{$this->loginType . '_login_code'})) {
                return;
            }

            $user->update([
                "{$this->loginType}_login_code" => null,
                "{$this->loginType}_login_expires_at" => null,
            ]);

            $loginSucceeded = true;
        });

        if (! $loginSucceeded) {
            $wrongKey = 'sms_wrong:' . md5($this->phone_number);
            $attempts = $this->cacheIncrement($wrongKey, $this->smsAttemptDecay);

            if ($attempts >= $this->maxSmsAttempts) {
                Notification::make()
                    ->title(__('filament-loginkit::filament-loginkit.sms.max_wrong_title'))
                    ->body(__('filament-loginkit::filament-loginkit.sms.max_wrong_body'))
                    ->danger()
                    ->send();

                return;
            }

            Notification::make()
                ->title(__('filament-loginkit::filament-loginkit.sms.invalid_code_title'))
                ->body(__('filament-loginkit::filament-loginkit.sms.invalid_code_body'))
                ->danger()
                ->send();

            return;
        }

        Cache::forget('sms_wrong:' . md5($this->phone_number));

        $panelId = session('flk_panel_id');
        if ($panelId) {
            session()->put('panel', $panelId);
        }

        Filament::auth()->login($user, false);
        session()->forget('flk_sms_phone');

        $panel = $panelId ? Filament::getPanel($panelId) : Filament::getCurrentPanel();
        if (! $panel || ($user instanceof FilamentUser && ! $user->canAccessPanel($panel))) {
            Filament::auth()->logout();
            Notification::make()
                ->title(__('filament-loginkit::filament-loginkit.sms.inactive_title'))
                ->body(__('filament-loginkit::filament-loginkit.sms.inactive_body'))
                ->danger()
                ->send();

            return;
        }

        session()->put('login_type', 'sms');

        request()->session()->regenerate(true);

        return $this->redirectIntended($panel->getUrl(), navigate: false);
    }

    public function resend(): void
    {
        if (blank($this->phone_number)) {
            Notification::make()
                ->title(__('filament-loginkit::filament-loginkit.sms.invalid_session_title'))
                ->body(__('filament-loginkit::filament-loginkit.sms.invalid_session_body'))
                ->danger()
                ->send();

            return;
        }

        $user = User::where('phone_number', $this->phone_number)->first();
        if (! $user) {
            Notification::make()
                ->title(__('filament-loginkit::filament-loginkit.sms.generic_fail_title'))
                ->body(__('filament-loginkit::filament-loginkit.sms.generic_fail_body'))
                ->danger()
                ->send();

            return;
        }

        if ($this->isUserInactive($user)) {
            Notification::make()
                ->title(__('filament-loginkit::filament-loginkit.sms.inactive_title'))
                ->body(__('filament-loginkit::filament-loginkit.sms.inactive_body'))
                ->danger()
                ->send();

            return;
        }

        $floodKey = 'sms_flood:' . md5($this->phone_number);
        if (Cache::get($floodKey, 0) >= config('filament-loginkit.sms.flood.max_per_window')) {
            Notification::make()
                ->title(__('filament-loginkit::filament-loginkit.sms.too_many_requests_title'))
                ->body(__('filament-loginkit::filament-loginkit.sms.too_many_requests_body'))
                ->danger()
                ->send();

            return;
        }

        $resendKey = 'sms_resend:' . md5($this->phone_number);
        $resendWindow = (int) config('filament-loginkit.sms.resend.window_minutes');
        if ($this->cacheIncrement($resendKey, $resendWindow * 60) >
            (int) config('filament-loginkit.sms.resend.max_requests')) {

            Notification::make()
                ->title(__('filament-loginkit::filament-loginkit.sms.resend_limit_title'))
                ->body(__('filament-loginkit::filament-loginkit.sms.resend_limit_body', ['window_minutes' => $resendWindow]))
                ->danger()
                ->send();

            return;
        }

        $code = $this->generateSmsCode();

        $user->update([
            "{$this->loginType}_login_code" => Hash::make($code),
            "{$this->loginType}_login_expires_at" => now()->addMinutes(config('filament-loginkit.sms.code_ttl')),
        ]);

        try {
            if ($this->loginType === 'sms') {
                $this->dispatchSms($user, $code);
            } else {
                $this->dispatchWhatsapp($user, $code);
            }
        } catch (\Throwable $e) {
            Notification::make()
                ->title(__('filament-loginkit::filament-loginkit.sms.send_failed_title'))
                ->body(__('filament-loginkit::filament-loginkit.sms.send_failed_body'))
                ->danger()
                ->send();

            return;
        }

        $this->cacheIncrement($floodKey, (int) config('filament-loginkit.sms.flood.window_minutes') * 60);

        $this->startCountdown();

        Notification::make()
            ->title(__('filament-loginkit::filament-loginkit.sms.resent_title'))
            ->body(__('filament-loginkit::filament-loginkit.sms.resent_body'))
            ->success()
            ->send();
    }

    private function isUserInactive(?User $user): bool
    {
        return $user
            && Schema::hasColumn('users', 'is_active')
            && ! (bool) $user->is_active;
    }

    private function cacheIncrement(string $key, int $ttl): int
    {
        if (! Cache::has($key)) {
            Cache::put($key, 1, $ttl);

            return 1;
        }
        Cache::increment($key);

        return (int) Cache::get($key);
    }

    private function generateSmsCode(): string
    {
        $len = (int) config('filament-loginkit.sms.code_length');

        return str_pad((string) random_int(0, (10 ** $len) - 1), $len, '0', STR_PAD_LEFT);
    }

    private function dispatchSms(User $user, string $code): void
    {
        $locale = app()->getLocale();
        $notification = new SmsLoginNotification($code, null, null, $locale);
        $notification->locale($locale);

        if (config('filament-loginkit.queue_notifications')) {
            $user->notify($notification);
        } else {
            $user->notifyNow($notification);
        }
    }

    public function startCountdown(): void
    {
        $seconds = (int) config('filament-loginkit.sms.resend_cooldown', 60);
        $this->countdown = $seconds;
        $this->canResend = false;
        $this->dispatch('start-countdown', seconds: $seconds);
    }

    public function resetCountdown(): void
    {
        $this->countdown = 0;
        $this->canResend = true;
    }

    public function updateCountdown(int $value): void
    {
        $this->countdown = $value;
        $this->canResend = $value <= 0;
    }

    private function dispatchWhatsapp(User $user, string $code): void
    {
        $sid = config('filament-loginkit.twilio.sid');
        $token = config('filament-loginkit.twilio.token');
        $from = config('filament-loginkit.twilio.whatsapp_from');
        $tplSid = config('filament-loginkit.twilio.whatsapp_template_sid');

        if (blank($sid) || blank($token) || blank($from) || blank($tplSid)) {
            Log::error('Twilio WhatsApp credentials missing.');

            throw new \RuntimeException('Twilio WhatsApp credentials missing.');
        }

        $client = new Client($sid, $token);

        $to = 'whatsapp:' . (str_starts_with($user->phone_number, '+')
                ? $user->phone_number
                : '+' . $user->phone_number);

        if (! str_starts_with($from, 'whatsapp:')) {
            $from = 'whatsapp:' . (str_starts_with($from, '+') ? $from : '+' . $from);
        }

        try {
            $client->messages->create(
                $to,
                [
                    'from' => $from,
                    'contentSid' => $tplSid,
                    'contentVariables' => json_encode([1 => $code]),
                ]
            );
        } catch (\Throwable $e) {
            Log::error('WhatsApp send failed', [
                'to' => $to,
                'from' => $from,
                'tplSid' => $tplSid,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
