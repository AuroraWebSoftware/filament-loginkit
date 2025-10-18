<x-filament-panels::page.simple>
    @php
        $emailEnabled = config('filament-loginkit.email_login');
        $smsEnabled = config('filament-loginkit.sms_login');
        $twilioEnabled = config('filament-loginkit.twilio.enabled');
    @endphp

    @if (filament()->hasRegistration())
        <x-slot name="subheading">
            {{ __('filament-loginkit::filament-loginkit.or') }}
            {{ $this->registerAction }}
        </x-slot>
    @endif

    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE, scopes: $this->getRenderHookScopes()) }}

    <div class="space-y-8">
        {{-- Tab Yapısı --}}
        @if ($emailEnabled && $smsEnabled)
            <div class="flex mb-5">
                <div class="grid w-full grid-cols-2 gap-x-1 rounded-xl bg-gray-100 p-1 dark:bg-gray-800">
                    <button
                        type="button"
                        wire:click="$set('loginTab', 'email')"
                        @class([
                            'flex items-center justify-center gap-x-2 rounded-lg px-3 py-2.5 font-semibold transition-all duration-200',
                            'text-xs sm:text-sm',
                            'bg-white text-gray-900 shadow-sm ring-1 ring-gray-200 dark:bg-gray-900 dark:text-white dark:ring-gray-700' => $loginTab === 'email',
                            'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' => $loginTab !== 'email',
                        ])
                    >
                        <svg class="h-4 w-4 sm:h-5 sm:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <span>{{ __('filament-loginkit::filament-loginkit.email_login_tab') }}</span>
                    </button>

                    <button
                        type="button"
                        wire:click="$set('loginTab', 'sms')"
                        @class([
                            'flex items-center justify-center gap-x-2 rounded-lg px-3 py-2.5 font-semibold transition-all duration-200',
                            'text-xs sm:text-sm',
                            'bg-white text-gray-900 shadow-sm ring-1 ring-gray-200 dark:bg-gray-900 dark:text-white dark:ring-gray-700' => $loginTab === 'sms',
                            'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' => $loginTab !== 'sms',
                        ])
                    >
                        <svg class="h-4 w-4 sm:h-5 sm:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                        <span>{{ __('filament-loginkit::filament-loginkit.sms_login_tab') }}</span>
                    </button>
                </div>
            </div>
        @endif



        <div class="space-y-6">
            @if ($emailEnabled && (!$smsEnabled || ($smsEnabled && $loginTab === 'email')))
                <div>
                    <form wire:submit="authenticate">
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
                                {{ __('filament-panels::auth/pages/login.form.actions.authenticate.label') }}
                            </x-filament::button>
                        </div>
                    </form>
                </div>
            @endif

            @if ($smsEnabled && (!$emailEnabled || ($emailEnabled && $loginTab === 'sms')))
                <div>
                    <div class="space-y-6">
                        <form wire:submit.prevent="sendSmsCode">
                            <div class="mt-5 mb-6">
                                {{ $this->smsPhoneForm }}
                            </div>

                            @if (config('filament-loginkit.turnstile.enabled'))
                                <div class="pt-2 pb-4 flex justify-center" wire:ignore>
                                    <div id="turnstile-widget-sms"></div>
                                </div>
                            @endif

                            <div class="grid gap-3 {{ $twilioEnabled ? 'grid-cols-1 md:grid-cols-2' : 'grid-cols-1' }}">
                                @if($twilioEnabled)
                                    <x-filament::button
                                        type="button"
                                        color="primary"
                                        class="w-full flex items-center justify-center"
                                        wire:click.prevent="sendWhatsappCode"
                                        :indicator="false"
                                        wire:loading.attr="disabled"
                                        wire:target="sendWhatsappCode"
                                    >
            <span class="inline-flex items-center justify-center gap-2">
                <svg class="h-4 w-5 shrink-0" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path
                        d="M6.014 8.00613C6.12827 7.1024 7.30277 5.87414 8.23488 6.01043L8.23339 6.00894C9.14051 6.18132 9.85859 7.74261 10.2635 8.44465C10.5504 8.95402 10.3641 9.4701 10.0965 9.68787C9.7355 9.97883 9.17099 10.3803 9.28943 10.7834C9.5 11.5 12 14 13.2296 14.7107C13.695 14.9797 14.0325 14.2702 14.3207 13.9067C14.5301 13.6271 15.0466 13.46 15.5548 13.736C16.3138 14.178 17.0288 14.6917 17.69 15.27C18.0202 15.546 18.0977 15.9539 17.8689 16.385C17.4659 17.1443 16.3003 18.1456 15.4542 17.9421C13.9764 17.5868 8 15.27 6.08033 8.55801C5.97237 8.24048 5.99955 8.12044 6.014 8.00613Z"/>
                    <path fill-rule="evenodd" clip-rule="evenodd"
                          d="M12 23C10.7764 23 10.0994 22.8687 9 22.5L6.89443 23.5528C5.56462 24.2177 4 23.2507 4 21.7639V19.5C1.84655 17.492 1 15.1767 1 12C1 5.92487 5.92487 1 12 1C18.0751 1 23 5.92487 23 12C23 18.0751 18.0751 23 12 23ZM6 18.6303L5.36395 18.0372C3.69087 16.4772 3 14.7331 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12C21 16.9706 16.9706 21 12 21C11.0143 21 10.552 20.911 9.63595 20.6038L8.84847 20.3397L6 21.7639V18.6303Z"/>
                </svg>
                <span class="whitespace-nowrap">
                    {{ __('filament-loginkit::filament-loginkit.sms.login_whatsapp') }}
                </span>
            </span>
                                    </x-filament::button>
                                @endif

                                <x-filament::button
                                    type="submit"
                                    color="primary"
                                    class="w-full flex items-center justify-center"
                                    wire:loading.attr="disabled"
                                    wire:target="sendSmsCode"
                                >
                                    <x-filament::loading-indicator
                                        class="h-4 w-4 mr-2"
                                        wire:loading
                                        wire:target="sendSmsCode"
                                    />
                                    <span class="inline-flex items-center justify-center gap-2">
            <svg class="h-4 w-5 shrink-0" fill="none" stroke="currentColor"
                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                 viewBox="0 0 32 32" aria-hidden="true">
                <line x1="10" y1="12" x2="19" y2="12"/>
                <line x1="10" y1="16" x2="14" y2="16"/>
                <path
                    d="M11,4c-4.4,0-8,3.6-8,8v12v5l0,0c3.7-3.2,8.4-5,13.3-5H21c4.4,0,8-3.6,8-8v-4c0-4.4-3.6-8-8-8H11z"/>
            </svg>
            <span class="whitespace-nowrap">
                {{ __('filament-loginkit::filament-loginkit.sms.login') }}
            </span>
        </span>
                                </x-filament::button>
                            </div>
                        </form>
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
