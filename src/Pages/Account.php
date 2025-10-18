<?php

namespace AuroraWebSoftware\FilamentLoginKit\Pages;

use AuroraWebSoftware\FilamentLoginKit\Enums\TwoFactorType;
use AuroraWebSoftware\FilamentLoginKit\Notifications\SendOTP;
use AuroraWebSoftware\FilamentLoginKit\Notifications\SmsLoginNotification;
use Carbon\Carbon;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

class Account extends Page implements HasForms
{
    use InteractsWithForms, WithRateLimiting;

    protected string $view = 'filament-loginkit::account';

    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationLabel(): string
    {
        return __('filament-loginkit::filament-loginkit.navigation.account');
    }

    public function getTitle(): string
    {
        return __('filament-loginkit::filament-loginkit.account.title');
    }

    public function getLayout(): string
    {
        $user = Auth::user();

        $has2faColumns = $user && (
                array_key_exists('is_2fa_required', $user->getAttributes()) ||
                array_key_exists('two_factor_type', $user->getAttributes())
            );

        if (
            $has2faColumns &&
            ($user->is_2fa_required ?? false) &&
            (empty($user->two_factor_type) || is_null($user->two_factor_type))
        ) {
            return 'filament-loginkit::layouts.simple';
        }

        return parent::getLayout();
    }

    public array $account = [];

    public array $password = [];

    public array $twoFactor = [];

    public $user;

    public bool $show2faSetup = false;

    public bool $showQrCode = false;

    public bool $showRecoveryCodes = false;

    public bool $showConfirmation = false;

    public string $otpCode = '';

    public string $selected2faType = '';

    public bool $canEditAccount = true;

    public function mount(): void
    {
        $this->user = Auth::user();

        $this->account = [
            'name' => $this->user->name,
            'email' => $this->user->email,
            'phone_number' => $this->user->phone_number,
        ];

        $this->twoFactor = [
            'twoFactorType' => $this->user->two_factor_type ? TwoFactorType::from($this->user->two_factor_type)->value : null,
        ];
        $this->canEditAccount = config('filament-loginkit.account_page.can_edit');

    }

    public function isSimpleLayout(): bool
    {
        return $this->getLayout() === 'filament-loginkit::layouts.simple';
    }

