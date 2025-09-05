<x-filament-panels::page.simple>
    @php
        $emailEnabled = config('filament-loginkit.email_login');
        $smsEnabled = config('filament-loginkit.sms_login');
    @endphp

    @if (filament()->hasRegistration())
        <x-slot name="subheading">
            {{ __('filament-panels::pages/auth/login.actions.register.before') }}
            {{ $this->registerAction }}
        </x-slot>
    @endif

    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE, scopes: $this->getRenderHookScopes()) }}

    <div class="space-y-8">
        {{-- Tab Yapısı --}}
        @if ($emailEnabled && $smsEnabled)
            @if (!$showSmsCode)
                <div class="fi-tabs flex overflow-x-auto mb-5">
                    <div class="grid w-full grid-cols-2 gap-x-1 rounded-xl bg-gray-50 p-1 dark:bg-white/5">
                        <button
                            type="button"
                            wire:click="$set('loginTab', 'email')"
                            @class([
                                'flex items-center justify-center gap-x-2 rounded-lg px-3 py-2.5 text-sm font-semibold transition-all duration-200',
                                'bg-white text-gray-950 shadow-sm ring-1 ring-gray-950/10 dark:bg-white/10 dark:text-white dark:ring-white/20' => $loginTab === 'email',
                                'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' => $loginTab !== 'email',
                            ])
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            <span>{{ __('filament-loginkit::filament-loginkit.email_login_tab') }}</span>
                        </button>

                        <button
                            type="button"
                            wire:click="$set('loginTab', 'sms')"
                            @class([
                                'flex items-center justify-center gap-x-2 rounded-lg px-3 py-2.5 text-sm font-semibold transition-all duration-200',
                                'bg-white text-gray-950 shadow-sm ring-1 ring-gray-950/10 dark:bg-white/10 dark:text-white dark:ring-white/20' => $loginTab === 'sms',
                                'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' => $loginTab !== 'sms',
                            ])
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                            <span>{{ __('filament-loginkit::filament-loginkit.sms_login_tab') }}</span>
                        </button>
                    </div>
                </div>
            @endif
        @endif

        <div class="space-y-6">
            {{-- E-posta Giriş Formu --}}
            @if ($emailEnabled && (!$smsEnabled || ($smsEnabled && $loginTab === 'email')) && !$showSmsCode)
                <div>
                    <form wire:submit="loginWithFortify">
                        <div class="mt-5 mb-6">
                            {{ $this->form }}
                        </div>

                        @if (config('filament-loginkit.turnstile.enabled'))
                            <div class="pb-5 flex justify-center" wire:ignore>
                                <div id="turnstile-widget"></div>
                            </div>
                        @endif

                        <div class="">
                            <x-filament::button
                                type="submit"
                                class="w-full"
                                color="primary"
                                wire:loading.attr="disabled"
                            >
                                <x-filament::loading-indicator
                                    class="h-4 w-4 mr-2"
                                    wire:loading
                                    wire:target="loginWithFortify"
                                />
                                {{ __('filament-panels::pages/auth/login.form.actions.authenticate.label') }}
                            </x-filament::button>
                        </div>
                    </form>
                </div>
            @endif

            @if ($smsEnabled && (!$emailEnabled || ($emailEnabled && $loginTab === 'sms')) && !$showSmsCode)
                <div>
                    <div class="space-y-6">
                        <form wire:submit.prevent="sendSmsCode">
                            <div class="mt-5 mb-6">
                                {{ $this->getSmsPhoneForm() }}
                            </div>

                            @if (config('filament-loginkit.turnstile.enabled'))
                                <div class="pt-2 pb-4 flex justify-center" wire:ignore>
                                    <div id="turnstile-widget-sms"></div>
                                </div>
                            @endif

                            <div class="">
                                <x-filament::button
                                    type="submit"
                                    color="primary"
                                    class="w-full"
                                    wire:loading.attr="disabled"
                                >
                                    <x-filament::loading-indicator
                                        class="h-4 w-4 mr-2"
                                        wire:loading
                                        wire:target="sendSmsCode"
                                    />
                                    {{ __('filament-loginkit::filament-loginkit.sms.login') }}
                                </x-filament::button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

            @if ($showSmsCode)
                <div>
                    <div class="space-y-6">
                        <div class="text-center">
                            <div
                                class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/20">
                                <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none"
                                     stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <h3 class="mt-3 text-lg font-semibold text-gray-900 dark:text-white">
                                {{ __('filament-loginkit::filament-loginkit.sms.code_title') }}
                            </h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                {{ __('filament-loginkit::filament-loginkit.sms.code_instruction', [
                                    'number' => $this->phone_number,
                                    'length' => config('filament-loginkit.sms.code_length')
                                ]) }}
                            </p>
                        </div>

                        <form wire:submit.prevent="loginWithSms" class="space-y-4">
                            <div>
                                {{ $this->getSmsCodeForm() }}
                            </div>

                            {{-- Action Buttons --}}
                            <div class="grid grid-cols-2 gap-3">
                                <x-filament::button
                                    type="button"
                                    wire:click="backToSmsForm"
                                    color="gray"
                                    outlined
                                    class="w-full"
                                >
                                    <x-slot name="icon">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                                        </svg>
                                    </x-slot>
                                    {{ __('filament-loginkit::filament-loginkit.sms.back') }}
                                </x-filament::button>

                                <x-filament::button
                                    type="submit"
                                    color="primary"
                                    class="w-full"
                                    wire:loading.attr="disabled"
                                >
                                    <x-filament::loading-indicator
                                        class="h-4 w-4 mr-2"
                                        wire:loading
                                        wire:target="loginWithSms"
                                    />
                                    {{ __('filament-loginkit::filament-loginkit.sms.verify') }}
                                </x-filament::button>
                            </div>
                        </form>

                        <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                            <div class="flex items-center justify-center space-x-2 text-sm">
                                <span class="text-gray-500 dark:text-gray-400">
                                    {{ __('filament-loginkit::filament-loginkit.sms.not_received') }}
                                </span>

                                @if ($canResend)
                                    <x-filament::button
                                        type="button"
                                        wire:click="resendSmsCode"
                                        color="primary"
                                        size="sm"
                                        outlined
                                        wire:loading.attr="disabled"
                                        wire:target="resendSmsCode"
                                    >
                                        <x-filament::loading-indicator
                                            class="h-3 w-3 mr-1"
                                            wire:loading
                                            wire:target="resendSmsCode"
                                        />
                                        {{ __('filament-loginkit::filament-loginkit.sms.resend') }}
                                    </x-filament::button>
                                @else
                                    <div class="flex items-center space-x-2">
                                        <div
                                            class="h-4 w-4 animate-spin rounded-full border-2 border-primary-600 border-t-transparent"></div>
                                        <span class="text-primary-600 dark:text-primary-400 font-medium">
                                            {{ $countdown }}s
                                        </span>
                                    </div>
                                @endif
                            </div>

                            <p class="mt-2 text-xs text-center text-gray-400 dark:text-gray-500">
                                {{ __('filament-loginkit::filament-loginkit.sms.code_expires', [
                                    'minutes' => config('filament-loginkit.sms.code_ttl')
                                ]) }}
                            </p>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_AFTER, scopes: $this->getRenderHookScopes()) }}

    @push('scripts')
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
        <script>
            let turnstileInstances = {};

            function getTurnstileTheme() {
                const htmlElement = document.documentElement;
                const isDark = htmlElement.classList.contains('dark') ||
                    htmlElement.getAttribute('data-theme') === 'dark' ||
                    getComputedStyle(htmlElement).getPropertyValue('color-scheme').includes('dark');
                return isDark ? 'dark' : 'light';
            }

            function clearAllTurnstileWidgets() {
                Object.keys(turnstileInstances).forEach(widgetId => {
                    const container = document.getElementById(widgetId);
                    if (container) {
                        container.innerHTML = '';
                    }
                });
                turnstileInstances = {};
            }

            function renderTurnstileWidget(containerId, tokenProperty = 'turnstileToken') {
                const container = document.getElementById(containerId);

                if (!container || !window.turnstile) {
                    return;
                }

                if (container.offsetParent === null) {
                    return;
                }

                if (turnstileInstances[containerId]) {
                    return;
                }

                const theme = getTurnstileTheme();

                try {
                    container.innerHTML = '';

                    const widgetId = window.turnstile.render(container, {
                        sitekey: "{{ config('filament-loginkit.turnstile.sitekey') }}",
                        theme: theme,
                        size: 'normal',
                        callback: function (token) {
                            @this.
                            set(tokenProperty, token);
                        },
                        'error-callback': function () {
                            @this.
                            set(tokenProperty, '');
                        },
                        'expired-callback': function () {
                            @this.
                            set(tokenProperty, '');
                        }
                    });

                    turnstileInstances[containerId] = widgetId;
                } catch (error) {
                    console.error(`Error rendering turnstile widget ${containerId}:`, error);
                }
            }

            function renderAllTurnstileWidgets() {
                if (document.getElementById('turnstile-widget')) {
                    renderTurnstileWidget('turnstile-widget', 'turnstileToken');
                }
                if (document.getElementById('turnstile-widget-sms')) {
                    renderTurnstileWidget('turnstile-widget-sms', 'turnstileTokenSms');
                }
                if (document.getElementById('turnstile-widget-verify')) {
                    renderTurnstileWidget('turnstile-widget-verify', 'turnstileTokenVerify');
                }
            }

            function initializeTurnstile() {
                if (!window.turnstile) {
                    setTimeout(initializeTurnstile, 100);
                    return;
                }
                setTimeout(() => {
                    renderAllTurnstileWidgets();
                }, 200);
            }

            function observeThemeChanges() {
                const observer = new MutationObserver(function (mutations) {
                    mutations.forEach(function (mutation) {
                        if (mutation.type === 'attributes' &&
                            (mutation.attributeName === 'class' || mutation.attributeName === 'data-theme')) {
                            clearAllTurnstileWidgets();
                            setTimeout(renderAllTurnstileWidgets, 300);
                        }
                    });
                });

                observer.observe(document.documentElement, {
                    attributes: true,
                    attributeFilter: ['class', 'data-theme']
                });
            }

            let countdownInterval = null;

            document.addEventListener('start-countdown', function (e) {
                startCountdownTimer(e.detail.seconds ?? 90);
            });

            function startCountdownTimer(seconds) {
                if (countdownInterval) clearInterval(countdownInterval);

                let timeLeft = parseInt(seconds, 10);

                @this.
                set('countdown', timeLeft);
                @this.
                set('canResend', false);

                countdownInterval = setInterval(() => {
                    timeLeft--;
                    @this.
                    set('countdown', timeLeft);

                    if (timeLeft <= 0) {
                        clearInterval(countdownInterval);
                        countdownInterval = null;
                        @this.
                        set('canResend', true);
                        @this.
                        set('countdown', 0);
                    }
                }, 1000);
            }

            document.addEventListener('DOMContentLoaded', function () {
                initializeTurnstile();
                observeThemeChanges();
            });

            document.addEventListener('livewire:init', function () {
                setTimeout(() => {
                    initializeTurnstile();
                }, 100);
            });

            document.addEventListener('livewire:navigated', function () {
                clearAllTurnstileWidgets();
                if (countdownInterval) {
                    clearInterval(countdownInterval);
                    countdownInterval = null;
                }
                setTimeout(() => {
                    initializeTurnstile();
                }, 100);
            });

            document.addEventListener('livewire:updated', function () {
                setTimeout(() => {
                    renderAllTurnstileWidgets();
                }, 50);
            });

            document.addEventListener('click', function (event) {
                if (event.target.closest('[wire\\:click*="loginTab"]')) {
                    setTimeout(() => {
                        clearAllTurnstileWidgets();
                        renderAllTurnstileWidgets();
                    }, 200);
                }
            });

            window.addEventListener('resetTurnstile', function () {
                clearAllTurnstileWidgets();
                setTimeout(renderAllTurnstileWidgets, 100);
            });

            window.addEventListener('beforeunload', function () {
                if (countdownInterval) {
                    clearInterval(countdownInterval);
                }
            });
        </script>
    @endpush

</x-filament-panels::page.simple>
