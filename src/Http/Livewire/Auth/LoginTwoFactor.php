<?php

namespace AuroraWebSoftware\FilamentLoginKit\Http\Livewire\Auth;

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
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Http\Requests\TwoFactorLoginRequest;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Reactive;
use AuroraWebSoftware\FilamentLoginKit\Enums\TwoFactorType;
use AuroraWebSoftware\FilamentLoginKit\Notifications\SendOTP;
use AuroraWebSoftware\FilamentLoginKit\Notifications\SmsLoginNotification;

class LoginTwoFactor extends Page implements HasActions, HasForms
{
    use InteractsWithFormActions;
    use InteractsWithForms;
    use WithRateLimiting;

    protected static string $layout = 'filament-loginkit::layouts.login';
    protected static string $view   = 'filament-loginkit::auth.login-two-factor';
    protected static bool   $shouldRegisterNavigation = false;

    public mixed   $challengedUser = null;
    public ?string $twoFactorType  = null;

    /**  Filament state dizisi  */
    public array   $data = [];

    #[Reactive] public int $lastResendTime = 0;

    /* --------------------------------------------------------------------- */
    /*  MOUNT                                                                */
    /* --------------------------------------------------------------------- */
    public function mount(TwoFactorLoginRequest $request): void
    {
        abort_unless($request->challengedUser(), 403);

        $this->challengedUser = $request->challengedUser();
        $this->twoFactorType  = $this->challengedUser->two_factor_type?->value
            ?? TwoFactorType::email->value;

        // 30 sn cool-down
        Cache::put('resend_cooldown_'.$this->challengedUser->id, true, now()->addSeconds(30));

        $this->form->fill();
    }

    /* --------------------------------------------------------------------- */
    /*  FORM ŞEMASI                                                          */
    /* --------------------------------------------------------------------- */
    protected function getFormSchema(): array
    {
        return [
            TextInput::make('code')
                ->label(__('Code'))
                ->required()
                ->numeric()
                ->extraInputAttributes([
                    'autocomplete' => 'one-time-code',
                ]),
        ];
    }

    /* --------------------------------------------------------------------- */
    /*  RESEND LOGIC                                                         */
    /* --------------------------------------------------------------------- */
    #[Computed]
    public function canResend(): bool
    {
        return ! Cache::has('resend_cooldown_'.$this->challengedUser->id);
    }

    public function resend(): Action
    {
        return Action::make('resend')
            ->label(__('Resend'))
            ->color('primary')
            ->disabled(fn () => ! $this->canResend())
            ->action(fn () => $this->handleResend());
    }

    public function handleResend(): void
    {
        if (! $this->canResend() || ! $this->throttle()) {
            return;
        }

        $this->generateAndSendCode();

        Cache::put('resend_cooldown_'.$this->challengedUser->id, true, now()->addSeconds(30));
        $this->dispatch('resent');

        Notification::make()
            ->title(__('Successfully resent the code'))
            ->success()
            ->send();
    }

    private function throttle(): bool
    {
        try {
            $this->rateLimit(1, 60);
            return true;
        } catch (TooManyRequestsException $e) {
            Notification::make()
                ->title(__('Too many attempts — please wait.'))
                ->danger()
                ->send();
            return false;
        }
    }

    /* --------------------------------------------------------------------- */
    /*  KOD ÜRET / GÖNDER                                                    */
    /* --------------------------------------------------------------------- */
    protected function generateAndSendCode(): void
    {
        $len  = config('filament-loginkit.sms.code_length', 6);
        $code = str_pad(random_int(0, (10 ** $len) - 1), $len, '0', STR_PAD_LEFT);

        $this->challengedUser->forceFill([
            'two_factor_code'       => Hash::make($code),
            'two_factor_expires_at' => now()->addMinutes(config('filament-loginkit.sms.code_ttl', 5)),
        ])->save();

        $notification = $this->twoFactorType === TwoFactorType::sms->value
            ? new SmsLoginNotification($code)
            : new SendOTP($code);

        $this->challengedUser->notify($notification);
    }

    /* --------------------------------------------------------------------- */
    /*  SUBMIT                                                               */
    /* --------------------------------------------------------------------- */
    public function submit(): void
    {
        $code = trim($this->data['code'] ?? '');

        if ($code === '') {
            Notification::make()->title(__('Lütfen doğrulama kodunu girin'))->danger()->send();
            return;
        }

        if (! $this->throttle()) {
            return;
        }

        if ($this->twoFactorType === TwoFactorType::authenticator->value) {
            try {
                app(ConfirmTwoFactorAuthentication::class)($this->challengedUser, $code);
            } catch (\Throwable) {
                Notification::make()->title(__('Authenticator kodu hatalı'))->danger()->send();
                return;
            }
        } else {
            if (
                ! $this->challengedUser->two_factor_code ||
                ! $this->challengedUser->two_factor_expires_at ||
                now()->greaterThan($this->challengedUser->two_factor_expires_at)
            ) {
                Notification::make()->title(__('Kod süresi geçti, yeni kod isteyin'))->danger()->send();
                return;
            }

            if (! Hash::check($code, $this->challengedUser->two_factor_code)) {
                Notification::make()->title(__('Kod hatalı'))->danger()->send();
                return;
            }

            $this->challengedUser->forceFill([
                'two_factor_code'       => null,
                'two_factor_expires_at' => null,
            ])->save();
        }

        Filament::auth()->login($this->challengedUser, false);
        session()->put('panel', Filament::getCurrentPanel()?->getId());
        session()->regenerate();
        session(['login_2fa_passed' => true]);

        $panelHome = Filament::getCurrentPanel()?->getUrl() ?? '/';
        $this->redirect($panelHome, navigate: true);
    }



    /* --------------------------------------------------------------------- */
    /*  LOGO                                                                 */
    /* --------------------------------------------------------------------- */
    public function hasLogo(): bool
    {
        return true;
    }

    /* --------------------------------------------------------------------- */
    /*  FORM STATE PATH                                                      */
    /* --------------------------------------------------------------------- */
    protected function getFormStatePath(): ?string
    {
        return 'data';
    }
}
