<?php

namespace AuroraWebSoftware\FilamentLoginKit\Http\Livewire\Auth;

use App\Models\User;
use AuroraWebSoftware\FilamentLoginKit\Http\Middleware\RedirectIfTwoFactorAuthenticatable;
use AuroraWebSoftware\FilamentLoginKit\Notifications\SmsLoginNotification;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\MultiFactor\Contracts\HasBeforeChallengeHook;
use Filament\Auth\MultiFactor\Contracts\MultiFactorAuthenticationProvider;
use Filament\Facades\Filament;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Models\Contracts\FilamentUser;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Illuminate\Auth\Events\Failed;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Pipeline;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema as DbSchema;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\AttemptToAuthenticate;
use Laravel\Fortify\Actions\CanonicalizeUsername;
use Laravel\Fortify\Actions\EnsureLoginIsNotThrottled;
use Laravel\Fortify\Actions\PrepareAuthenticatedSession;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Livewire\Attributes\Locked;
use Livewire\Features\SupportRedirects\Redirector;
use SensitiveParameter;
use Twilio\Rest\Client;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

class Login extends SimplePage
{
    use WithRateLimiting, InteractsWithSchemas;

    public string $loginTab = 'email';

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];
    public ?array $smsPhoneData = [];

    public ?string $phone_number = null;
    public string $turnstileToken = '';
    public string $turnstileTokenSms = '';

    public int $maxSmsAttempts;
    public int $smsAttemptDecay;

    #[Locked]
    public ?string $userUndertakingMultiFactorAuthentication = null;

    public function mount(): void
    {
        if (Filament::auth()->check()) {
            redirect()->intended(Filament::getUrl());
        }

        $this->form->fill();
        $this->smsPhoneForm->fill();

        $this->maxSmsAttempts = config('filament-loginkit.sms.max_wrong_attempts');
        $this->smsAttemptDecay = config('filament-loginkit.sms.wrong_attempt_decay');
    }

    public function authenticate(): LoginResponse | Redirector | Response | null
    {
        $limits = config('filament-loginkit.rate_limits.login');

        try {
            $this->rateLimit($limits['max_requests'], $limits['per_minutes'] * 60);
        } catch (TooManyRequestsException $e) {
            $this->errorNotify('too_many_attempts', 'email', ['seconds' => $e->secondsUntilAvailable ?? 60]);

            return null;
        }

        if (config('filament-loginkit.turnstile.enabled') &&
            ! $this->verifyTurnstileOrNotify($this->turnstileToken)) {
            return null;
        }

        $data = $this->form->getState();
        if (! $this->validateCredentials($this->getCredentialsFromFormData($data))) {
            $this->errorNotify('invalid_credentials');
            $this->dispatch('resetTurnstile');

            return null;
        }

        $candidate = User::where('email', $data['email'] ?? null)->first();
        if ($this->isUserInactive($candidate)) {
            $this->errorNotify('inactive', 'email');
            $this->dispatch('resetTurnstile');

            return null;
        }

        $req = request()->merge($data);

        session()->put('login_type', 'email');

        return $this->loginPipeline($req)->then(function () use ($data) {

            if (! Filament::auth()->attempt(
                $this->getCredentialsFromFormData($data),
                $data['remember'] ?? false
            )) {
                $this->throwFailureValidationException();
            }

            $user = Filament::auth()->user();

            if ($this->isUserInactive($user)) {
                Filament::auth()->logout();
                $this->errorNotify('inactive', 'email');
                $this->throwFailureValidationException();
            }

            if (! Filament::getCurrentPanel() ||
                ($user instanceof FilamentUser &&
                    ! $user->canAccessPanel(Filament::getCurrentPanel()))) {
                Filament::auth()->logout();
                $this->throwFailureValidationException();
            }

            session()->regenerate();

            return app(LoginResponse::class);
        });
    }

    protected function loginPipeline(Request $request): Pipeline
    {
        if (Fortify::$authenticateThroughCallback) {
            return (new Pipeline(app()))
                ->send($request)
                ->through(array_filter(
                    call_user_func(Fortify::$authenticateThroughCallback, $request)
                ));
        }

        if (is_array(config('fortify.pipelines.login'))) {
            return (new Pipeline(app()))
                ->send($request)
                ->through(array_filter(config('fortify.pipelines.login')));
        }

        return (new Pipeline(app()))
            ->send($request)
            ->through(array_filter([
                config('fortify.limiters.login') ? null : EnsureLoginIsNotThrottled::class,
                config('fortify.lowercase_usernames') ? CanonicalizeUsername::class : null,
                Features::enabled(Features::twoFactorAuthentication())
                    ? RedirectIfTwoFactorAuthenticatable::class
                    : null,
                AttemptToAuthenticate::class,
                PrepareAuthenticatedSession::class,
            ]));
    }

    protected function validateCredentials(array $credentials): bool
    {
        $provider = Filament::auth()->getProvider();
        $user = $provider->retrieveByCredentials($credentials);

        return $user && $provider->validateCredentials($user, $credentials);
    }

