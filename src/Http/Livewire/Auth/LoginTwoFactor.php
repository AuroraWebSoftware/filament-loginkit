<?php

namespace AuroraWebSoftware\FilamentLoginKit\Http\Livewire\Auth;

use AuroraWebSoftware\FilamentLoginKit\Notifications\SendOTP;
use AuroraWebSoftware\FilamentLoginKit\Notifications\SmsLoginNotification;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Filament\Actions\Action;
use Filament\Actions\Contracts\HasActions;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Panel;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Http\Requests\TwoFactorLoginRequest;
use Livewire\Attributes\Computed;

class LoginTwoFactor extends SimplePage implements HasActions, HasForms
{
    use InteractsWithFormActions;
    use InteractsWithForms;
    use WithRateLimiting;

    protected string $view = 'filament-loginkit::auth.login-two-factor';

    protected static bool $shouldRegisterNavigation = false;

    public mixed $challengedUser = null;

    public ?string $twoFactorType = null;

    public array $data = [];

    public function mount(TwoFactorLoginRequest $request): void
    {
        abort_unless($request->challengedUser(), 403);

        $this->challengedUser = $request->challengedUser();
        $this->twoFactorType = $this->challengedUser->two_factor_type ?: 'email';

        if ($panel = Filament::getCurrentPanel()) {
            $panelColor = $panel->getColors()['primary'] ?? Color::Amber;

            FilamentColor::register([
                'default' => is_string($panelColor)
                    ? Color::hex($panelColor)
                    : $panelColor,

                'primary' => is_string($panelColor)
                    ? Color::hex($panelColor)
                    : $panelColor,
            ]);
        }

        Cache::put('resend_cooldown_' . $this->challengedUser->id, true, now()->addSeconds(30));
        $this->form->fill();
    }

    public static function registerRoutes(Panel $panel): void
    {
        //
    }

    protected function getFormSchema(): array
    {
        return [
            TextInput::make('code')
                ->label(__('filament-loginkit::filament-loginkit.two_factor.code_label'))
                ->required()
                ->extraInputAttributes(['autocomplete' => 'one-time-code']),
        ];
    }

    #[Computed]
    public function canResend(): bool
    {
        return !Cache::has('resend_cooldown_' . $this->challengedUser->id);
    }

    public function resend(): Action
    {
        return Action::make('resend')
            ->label(__('filament-loginkit::filament-loginkit.two_factor.resend'))
            ->color('primary')
            ->disabled(fn() => !$this->canResend())
            ->action(fn() => $this->handleResend());
    }

    public function handleResend(): void
    {
        app()->setLocale(session('locale') ?? $this->challengedUser->locale ?? config('app.locale'));

        if (!$this->canResend() || !$this->throttle()) {
            return;
        }

        $this->generateAndSendCode();

        Cache::put('resend_cooldown_' . $this->challengedUser->id, true, now()->addSeconds(30));
        $this->dispatch('resent');

        Notification::make()
            ->title(__('filament-loginkit::filament-loginkit.two_factor.resent_success'))
            ->success()
            ->send();
    }

    private function throttle(): bool
    {
        $limits = config('filament-loginkit.rate_limits.two_factor');

        try {
            $this->rateLimit($limits['max_requests'], $limits['per_minutes'] * 60);

            return true;
        } catch (TooManyRequestsException $e) {
            Notification::make()
                ->title(__('filament-loginkit::filament-loginkit.two_factor.too_many_attempts_title'))
                ->body(
                    __('filament-loginkit::filament-loginkit.two_factor.too_many_attempts_body', [
                        'seconds' => $e->secondsUntilAvailable ?? ($limits['per_minutes'] * 60),
                    ])
                )
                ->danger()
                ->send();

            return false;
        }
    }

    protected function generateAndSendCode(): void
    {
        $len = config('filament-loginkit.sms.code_length', 6);
        $code = str_pad(random_int(0, (10 ** $len) - 1), $len, '0', STR_PAD_LEFT);

        $this->challengedUser->forceFill([
            'two_factor_code' => Hash::make($code),
            'two_factor_expires_at' => now()->addMinutes(config('filament-loginkit.sms.code_ttl', 5)),
        ])->save();

        $notification = $this->twoFactorType === 'sms'
            ? new SmsLoginNotification($code)
            : new SendOTP($code);

        if (config('filament-loginkit.queue_notifications', true)) {
            $this->challengedUser->notify($notification);
        } else {
            $this->challengedUser->notifyNow($notification);
        }
    }

    private function validateRecoveryCode(string $input): bool
    {
        $input = trim($input);

        if (!$this->challengedUser->two_factor_recovery_codes) {
            return false;
        }

        try {
            $recoveryCodes = json_decode(decrypt($this->challengedUser->two_factor_recovery_codes), true);
        } catch (\Exception $e) {
            return false;
        }

        if (!is_array($recoveryCodes)) {
            return false;
        }

        if (in_array($input, $recoveryCodes, true)) {
            $updatedCodes = array_values(array_diff($recoveryCodes, [$input]));

            $this->challengedUser->forceFill([
                'two_factor_recovery_codes' => empty($updatedCodes)
                    ? null
                    : encrypt(json_encode($updatedCodes)),
            ])->save();

            return true;
        }

        return false;
    }

    private function validateTwoFactorCode(string $code): bool
    {
        if (!$this->challengedUser->two_factor_secret) {
            return false;
        }

        try {
            $twoFactorProvider = app(TwoFactorAuthenticationProvider::class);

            return $twoFactorProvider->verify(
                decrypt($this->challengedUser->two_factor_secret),
                $code
            );
        } catch (\Exception $e) {
            return false;
        }
    }

    public function submit(): void
    {
        $code = trim($this->data['code'] ?? '');

        if ($code === '') {
            Notification::make()
                ->title(__('filament-loginkit::filament-loginkit.two_factor.please_enter_code'))
                ->danger()
                ->send();

            return;
        }

        if (!$this->throttle()) {
            return;
        }

        if ($this->twoFactorType === 'authenticator') {
            $isValid = $this->validateTwoFactorCode($code) || $this->validateRecoveryCode($code);

            if (!$isValid) {
                Notification::make()
                    ->title(__('filament-loginkit::filament-loginkit.two_factor.invalid_code'))
                    ->danger()
                    ->send();

                return;
            }
        } else {
            if (
                !$this->challengedUser->two_factor_code ||
                !$this->challengedUser->two_factor_expires_at ||
                now()->greaterThan($this->challengedUser->two_factor_expires_at)
            ) {
                Notification::make()
                    ->title(__('filament-loginkit::filament-loginkit.two_factor.code_expired'))
                    ->danger()
                    ->send();

                return;
            }

            if (!Hash::check($code, $this->challengedUser->two_factor_code)) {
                Notification::make()
                    ->title(__('filament-loginkit::filament-loginkit.two_factor.invalid_code'))
                    ->danger()
                    ->send();

                return;
            }

            $this->challengedUser->forceFill([
                'two_factor_code' => null,
                'two_factor_expires_at' => null,
            ])->save();
        }

        Filament::auth()->login($this->challengedUser, false);
        session()->put('panel', Filament::getCurrentPanel()?->getId());
        session()->regenerate();
        session(['login_2fa_passed' => true]);

        $panelId = session('panel') ?? Filament::getDefaultPanel()?->getId();
        $panel = Filament::getPanel($panelId);

        $this->redirect($panel?->getUrl() ?? '/', navigate: true);
    }

    public function hasLogo(): bool
    {
        return true;
    }

    protected function getFormStatePath(): ?string
    {
        return 'data';
    }
}
