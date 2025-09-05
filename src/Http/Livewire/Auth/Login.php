<?php

namespace AuroraWebSoftware\FilamentLoginKit\Http\Livewire\Auth;

use App\Models\User;
use AuroraWebSoftware\FilamentLoginKit\Http\Middleware\RedirectIfTwoFactorAuthenticatable;
use AuroraWebSoftware\FilamentLoginKit\Http\Responses\LoginResponse;
use AuroraWebSoftware\FilamentLoginKit\Notifications\SmsLoginNotification;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Models\Contracts\FilamentUser;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Pipeline;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\HtmlString;
use Laravel\Fortify\Actions\AttemptToAuthenticate;
use Laravel\Fortify\Actions\CanonicalizeUsername;
use Laravel\Fortify\Actions\EnsureLoginIsNotThrottled;
use Laravel\Fortify\Actions\PrepareAuthenticatedSession;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Livewire\Features\SupportRedirects\Redirector;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

class Login extends BaseLogin
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament-loginkit::auth.login';

    public string $email = '';

    public string $password = '';

    public string $loginTab = 'email';

    public bool $showSmsCode = false;

    public string $phone_number = '';

    public string $sms_code = '';

    public int $countdown = 0;

    public bool $canResend = false;

    public string $turnstileToken = '';

    public string $turnstileTokenSms = '';

    public ?array $phoneData = [];

    public ?array $codeData = [];

    public bool $resetPasswordEnabled = true;

    public bool $registrationEnabled = false;

    public int $maxSmsAttempts;

    public int $smsAttemptDecay;

    public function mount(): void
    {
        parent::mount();

        $this->resetPasswordEnabled = Features::enabled(Features::resetPasswords());
        $this->registrationEnabled = Features::enabled(Features::registration());

        if (session('status')) {
            Notification::make()->title(session('status'))->success()->send();
        }

        $this->maxSmsAttempts = config('filament-loginkit.sms.max_wrong_attempts');
        $this->smsAttemptDecay = config('filament-loginkit.sms.wrong_attempt_decay');

        $this->getSmsPhoneForm()->fill();

    }

    public function getHeading(): string
    {
        return '';
    }

    // Login.php

    public function startCountdown(): void
    {
        $seconds = config('filament-loginkit.sms.resend_cooldown', 90);

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

    private function cacheIncrement(string $key, int $ttl): int
    {
        if (! Cache::has($key)) {
            Cache::put($key, 1, $ttl);

            return 1;
        }
        Cache::increment($key);

        return Cache::get($key);
    }

    private function isUserInactive(?User $user): bool
    {
        return $user
            && Schema::hasColumn('users', 'is_active')
            && ! (bool) $user->is_active;
    }

    //    private function ipThrottle(string $suffix): bool
    //    {
    //        if (!config('filament-loginkit.enabled_features.ip_rate_limit')) {
    //            return true;
    //        }
    //
    //        $ip = request()->ip();
    //        $settings = config('filament-loginkit.ip_limit');
    //        $key = "ip_limit:{$ip}:{$suffix}";
    //        $attempts = $this->cacheIncrement($key, $settings['decay_minutes'] * 60);
    //
    //        if ($attempts > $settings['max_attempts']) {
    //            Notification::make()
    //                ->title(__('filament-loginkit::filament-loginkit.ip.limited_title'))
    //                ->body(__('filament-loginkit::filament-loginkit.ip.limited_body'))
    //                ->danger()
    //                ->send();
    //            return false;
    //        }
    //        return true;
    //    }

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

    private function generateSmsCode(): string
    {
        $len = config('filament-loginkit.sms.code_length');

        return str_pad(random_int(0, (10 ** $len) - 1), $len, '0', STR_PAD_LEFT);
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
        if (blank($token) || ! $this->verifyTurnstile($token)) {
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

    public function getSmsPhoneForm(): Form
    {
        return $this->makeForm()
            ->schema($this->getSmsPhoneSchema())
            ->statePath('phoneData');
    }

    public function getSmsCodeForm(): Form
    {
        return $this->makeForm()
            ->schema($this->getSmsCodeSchema())
            ->statePath('codeData');
    }

    protected function getFormSchema(): array
    {
        return [
            TextInput::make('email')
                ->extraInputAttributes(['name' => 'email'])
                ->label(__('filament::login.fields.email.label'))
                ->email()
                ->required()
                ->autocomplete(),

            TextInput::make('password')
                ->extraInputAttributes(['name' => 'password'])
                ->label(__('filament::login.fields.password.label'))
                ->password()
                ->required(),

            Checkbox::make('remember')
                ->extraInputAttributes(['name' => 'remember'])
                ->label(__('filament::login.fields.remember.label')),
        ];
    }

    protected function getSmsPhoneSchema(): array
    {
        return [
            PhoneInput::make('phone_number')
                ->label(__('filament-loginkit::filament-loginkit.sms.phone_label'))
                ->initialCountry('tr')
                ->countryOrder(['tr'])
                ->strictMode()
                ->required(),
        ];
    }

    protected function getSmsCodeSchema(): array
    {
        $len = config('filament-loginkit.sms.code_length');

        return [
            TextInput::make('sms_code')
                ->label(__('filament-loginkit::filament-loginkit.sms.code_label'))
                ->placeholder(str_pad('', $len, '0'))
                ->length($len)
                ->required()
                ->rules(["digits:$len"])
                ->validationMessages([
                    'digits' => __('filament-loginkit::filament-loginkit.sms.code_format_error', [
                        'length' => $len,
                    ]),
                ])
                ->extraInputAttributes([
                    'name' => 'sms_code',
                    'maxlength' => $len,
                    'pattern' => "[0-9]{{$len}}",
                ]),
        ];
    }

    public function sendSmsCode(): void
    {
        //        if (!$this->ipThrottle('send_sms')) {
        //            return;
        //        }

        $limits = config('filament-loginkit.rate_limits.sms');

        try {
            $this->rateLimit($limits['max_requests'], $limits['per_minutes'] * 60);
        } catch (TooManyRequestsException $e) {
            $this->errorNotify('too_many_attempts', 'sms', ['seconds' => $e->secondsUntilAvailable ?? 60]);

            return;
        }

        $this->phone_number = $this->getSmsPhoneForm()->getState()['phone_number'] ?? '';
        $user = User::where('phone_number', $this->phone_number)->first();

        if (! $user) {
            $this->errorNotify('not_found', 'sms');

            return;
        }

        if ($this->isUserInactive($user)) {
            $this->errorNotify('inactive', 'sms');

            return;
        }

        $floodKey = 'sms_flood:' . md5($this->phone_number);
        $floodWindow = config('filament-loginkit.sms.flood.window_minutes');

        if ($this->cacheIncrement($floodKey, $floodWindow * 60) >
            config('filament-loginkit.sms.flood.max_per_window')) {
            $this->errorNotify('too_many_requests', 'sms');

            return;
        }

        if (config('filament-loginkit.turnstile.enabled') &&
            ! $this->verifyTurnstileOrNotify($this->turnstileTokenSms)) {
            return;
        }

        if ($user->sms_login_expires_at && now()->lessThan($user->sms_login_expires_at)) {

            $this->showSmsCode = true;
            $this->startCountdown();

            return;
        }

        $code = $this->generateSmsCode();

        $user->update([
            'sms_login_code' => Hash::make($code),
            'sms_login_expires_at' => now()->addMinutes(config('filament-loginkit.sms.code_ttl')),
        ]);

        $this->dispatchSms($user, $code);

        Notification::make()
            ->title(__('filament-loginkit::filament-loginkit.sms.sent_title'))
            ->body(__('filament-loginkit::filament-loginkit.sms.sent_body'))
            ->success()
            ->send();

        $this->showSmsCode = true;
        $this->turnstileTokenSms = '';

        $this->startCountdown();

        $this->dispatch('resetTurnstile');
    }

    public function resendSmsCode(): void
    {
        //        if (!$this->ipThrottle('resend_sms')) {
        //            return;
        //        }

        $limits = config('filament-loginkit.rate_limits.sms_resend');

        try {
            $this->rateLimit($limits['max_requests'], $limits['per_minutes'] * 60);
        } catch (TooManyRequestsException $e) {
            $this->errorNotify('resend_too_many', 'sms', ['seconds' => $e->secondsUntilAvailable ?? 60]);

            return;
        }

        if (blank($this->phone_number)) {
            $this->errorNotify('invalid_session', 'sms');

            return;
        }

        $user = User::where('phone_number', $this->phone_number)->first();

        if (! $user) {
            $this->errorNotify('generic', 'sms');

            return;
        }

        if ($this->isUserInactive($user)) {
            $this->errorNotify('inactive', 'sms');

            return;
        }

        $floodKey = 'sms_flood:' . md5($this->phone_number);

        if (Cache::get($floodKey, 0) >=
            config('filament-loginkit.sms.flood.max_per_window')) {
            $this->errorNotify('too_many_requests', 'sms');

            return;
        }

        $resendKey = 'sms_resend:' . md5($this->phone_number);
        $resendWindow = config('filament-loginkit.sms.resend.window_minutes');

        if ($this->cacheIncrement($resendKey, $resendWindow * 60) >
            config('filament-loginkit.sms.resend.max_requests')) {
            $this->errorNotify('resend_limit', 'sms', ['window_minutes' => $resendWindow]);

            return;
        }

        $code = $this->generateSmsCode();

        $user->update([
            'sms_login_code' => Hash::make($code),
            'sms_login_expires_at' => now()->addMinutes(config('filament-loginkit.sms.code_ttl')),
        ]);

        $this->dispatchSms($user, $code);

        $this->cacheIncrement(
            $floodKey,
            config('filament-loginkit.sms.flood.window_minutes') * 60
        );

        $this->startCountdown();

        Notification::make()
            ->title(__('filament-loginkit::filament-loginkit.sms.resent_title'))
            ->body(__('filament-loginkit::filament-loginkit.sms.resent_body'))
            ->success()
            ->send();
    }

    public function loginWithSms()
    {
        //        if (!$this->ipThrottle('login_sms')) {
        //            return;
        //        }

        $this->sms_code = $this->getSmsCodeForm()->getState()['sms_code'] ?? '';

        $loginSucceeded = false;
        $user = null;

        DB::transaction(function () use (&$loginSucceeded, &$user) {
            $user = User::where('phone_number', $this->phone_number)
                ->where('sms_login_expires_at', '>', now())
                ->lockForUpdate()
                ->first();

            if (! $user || ! Hash::check($this->sms_code, $user->sms_login_code)) {
                return;
            }

            $user->update([
                'sms_login_code' => null,
                'sms_login_expires_at' => null,
            ]);

            $loginSucceeded = true;
        });

        if (! $loginSucceeded) {
            $wrongKey = 'sms_wrong:' . md5($this->phone_number);
            $attempts = $this->cacheIncrement($wrongKey, $this->smsAttemptDecay);

            if ($attempts >= $this->maxSmsAttempts) {
                $this->errorNotify('max_wrong', 'sms');

                return null;
            }

            $this->errorNotify('invalid_code', 'sms');

            return null;
        }

        if ($this->isUserInactive($user)) {
            $this->errorNotify('inactive', 'sms');

            return null;
        }

        Cache::forget('sms_wrong:' . md5($this->phone_number));

        session()->put('panel', Filament::getCurrentPanel()?->getId());
        session()->put('login_type', 'sms');

        Filament::auth()->login($user, false);

        $authed = Filament::auth()->user();
        if (! Filament::getCurrentPanel()
            || ($authed instanceof FilamentUser
                && ! $authed->canAccessPanel(Filament::getCurrentPanel()))) {
            Filament::auth()->logout();
            $this->errorNotify('inactive', 'sms');
            return null;
        }

        session()->put('login_type', 'sms');

        return app(Pipeline::class)
            ->send(request())
            ->through([PrepareAuthenticatedSession::class])
            ->then(function () {
                return app(\AuroraWebSoftware\FilamentLoginKit\Http\Responses\LoginResponse::class);
            });
    }

    public function loginWithFortify(): LoginResponse | Redirector | Response | null
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

            session()->put('login_type', 'email');
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

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->extraInputAttributes(['name' => 'password', 'tabindex' => 2])
            ->label(__('filament-loginkit::filament-loginkit.form.password'))
            ->password()
            ->required()
            ->revealable(Filament::arePasswordsRevealable())
            ->autocomplete('current-password')
            ->hint(
                Filament::hasPasswordReset()
                    ? new HtmlString(Blade::render(
                        '<x-filament::link :href="filament()->getRequestPasswordResetUrl()" tabindex="3">
                            {{ __(\'filament-panels::pages/auth/login.actions.request_password_reset.label\') }}
                         </x-filament::link>'
                    ))
                    : null
            );
    }

    protected function getAuthenticateFormAction(): Action
    {
        return Action::make('authenticate')
            ->color('primary')
            ->label(__('filament-panels::pages/auth/login.form.actions.authenticate.label'))
            ->submit('authenticate');
    }

    protected function validateCredentials(array $credentials): bool
    {
        $provider = Filament::auth()->getProvider();
        $user = $provider->retrieveByCredentials($credentials);

        return $user && $provider->validateCredentials($user, $credentials);
    }

    public function backToSmsForm(): void
    {
        $this->showSmsCode = false;
        $this->sms_code = '';
        $this->codeData = [];
        $this->turnstileTokenSms = '';
        $this->resetCountdown();

        $this->dispatch('resetTurnstile');
    }
}