//    public function authenticate(): ?LoginResponse
//    {
//        try {
//            $this->rateLimit(5);
//        } catch (TooManyRequestsException $exception) {
//            $this->getRateLimitedNotification($exception)?->send();
//
//            return null;
//        }
//
//        if (config('filament-loginkit.turnstile.enabled') &&
//            !$this->verifyTurnstileOrNotify($this->turnstileToken)) {
//            return null;
//        }
//
//        $data = $this->form->getState();
//
//        /** @var SessionGuard $authGuard */
//        $authGuard = Filament::auth();
//
//        $authProvider = $authGuard->getProvider();
//        /** @phpstan-ignore-line */
//        $credentials = $this->getCredentialsFromFormData($data);
//
//        $user = $authProvider->retrieveByCredentials($credentials);
//
//        if ((!$user) || (!$authProvider->validateCredentials($user, $credentials))) {
//            $this->userUndertakingMultiFactorAuthentication = null;
//
//            $this->fireFailedEvent($authGuard, $user, $credentials);
//            $this->throwFailureValidationException();
//        }
//
//        if ($this->isUserInactive($user)) {
//            $this->errorNotify('inactive', 'email');
//            $this->resetTurnstile();
//            $this->throwFailureValidationException();
//        }
//
//        session()->put('login_type', 'email');
//
//        if (
//            filled($this->userUndertakingMultiFactorAuthentication) &&
//            (decrypt($this->userUndertakingMultiFactorAuthentication) === $user->getAuthIdentifier())
//        ) {
//            $this->multiFactorChallengeForm->validate();
//        } else {
//            foreach (Filament::getMultiFactorAuthenticationProviders() as $multiFactorAuthenticationProvider) {
//                if (!$multiFactorAuthenticationProvider->isEnabled($user)) {
//                    continue;
//                }
//
//                $this->userUndertakingMultiFactorAuthentication = encrypt($user->getAuthIdentifier());
//
//                if ($multiFactorAuthenticationProvider instanceof HasBeforeChallengeHook) {
//                    $multiFactorAuthenticationProvider->beforeChallenge($user);
//                }
//
//                break;
//            }
//
//            if (filled($this->userUndertakingMultiFactorAuthentication)) {
//                $this->multiFactorChallengeForm->fill();
//
//                return null;
//            }
//        }
//
//        if (!$authGuard->attemptWhen($credentials, function (Authenticatable $user): bool {
//            if (!($user instanceof FilamentUser)) {
//                return true;
//            }
//
//            return $user->canAccessPanel(Filament::getCurrentOrDefaultPanel());
//        }, $data['remember'] ?? false)) {
//            $this->fireFailedEvent($authGuard, $user, $credentials);
//            $this->throwFailureValidationException();
//        }
//
//        session()->regenerate();
//
//        return app(LoginResponse::class);
//    }

    protected function getRateLimitedNotification(TooManyRequestsException $exception): ?Notification
    {
        return Notification::make()
            ->title(__('filament-panels::auth/pages/login.notifications.throttled.title', [
                'seconds' => $exception->secondsUntilAvailable,
                'minutes' => $exception->minutesUntilAvailable,
            ]))
            ->body(array_key_exists('body', __('filament-panels::auth/pages/login.notifications.throttled') ?: []) ? __('filament-panels::auth/pages/login.notifications.throttled.body', [
                'seconds' => $exception->secondsUntilAvailable,
                'minutes' => $exception->minutesUntilAvailable,
            ]) : null)
            ->danger();
    }

    /**
     * @param array<string, mixed> $credentials
     */
    protected function fireFailedEvent(Guard $guard, ?Authenticatable $user, #[SensitiveParameter] array $credentials): void
    {
        event(app(Failed::class, ['guard' => property_exists($guard, 'name') ? $guard->name : '', 'user' => $user, 'credentials' => $credentials]));
    }

    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.email' => __('filament-panels::auth/pages/login.messages.failed'),
        ]);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema
            ->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getRememberFormComponent(),
            ]);
    }

    public function defaultMultiFactorChallengeForm(Schema $schema): Schema
    {
        return $schema
            ->components(function (): array {
                if (blank($this->userUndertakingMultiFactorAuthentication)) {
                    return [];
                }

                $authProvider = Filament::auth()->getProvider();
                /** @phpstan-ignore-line */
                $user = $authProvider->retrieveById(decrypt($this->userUndertakingMultiFactorAuthentication));

                $enabledMultiFactorAuthenticationProviders = array_filter(
                    Filament::getMultiFactorAuthenticationProviders(),
                    fn(MultiFactorAuthenticationProvider $multiFactorAuthenticationProvider): bool => $multiFactorAuthenticationProvider->isEnabled($user)
                );

                return [
                    ...Arr::wrap($this->getMultiFactorProviderFormComponent()),
                    ...collect($enabledMultiFactorAuthenticationProviders)
                        ->map(fn(MultiFactorAuthenticationProvider $multiFactorAuthenticationProvider): Component => Group::make($multiFactorAuthenticationProvider->getChallengeFormComponents($user))
                            ->statePath($multiFactorAuthenticationProvider->getId())
                            ->when(
                                count($enabledMultiFactorAuthenticationProviders) > 1,
                                fn(Group $group) => $group->visible(fn(Get $get): bool => $get('provider') === $multiFactorAuthenticationProvider->getId())
                            ))
                        ->all(),
                ];
            })
            ->statePath('data.multiFactor');
    }

    public function multiFactorChallengeForm(Schema $schema): Schema
    {
        return $schema;
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label(__('filament-panels::auth/pages/login.form.email.label'))
            ->email()
            ->required()
            ->autocomplete()
            ->autofocus()
            ->extraInputAttributes(['tabindex' => 1]);
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label(__('filament-panels::auth/pages/login.form.password.label'))
            ->hint(filament()->hasPasswordReset() ? new HtmlString(Blade::render('<x-filament::link :href="filament()->getRequestPasswordResetUrl()" tabindex="3"> {{ __(\'filament-panels::auth/pages/login.actions.request_password_reset.label\') }}</x-filament::link>')) : null)
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->autocomplete('current-password')
            ->required()
            ->extraInputAttributes(['tabindex' => 2]);
    }

    protected function getRememberFormComponent(): Component
    {
        return Checkbox::make('remember')
            ->label(__('filament-panels::auth/pages/login.form.remember.label'));
    }

    protected function getMultiFactorProviderFormComponent(): ?Component
    {
        $authProvider = Filament::auth()->getProvider();
        /** @phpstan-ignore-line */
        $user = $authProvider->retrieveById(decrypt($this->userUndertakingMultiFactorAuthentication));

        $enabledMultiFactorAuthenticationProviders = array_filter(
            Filament::getMultiFactorAuthenticationProviders(),
            fn(MultiFactorAuthenticationProvider $multiFactorAuthenticationProvider): bool => $multiFactorAuthenticationProvider->isEnabled($user)
        );

        if (count($enabledMultiFactorAuthenticationProviders) <= 1) {
            return null;
        }

        return Section::make()
            ->compact()
            ->secondary()
            ->schema(fn(Section $section): array => [
                Radio::make('provider')
                    ->label(__('filament-panels::auth/pages/login.multi_factor.form.provider.label'))
                    ->options(array_map(
                        fn(MultiFactorAuthenticationProvider $multiFactorAuthenticationProvider): string => $multiFactorAuthenticationProvider->getLoginFormLabel(),
                        $enabledMultiFactorAuthenticationProviders,
                    ))
                    ->live()
                    ->afterStateUpdated(function (?string $state) use ($enabledMultiFactorAuthenticationProviders, $section, $user): void {
                        $provider = $enabledMultiFactorAuthenticationProviders[$state] ?? null;

                        if (!$provider) {
                            return;
                        }

                        $section
                            ->getContainer()
                            ->getComponent($provider->getId())
                            ->getChildSchema()
                            ->fill();

                        if (!($provider instanceof HasBeforeChallengeHook)) {
                            return;
                        }

                        $provider->beforeChallenge($user);
                    })
                    ->default(array_key_first($enabledMultiFactorAuthenticationProviders))
                    ->required()
                    ->markAsRequired(false),
            ]);
    }

    public function registerAction(): Action
    {
        return Action::make('register')
            ->link()
            ->label(__('filament-panels::auth/pages/login.actions.register.label'))
            ->url(filament()->getRegistrationUrl());
    }

    public function getTitle(): string|Htmlable
    {
        return __('filament-panels::auth/pages/login.title');
    }

    public function getHeading(): string|Htmlable
    {
        if (filled($this->userUndertakingMultiFactorAuthentication)) {
            return __('filament-panels::auth/pages/login.multi_factor.heading');
        }

        return __('filament-panels::auth/pages/login.heading');
    }

    /**
     * @return array<Action | ActionGroup>
     */
    protected function getFormActions(): array
    {
        return [
            $this->getAuthenticateFormAction(),
        ];
    }

    protected function getAuthenticateFormAction(): Action
    {
        return Action::make('authenticate')
            ->label(__('filament-panels::auth/pages/login.form.actions.authenticate.label'))
            ->submit('authenticate');
    }

    /**
     * @return array<Action | ActionGroup>
     */
    protected function getMultiFactorChallengeFormActions(): array
    {
        return [
            $this->getMultiFactorAuthenticateFormAction(),
        ];
    }

    protected function getMultiFactorAuthenticateFormAction(): Action
    {
        return Action::make('authenticate')
            ->label(__('filament-panels::auth/pages/login.multi_factor.form.actions.authenticate.label'))
            ->submit('authenticate');
    }

    protected function hasFullWidthFormActions(): bool
    {
        return true;
    }

    protected function hasFullWidthMultiFactorChallengeFormActions(): bool
    {
        return $this->hasFullWidthFormActions();
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function getCredentialsFromFormData(#[SensitiveParameter] array $data): array
    {
        return [
            'email' => $data['email'],
            'password' => $data['password'],
        ];
    }

    public function getSubheading(): string|Htmlable|null
    {
        if (filled($this->userUndertakingMultiFactorAuthentication)) {
            return __('filament-panels::auth/pages/login.multi_factor.subheading');
        }

        if (!filament()->hasRegistration()) {
            return null;
        }

        return new HtmlString(__('filament-panels::auth/pages/login.actions.register.before') . ' ' . $this->registerAction->toHtml());
    }

    public function getFormContentComponent(): Component
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('form')
            ->livewireSubmitHandler('authenticate')
            ->footer([
                Actions::make($this->getFormActions())
                    ->alignment($this->getFormActionsAlignment())
                    ->fullWidth($this->hasFullWidthFormActions())
                    ->key('form-actions'),
            ])
            ->visible(fn(): bool => blank($this->userUndertakingMultiFactorAuthentication));
    }

    public function getMultiFactorChallengeFormContentComponent(): Component
    {
        return Form::make([EmbeddedSchema::make('multiFactorChallengeForm')])
            ->id('multiFactorChallengeForm')
            ->livewireSubmitHandler('authenticate')
            ->footer([
                Actions::make($this->getMultiFactorChallengeFormActions())
                    ->alignment($this->getMultiFactorChallengeFormActionsAlignment())
                    ->fullWidth($this->hasFullWidthMultiFactorChallengeFormActions()),
            ])
            ->visible(fn(): bool => filled($this->userUndertakingMultiFactorAuthentication));
    }

    public function getMultiFactorChallengeFormActionsAlignment(): string|Alignment
    {
        return $this->getFormActionsAlignment();
    }

    public function smsPhoneForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                PhoneInput::make('phone_number')
                    ->label(__('filament-loginkit::filament-loginkit.sms.phone_label'))
                    ->initialCountry('tr')
                    ->countryOrder(['tr'])
                    ->strictMode()
                    ->required(),
            ])
            ->statePath('smsPhoneData');
    }

    private function cacheIncrement(string $key, int $ttl): int
    {
        if (!Cache::has($key)) {
            Cache::put($key, 1, $ttl);
            return 1;
        }
        Cache::increment($key);
        return Cache::get($key);
    }

    private function isUserInactive(?User $user): bool
    {
        return $user
            && DbSchema::hasColumn('users', 'is_active')
            && !(bool)$user->is_active;
    }

    private function generateSmsCode(): string
    {
        $len = config('filament-loginkit.sms.code_length', 6);
        return str_pad(random_int(0, (10 ** $len) - 1), $len, '0', STR_PAD_LEFT);
    }

    private function verifyTurnstile(string $token): bool
    {
        try {
            $res = Http::timeout(10)
                ->asForm()
                ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                    'secret' => config('filament-loginkit.turnstile.secret'),
                    'response' => $token,
                    'remoteip' => request()->ip(),
                ]);

            return $res->successful() && ($res->json()['success'] ?? false);
        } catch (\Throwable $e) {
            Log::error('Turnstile HTTP error', ['msg' => $e->getMessage()]);
            return false;
        }
    }

    private function verifyTurnstileOrNotify(?string $token, string $property = 'turnstileToken'): bool
    {
        if (blank($token) || !$this->verifyTurnstile($token)) {
            $this->resetTurnstile($property);

            Notification::make()
                ->title(__('filament-loginkit::filament-loginkit.sms.captcha_failed_title'))
                ->body(__('filament-loginkit::filament-loginkit.sms.captcha_failed_body'))
                ->danger()
                ->send();

            return false;
        }

        return true;
    }

    private function resetTurnstile(string $property = 'turnstileToken'): void
    {
        $this->{$property} = '';
        $this->dispatch('resetTurnstile');
    }

    private function errorNotify(string $key, string $type = 'email', array $params = []): void
    {
        if (config('filament-loginkit.enabled_features.generic_errors')) {
            Notification::make()
                ->title(__('filament-loginkit::filament-loginkit.generic_fail_title'))
                ->body(__('filament-loginkit::filament-loginkit.generic_fail_body'))
                ->danger()
                ->send();
            return;
        }

        Notification::make()
            ->title(__("filament-loginkit::filament-loginkit.{$type}.{$key}_title"))
            ->body(__("filament-loginkit::filament-loginkit.{$type}.{$key}_body", $params))
            ->danger()
            ->send();
    }


    public function sendSmsCode()
    {
        $limits = config('filament-loginkit.rate_limits.sms');

        try {
            $this->rateLimit($limits['max_requests'], $limits['per_minutes'] * 60);
        } catch (TooManyRequestsException $e) {
            Notification::make()
                ->title(__('filament-loginkit::filament-loginkit.sms.too_many_attempts_title'))
                ->body(__('filament-loginkit::filament-loginkit.sms.too_many_attempts_body', [
                    'seconds' => $e->secondsUntilAvailable ?? 60,
                ]))
                ->danger()
                ->send();

            return;
        }

        $this->phone_number = $this->smsPhoneData['phone_number'] ?? null;

        $user = User::where('phone_number', $this->phone_number)->first();

        if (!$user) {
            $this->errorNotify('not_found', 'sms');

            return;
        }

        if ($this->isUserInactive($user)) {
            $this->errorNotify('inactive', 'sms');

            return;
        }

        $floodKey = 'sms_flood:' . md5($this->phone_number);
        $floodWindow = (int)config('filament-loginkit.sms.flood.window_minutes');
        if ($this->cacheIncrement($floodKey, $floodWindow * 60) >
            (int)config('filament-loginkit.sms.flood.max_per_window')) {
            $this->errorNotify('too_many_requests', 'sms');

            return;
        }

        if (config('filament-loginkit.turnstile.enabled') &&
            !$this->verifyTurnstileOrNotify($this->turnstileTokenSms)) {
            return;
        }

        $panelPath = Filament::getDefaultPanel()?->getPath()
            ?? config('filament.path', 'filament');
        session()->put('flk_panel_id', Filament::getCurrentPanel()?->getId());
        session()->put('flk_sms_phone', $this->phone_number);
        session()->put('flk_login_type', 'sms');

        if ($user->sms_login_expires_at && now()->lessThan($user->sms_login_expires_at)) {
            $this->turnstileTokenSms = '';
            $this->dispatch('resetTurnstile');

            return $this->redirect(url($panelPath . '/sms-verify'), navigate: false);
        }

        $code = $this->generateSmsCode();

        $user->update([
            'sms_login_code' => \Illuminate\Support\Facades\Hash::make($code),
            'sms_login_expires_at' => now()->addMinutes((int)config('filament-loginkit.sms.code_ttl')),
        ]);

        $this->dispatchSms($user, $code);

        Notification::make()
            ->title(__('filament-loginkit::filament-loginkit.sms.sent_title'))
            ->body(__('filament-loginkit::filament-loginkit.sms.sent_body'))
            ->success()
            ->send();

        $this->turnstileTokenSms = '';
        $this->dispatch('resetTurnstile');

        return $this->redirect(url($panelPath . '/sms-verify'), navigate: false);
    }


    private function dispatchSms(User $user, string $code): void
    {
        $locale = app()->getLocale() ?? config('app.locale', 'tr');
        app()->setLocale($locale);

        $notification = new SmsLoginNotification($code, null, null, $locale);
        $notification->locale($locale);

        if (config('filament-loginkit.queue_notifications')) {
            $user->notify($notification);
        } else {
            $user->notifyNow($notification);
        }
    }

    private function dispatchWhatsapp($user, string $code): void
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
        if (!str_starts_with($from, 'whatsapp:')) {
            $from = 'whatsapp:' . (str_starts_with($from, '+') ? $from : '+' . $from);
        }

        $client->messages->create($to, [
            'from' => $from,
            'contentSid' => $tplSid,
            'contentVariables' => json_encode([1 => $code]),
        ]);
    }

    public function sendWhatsappCode()
    {
        $limits = config('filament-loginkit.rate_limits.sms');

        try {
            $this->rateLimit($limits['max_requests'], $limits['per_minutes'] * 60);
        } catch (TooManyRequestsException $e) {
            $this->errorNotify('too_many_attempts', 'sms', ['seconds' => $e->secondsUntilAvailable ?? 60]);

            return;
        }

        $this->phone_number = $this->smsPhoneData['phone_number'] ?? null;

        if (blank($this->phone_number)) {
            $this->errorNotify('not_found', 'sms');

            return;
        }

        $user = \App\Models\User::where('phone_number', $this->phone_number)->first();
        if (! $user) {
            $this->errorNotify('not_found', 'sms');

            return;
        }

        if ($this->isUserInactive($user)) {
            $this->errorNotify('inactive', 'sms');

            return;
        }

        $floodKey = 'wa_flood:' . md5($this->phone_number);
        $floodWindow = (int) config('filament-loginkit.sms.flood.window_minutes');
        if ($this->cacheIncrement($floodKey, $floodWindow * 60) >
            (int) config('filament-loginkit.sms.flood.max_per_window')) {
            $this->errorNotify('too_many_requests', 'sms');

            return;
        }

        if (config('filament-loginkit.turnstile.enabled') &&
            ! $this->verifyTurnstileOrNotify($this->turnstileTokenSms, 'turnstileTokenSms')) {
            return;
        }

        $panelPath = Filament::getDefaultPanel()?->getPath()
            ?? config('filament.path', 'filament');
        session()->put('flk_panel_id', Filament::getCurrentPanel()?->getId());
        session()->put('flk_sms_phone', $this->phone_number);
        session()->put('flk_login_type', 'whatsapp');

        if ($user->whatsapp_login_expires_at && now()->lessThan($user->whatsapp_login_expires_at)) {
            $this->turnstileTokenSms = '';
            $this->dispatch('resetTurnstile');

            return $this->redirect(url($panelPath . '/sms-verify'), navigate: false);
        }

        $code = $this->generateSmsCode();

        $user->update([
            'whatsapp_login_code' => \Illuminate\Support\Facades\Hash::make($code),
            'whatsapp_login_expires_at' => now()->addMinutes((int) config('filament-loginkit.sms.code_ttl')),
        ]);

        try {
            $this->dispatchWhatsapp($user, $code);
        } catch (\Throwable $e) {
            Log::error('WhatsApp dispatch failed', ['msg' => $e->getMessage()]);
            $this->errorNotify('send_failed', 'sms');

            return;
        }

        Notification::make()
            ->title(__('filament-loginkit::filament-loginkit.sms.sent_title'))
            ->body(__('filament-loginkit::filament-loginkit.sms.sent_body'))
            ->success()
            ->send();

        $this->turnstileTokenSms = '';
        $this->dispatch('resetTurnstile');

        return $this->redirect(url($panelPath . '/sms-verify'), navigate: false);
    }

}