    public function accountForm(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make(__('filament-loginkit::filament-loginkit.account.user_information'))
                    ->description(__('filament-loginkit::filament-loginkit.account.user_information_description'))
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('name')
                                ->label(__('filament-loginkit::filament-loginkit.fields.name'))
                                ->required()
                                ->maxLength(255)
                                ->disabled(fn() => !$this->canEditAccount),
                            TextInput::make('email')
                                ->label(__('filament-loginkit::filament-loginkit.fields.email'))
                                ->email()
                                ->required()
                                ->maxLength(255)
                                ->disabled(fn() => !$this->canEditAccount),
                            PhoneInput::make('phone_number')
                                ->label(__('filament-loginkit::filament-loginkit.sms.phone_label'))
                                ->initialCountry('tr')
                                ->countryOrder(['tr'])
                                ->strictMode()
                                ->required(),
                        ]),
                    ]),
            ])
            ->statePath('account');
    }

    public function saveAccount(): void
    {
        try {
            $this->user->update($this->account);
            Notification::make()
                ->title(__('filament-loginkit::filament-loginkit.notifications.success'))
                ->body(__('filament-loginkit::filament-loginkit.notifications.account_updated'))
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title(__('filament-loginkit::filament-loginkit.notifications.error'))
                ->body(__('filament-loginkit::filament-loginkit.notifications.account_update_failed'))
                ->danger()
                ->send();
        }
    }

    public function passwordForm(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make(__('filament-loginkit::filament-loginkit.account.change_password'))
                    ->description(__('filament-loginkit::filament-loginkit.account.change_password_description'))
                    ->schema([
                        Grid::make(1)->schema([
                            TextInput::make('current_password')
                                ->label(__('filament-loginkit::filament-loginkit.fields.current_password'))
                                ->password()
                                ->required()
                                ->currentPassword(),
                            TextInput::make('new_password')
                                ->label(__('filament-loginkit::filament-loginkit.fields.new_password'))
                                ->password()
                                ->required()
                                ->rules([Password::defaults()])
                                ->helperText(__('filament-loginkit::filament-loginkit.fields.password_requirements')),
                            TextInput::make('new_password_confirmation')
                                ->label(__('filament-loginkit::filament-loginkit.fields.new_password_confirmation'))
                                ->password()
                                ->required()
                                ->same('new_password'),
                        ]),
                    ]),
            ])
            ->statePath('password');
    }

    public function changePassword(): void
    {
        try {
            $data = $this->password;

            if (!Hash::check($data['current_password'], $this->user->password)) {
                Notification::make()
                    ->title(__('filament-loginkit::filament-loginkit.notifications.error'))
                    ->body(__('filament-loginkit::filament-loginkit.notifications.current_password_incorrect'))
                    ->danger()
                    ->send();

                return;
            }

            $this->user->update(['password' => Hash::make($data['new_password'])]);
            $this->reset('password');

            Notification::make()
                ->title(__('filament-loginkit::filament-loginkit.notifications.success'))
                ->body(__('filament-loginkit::filament-loginkit.notifications.password_changed'))
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title(__('filament-loginkit::filament-loginkit.notifications.error'))
                ->body(__('filament-loginkit::filament-loginkit.notifications.password_change_failed'))
                ->danger()
                ->send();
        }
    }

    public function twoFactorForm(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Radio::make('twoFactorType')
                    ->label(__('filament-loginkit::filament-loginkit.two_factor.method'))
                    ->options($this->resolve2faOptions())
                    ->descriptions($this->resolve2faDescriptions())
                    ->required()
                    ->inline(false)
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->selected2faType = $state;
                    }),
            ])
            ->statePath('twoFactor');
    }

    protected function resolve2faOptions(): array
    {
        $enabledOptions = config('filament-loginkit.options', []);
        $options = [];

        foreach ($enabledOptions as $option) {
            if ($option instanceof TwoFactorType) {
                $options[$option->value] = __('filament-loginkit::filament-loginkit.two_factor.methods.' . $option->value);
            }
        }

        return $options;
    }

    protected function resolve2faDescriptions(): array
    {
        return [
            TwoFactorType::sms->value => __('filament-loginkit::filament-loginkit.two_factor.descriptions.sms'),
            TwoFactorType::email->value => __('filament-loginkit::filament-loginkit.two_factor.descriptions.email'),
            TwoFactorType::authenticator->value => __('filament-loginkit::filament-loginkit.two_factor.descriptions.authenticator'),
        ];
    }


    public function getCurrentTwoFactorTypeProperty(): ?string
    {
        return $this->twoFactor['twoFactorType'] ?? null;
    }

    public function is2faEnabled(): bool
    {
        return $this->user->two_factor_confirmed_at !== null;
    }

    public function getCurrentMethodName(): string
    {
        if (!$this->is2faEnabled()) {
            return '';
        }

        return __('filament-loginkit::filament-loginkit.two_factor.methods.' . $this->user->two_factor_type);
    }

    public function start2faSetup(): void
    {
        if ($this->is2faEnabled()) {
            Notification::make()
                ->title(__('filament-loginkit::filament-loginkit.notifications.info'))
                ->body(__('filament-loginkit::filament-loginkit.notifications.two_factor_already_enabled'))
                ->warning()
                ->send();

            return;
        }

        $this->show2faSetup = true;
        $this->reset(['showQrCode', 'showRecoveryCodes', 'showConfirmation', 'otpCode']);
    }

    public function cancel2faSetup(): void
    {
        $this->reset([
            'show2faSetup',
            'showQrCode',
            'showRecoveryCodes',
            'showConfirmation',
            'otpCode',
            'selected2faType',
            'twoFactor',
        ]);
    }

    public function proceed2faSetup(): void
    {
        $type = $this->twoFactor['twoFactorType'] ?? null;

        if (!$type) {
            Notification::make()
                ->title(__('filament-loginkit::filament-loginkit.notifications.warning'))
                ->body(__('filament-loginkit::filament-loginkit.notifications.select_two_factor_method'))
                ->warning()
                ->send();
            return;
        }

        if ($type === TwoFactorType::sms->value && empty($this->user->phone_number)) {
            Notification::make()
                ->title(__('filament-loginkit::filament-loginkit.notifications.error'))
                ->body(__('filament-loginkit::filament-loginkit.account.phone_number_required_for_sms'))
                ->danger()
                ->send();

            return;
        }

        $this->selected2faType = $type;

        if ($type === TwoFactorType::authenticator->value) {
            $this->setup2faAuthenticator();
        } else {
            $this->setup2faCodeBased($type);
        }
    }


    private function setup2faAuthenticator(): void
    {
        try {
            $enableAction = app(EnableTwoFactorAuthentication::class);
            $enableAction($this->user);

            $this->showQrCode = true;
            $this->showConfirmation = true;

            Notification::make()
                ->title(__('filament-loginkit::filament-loginkit.notifications.qr_code_ready'))
                ->body(__('filament-loginkit::filament-loginkit.notifications.scan_qr_code'))
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title(__('filament-loginkit::filament-loginkit.notifications.error'))
                ->body(__('filament-loginkit::filament-loginkit.notifications.authenticator_setup_failed'))
                ->danger()
                ->send();
        }
    }


    private function setup2faCodeBased(string $type): void
    {
        try {
            $this->sendCodeForType($type);
        } catch (\Exception $e) {
            Notification::make()
                ->title(__('filament-loginkit::filament-loginkit.notifications.error'))
                ->body(__('filament-loginkit::filament-loginkit.notifications.verification_code_send_failed'))
                ->danger()
                ->send();
        }
    }

    private function sendCodeForType(string $type): void
    {
        $len = config('filament-loginkit.account_page.code_length', 6);
        $code = str_pad(random_int(0, (10 ** $len) - 1), $len, '0', STR_PAD_LEFT);

        $ttl = config('filament-loginkit.account_page.2fa.code_ttl', 5);
        $limits = config('filament-loginkit.account_page.rate_limits');
        $rateKey = '2fa:' . $type . ':' . $this->user->id;

        if (RateLimiter::tooManyAttempts($rateKey, $limits['max_requests'])) {
            $seconds = RateLimiter::availableIn($rateKey);
            Notification::make()
                ->title(__('filament-loginkit::filament-loginkit.notifications.warning'))
                ->body(__('filament-loginkit::filament-loginkit.two_factor.too_many_requests', [
                    'seconds' => $seconds,
                ]))
                ->danger()
                ->send();

            $this->showConfirmation = false;
            return;
        }

        RateLimiter::hit($rateKey, $limits['per_minutes'] * 60);

        if (
            $this->user->two_factor_code &&
            $this->user->two_factor_expires_at &&
            Carbon::parse($this->user->two_factor_expires_at)->isFuture()
        ) {
            $this->showConfirmation = true;
            return;
        }

        if ($type === TwoFactorType::sms->value) {
            $notification = new SmsLoginNotification($code);
        } elseif ($type === TwoFactorType::email->value) {
            $notification = new SendOTP($code);
        } else {
            Notification::make()
                ->title(__('filament-loginkit::filament-loginkit.notifications.error'))
                ->body(__('filament-loginkit::filament-loginkit.notifications.invalid_two_factor_type'))
                ->danger()
                ->send();

            $this->showConfirmation = true;
            return;
        }

        try {
            if (config('filament-loginkit.queue_notifications', true)) {
                $this->user->notify($notification);
            } else {
                $this->user->notifyNow($notification);
            }
        } catch (\Throwable $e) {
            Notification::make()
                ->title(__('filament-loginkit::filament-loginkit.notifications.error'))
                ->body(__('filament-loginkit::filament-loginkit.notifications.code_send_failed'))
                ->danger()
                ->send();

            $this->showConfirmation = true;
            return;
        }

        $this->user->forceFill([
            'two_factor_code' => Hash::make($code),
            'two_factor_expires_at' => now()->addMinutes($ttl),
        ])->save();

        $this->showConfirmation = true;

        $methodName = __('filament-loginkit::filament-loginkit.two_factor.methods.' . $type);
        Notification::make()
            ->title(__('filament-loginkit::filament-loginkit.notifications.success'))
            ->body(__('filament-loginkit::filament-loginkit.notifications.code_sent_to', ['method' => $methodName]))
            ->success()
            ->send();
    }




    public function confirmTwoFactorAuthentication(): void
    {
        if (!$this->otpCode || strlen($this->otpCode) !== 6) {
            Notification::make()
                ->title(__('filament-loginkit::filament-loginkit.notifications.warning'))
                ->body(__('filament-loginkit::filament-loginkit.notifications.enter_six_digit_code'))
                ->warning()
                ->send();

            return;
        }

        $type = $this->selected2faType;

        try {
            if ($type === TwoFactorType::authenticator->value) {
                $this->confirmAuthenticatorCode();
            } else {
                $this->confirmCodeBasedAuth();
            }

            $this->complete2faSetup();
        } catch (\Exception $e) {
            Notification::make()
                ->title(__('filament-loginkit::filament-loginkit.notifications.error'))
                ->body(__('filament-loginkit::filament-loginkit.notifications.invalid_verification_code'))
                ->danger()
                ->send();
        }
    }


    private function confirmAuthenticatorCode(): void
    {
        $confirmAction = app(ConfirmTwoFactorAuthentication::class);
        $confirmAction($this->user, $this->otpCode);
    }

    private function confirmCodeBasedAuth(): void
    {
        if (
            !$this->user->two_factor_code ||
            !Hash::check($this->otpCode, $this->user->two_factor_code) ||
            Carbon::parse($this->user->two_factor_expires_at)->isPast()
        ) {
            throw new \Exception('Invalid or expired code');
        }
    }

    private function complete2faSetup(): void
    {
        $this->user->forceFill([
            'two_factor_type' => $this->selected2faType,
            'two_factor_confirmed_at' => now(),
            'two_factor_code' => null,
            'two_factor_expires_at' => null,
        ])->save();

        $methodName = __('filament-loginkit::filament-loginkit.two_factor.methods.' . $this->selected2faType);

        if ($this->selected2faType === TwoFactorType::authenticator->value) {
            $this->showRecoveryCodes = true;
        }

        $this->reset([
            'show2faSetup',
            'showQrCode',
            'showConfirmation',
            'otpCode',
            'twoFactor',
        ]);

        Notification::make()
            ->title(__('filament-loginkit::filament-loginkit.notifications.success'))
            ->body(__('filament-loginkit::filament-loginkit.notifications.two_factor_enabled', ['method' => $methodName]))
            ->success()
            ->duration(5000)
            ->send();
    }


    public function disable2fa(): void
    {
        if (!$this->is2faEnabled()) {
            Notification::make()
                ->title(__('filament-loginkit::filament-loginkit.notifications.info'))
                ->body(__('filament-loginkit::filament-loginkit.notifications.two_factor_already_disabled'))
                ->warning()
                ->send();

            return;
        }

        try {
            if ($this->user->two_factor_type === TwoFactorType::authenticator->value) {
                $disableAction = app(DisableTwoFactorAuthentication::class);
                $disableAction($this->user);
            }

            $this->user->forceFill([
                'two_factor_type' => null,
                'two_factor_code' => null,
                'two_factor_expires_at' => null,
                'two_factor_confirmed_at' => null,
            ])->save();

            $this->reset([
                'show2faSetup',
                'showQrCode',
                'showRecoveryCodes',
                'showConfirmation',
                'twoFactor',
            ]);

            Notification::make()
                ->title(__('filament-loginkit::filament-loginkit.notifications.success'))
                ->body(__('filament-loginkit::filament-loginkit.notifications.two_factor_disabled'))
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title(__('filament-loginkit::filament-loginkit.notifications.error'))
                ->body(__('filament-loginkit::filament-loginkit.notifications.two_factor_disable_failed'))
                ->danger()
                ->send();
        }
    }

    public function regenerateRecoveryCodes(): void
    {
        if ($this->user->two_factor_type !== TwoFactorType::authenticator->value) {
            Notification::make()
                ->title(__('filament-loginkit::filament-loginkit.notifications.warning'))
                ->body(__('filament-loginkit::filament-loginkit.notifications.recovery_codes_only_authenticator'))
                ->warning()
                ->send();

            return;
        }

        try {
            $generateAction = app(GenerateNewRecoveryCodes::class);
            $generateAction($this->user);

            $this->showRecoveryCodes = true;

            Notification::make()
                ->title(__('filament-loginkit::filament-loginkit.notifications.success'))
                ->body(__('filament-loginkit::filament-loginkit.notifications.recovery_codes_generated'))
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title(__('filament-loginkit::filament-loginkit.notifications.error'))
                ->body(__('filament-loginkit::filament-loginkit.notifications.recovery_codes_generation_failed'))
                ->danger()
                ->send();
        }
    }

    public function downloadRecoveryCodes(): void
    {
        if ($this->user->two_factor_type !== TwoFactorType::authenticator->value) {
            return;
        }

        $codes = $this->user->recoveryCodes();
        $content = '# ' . config('app.name') . ' - ' . __('filament-loginkit::filament-loginkit.two_factor.recovery_codes') . "\n\n";
        $content .= __('filament-loginkit::filament-loginkit.two_factor.account') . ': ' . $this->user->email . "\n";
        $content .= __('filament-loginkit::filament-loginkit.two_factor.generated_at') . ': ' . now()->format('d.m.Y H:i') . "\n\n";
        $content .= __('filament-loginkit::filament-loginkit.two_factor.warning_save_securely') . "\n";
        $content .= __('filament-loginkit::filament-loginkit.two_factor.each_code_once') . "\n\n";
        $content .= __('filament-loginkit::filament-loginkit.two_factor.recovery_codes') . ":\n";
        $content .= "==================\n\n";

        foreach ($codes as $index => $code) {
            $content .= ($index + 1) . '. ' . $code . "\n";
        }

        $this->dispatch('download-recovery-codes', [
            'filename' => 'recovery-codes-' . now()->format('Y-m-d') . '.txt',
            'content' => $content,
        ]);

        $this->showRecoveryCodes = false;

        Notification::make()
            ->title(__('filament-loginkit::filament-loginkit.notifications.success'))
            ->body(__('filament-loginkit::filament-loginkit.notifications.recovery_codes_downloaded'))
            ->success()
            ->send();
    }

    public function hideRecoveryCodes(): void
    {
        $this->showRecoveryCodes = false;
    }
}
