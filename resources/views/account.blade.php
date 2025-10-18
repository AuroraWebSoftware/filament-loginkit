<x-filament-panels::page>
    @if(!$this->is2faEnabled())
        @if (session()->get('login_type') === 'email')
            <div
                class="mb-6 rounded-lg border border-yellow-300 bg-yellow-50 dark:bg-yellow-950/40 px-5 py-4 flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-3">
                    <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-yellow-500 shrink-0"/>
                    <div>
                        <div class="font-semibold text-yellow-900 dark:text-yellow-200">
                            {{ __('filament-loginkit::filament-loginkit.please_select_2fa') }}
                        </div>
                        <div class="text-sm text-yellow-800 dark:text-yellow-300 mt-1">
                            {{ __('filament-loginkit::filament-loginkit.please_select_2fa_description') }}
                        </div>
                    </div>
                </div>
                <a href="#two-factor-section"
                   class="ml-6 inline-flex items-center gap-2 px-4 py-2 rounded-md bg-yellow-400 text-yellow-900 font-medium hover:bg-yellow-500 transition focus:outline-none focus:ring-2 focus:ring-yellow-500">
                    <x-heroicon-m-shield-check class="w-4 h-4"/>
                    {{ __('filament-loginkit::filament-loginkit.two_factor.enable') }}
                </a>
            </div>
        @endif
    @endif

    <div class="space-y-6">

        <form wire:submit.prevent="saveAccount" class="space-y-6">
            {{ $this->accountForm }}
            <div class="flex justify-end mt-4">
                <x-filament::button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="saveAccount"
                    class="flex! items-center!"
                    :disabled="!$canEditAccount"
                >
            <span wire:loading.remove wire:target="saveAccount" class="flex items-center">
                <x-heroicon-m-check class="w-4 h-4 mr-1"/>
                {{ __('filament-loginkit::filament-loginkit.account.save') }}
            </span>
                    <span wire:loading wire:target="saveAccount" class="flex items-center">
                <svg class="animate-spin w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                            stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                          d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ __('filament-loginkit::filament-loginkit.account.saving') }}
            </span>
                </x-filament::button>
            </div>
        </form>


        <div>
            <form wire:submit.prevent="changePassword" class="space-y-6">
                {{ $this->passwordForm }}
                <div class="flex justify-end mt-4">
                    <x-filament::button
                        type="submit"
                        wire:loading.attr="disabled"
                        wire:target="changePassword"
                        class="flex items-center"
                    >
                        <span wire:loading.remove wire:target="changePassword" class="flex items-center">
                            <x-heroicon-m-key class="w-4 h-4 mr-1"/>
                            {{ __('filament-loginkit::filament-loginkit.account.change_password') }}
                        </span>
                        <span wire:loading wire:target="changePassword" class="flex items-center">
                            <svg class="animate-spin w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                      d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ __('filament-loginkit::filament-loginkit.account.changing_password') }}
                        </span>
                    </x-filament::button>
                </div>
            </form>
        </div>

        @if (session()->get('login_type') === 'email')
            <div id="two-factor-section">
                <x-filament::section>
                    <x-slot name="heading">
                        {{ __('filament-loginkit::filament-loginkit.two_factor.title') }}
                    </x-slot>

                    <x-slot name="description">
                        {{ __('filament-loginkit::filament-loginkit.two_factor.description') }}
                    </x-slot>

                    <div class="space-y-4">
                        {{-- 2FA Durumu --}}
                        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    @if($this->is2faEnabled())
                                        <div class="flex items-center gap-2 text-success-600 dark:text-success-400">
                                            <x-heroicon-s-shield-check class="w-5 h-5"/>
                                            <div>
                                                <div class="font-medium">
                                                    {{ __('filament-loginkit::filament-loginkit.two_factor.enabled') }}
                                                </div>
                                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                                    {{ __('filament-loginkit::filament-loginkit.two_factor.protected_with') }}
                                                    : {{ $this->getCurrentMethodName() }}
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <div class="flex items-center gap-2 text-danger-600 dark:text-danger-400">
                                            <x-heroicon-s-shield-exclamation class="w-5 h-5"/>
                                            <div>
                                                <div class="font-medium">
                                                    {{ __('filament-loginkit::filament-loginkit.two_factor.disabled') }}
                                                </div>
                                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                                    {{ __('filament-loginkit::filament-loginkit.two_factor.password_only') }}
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <div class="flex gap-2">
                                    @if($this->is2faEnabled())
                                        @if($user->two_factor_type === \AuroraWebSoftware\FilamentLoginKit\Enums\TwoFactorType::authenticator->value)
                                            <x-filament::button
                                                color="warning"
                                                wire:click="regenerateRecoveryCodes"
                                                outlined
                                                size="sm"
                                                wire:confirm="{{ __('filament-loginkit::filament-loginkit.two_factor.regenerate_codes_confirm') }}"
                                            >
                                            <span class="flex items-center gap-2">
                                                <x-heroicon-m-key class="w-4 h-4"/>
                                                {{ __('filament-loginkit::filament-loginkit.two_factor.regenerate_codes') }}
                                            </span>
                                            </x-filament::button>
                                        @endif

                                        <x-filament::button
                                            color="danger"
                                            wire:click="disable2fa"
                                            outlined
                                            size="sm"
                                            wire:confirm="{{ __('filament-loginkit::filament-loginkit.two_factor.disable_confirm') }}"
                                        >
                                        <span class="flex items-center gap-2">
                                            <x-heroicon-m-shield-exclamation class="w-4 h-4"/>
                                            {{ __('filament-loginkit::filament-loginkit.two_factor.disable') }}
                                        </span>
                                        </x-filament::button>
                                    @else
                                        <x-filament::button
                                            wire:click="start2faSetup"
                                            wire:loading.attr="disabled"
                                        >
                                        <span class="flex items-center gap-2">
                                            <x-heroicon-m-shield-check class="w-4 h-4"/>
                                            {{ __('filament-loginkit::filament-loginkit.two_factor.enable') }}
                                        </span>
                                        </x-filament::button>
                                    @endif

                                </div>
                            </div>
                        </div>

                        @if($show2faSetup)
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg">
                                <div
                                    class="bg-primary-50 dark:bg-primary-900/20 px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                                    <h3 class="font-medium text-primary-900 dark:text-primary-100">
                                        {{ __('filament-loginkit::filament-loginkit.two_factor.setup_title') }}
                                    </h3>
                                    <x-filament::button
                                        color="gray"
                                        wire:click="cancel2faSetup"
                                        size="sm"
                                        outlined
                                    >
                                    <span class="flex items-center">
                                        <x-heroicon-m-x-mark class="w-5 h-5"/>
                                    </span>
                                    </x-filament::button>

                                </div>

                                <div class="p-4 space-y-4">
                                    @if(!$showConfirmation)
                                        <div>
                                            <h4 class="font-medium mb-3">
                                                {{ __('filament-loginkit::filament-loginkit.two_factor.select_method') }}
                                            </h4>


                                            <form wire:submit.prevent="proceed2faSetup" class="space-y-6">
                                                {{ $this->twoFactorForm }}
                                                <div class="flex justify-end mt-4">
                                                    <x-filament::button type="submit" wire:loading.attr="disabled"
                                                                        wire:target="proceed2faSetup"
                                                                        class="flex! items-center!">
                                                    <span wire:loading.remove wire:target="proceed2faSetup"
                                                          class="flex items-center">
                                                        <x-heroicon-m-arrow-right class="w-4 h-4 mr-1"/>
                                                        {{ __('filament-loginkit::filament-loginkit.common.continue') }}
                                                    </span>
                                                        <span wire:loading wire:target="proceed2faSetup"
                                                              class="flex items-center">
                                                        <svg class="animate-spin w-4 h-4 mr-1" fill="none"
                                                             viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10"
                                                                    stroke="currentColor" stroke-width="4"></circle>
                                                            <path class="opacity-75" fill="currentColor"
                                                                  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                        </svg>
                                                        {{ __('filament-loginkit::filament-loginkit.common.preparing') }}
                                                    </span>
                                                    </x-filament::button>
                                                </div>
                                            </form>
                                        </div>
                                    @endif

                                    @if($showQrCode)
                                        <div>
                                            <h4 class="font-medium mb-3">
                                                {{ __('filament-loginkit::filament-loginkit.two_factor.scan_qr_code') }}
                                            </h4>

                                            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-6">
                                                <div class="text-center space-y-6">
                                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                                        {{ __('filament-loginkit::filament-loginkit.two_factor.scan_with_app') }}
                                                    </p>

                                                    <div class="flex justify-center">
                                                        <div class="bg-white p-4 rounded-lg border shadow-sm">
                                                            {!! $user->twoFactorQrCodeSvg() !!}
                                                        </div>
                                                    </div>

                                                    <div class="max-w-md mx-auto">
                                                        <label
                                                            class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2 text-center">
                                                            {{ __('filament-loginkit::filament-loginkit.two_factor.manual_key') }}
                                                        </label>
                                                        <div
                                                            class="font-mono text-sm bg-white dark:bg-gray-700 border rounded-lg px-4 py-3 break-all text-center select-all">
                                                            {{ decrypt($user->two_factor_secret) }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    @if($showConfirmation)
                                        <div>
                                            <h4 class="font-medium mb-3 text-center">
                                                {{ __('filament-loginkit::filament-loginkit.two_factor.enter_code') }}
                                            </h4>

                                            <div class="space-y-4 text-center p-4">
                                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                                    @if($user->two_factor_type === \AuroraWebSoftware\FilamentLoginKit\Enums\TwoFactorType::authenticator->value)
                                                        {{ __('filament-loginkit::filament-loginkit.two_factor.enter_app_code') }}
                                                    @else
                                                        {{ __('filament-loginkit::filament-loginkit.two_factor.enter_sent_code') }}
                                                    @endif
                                                </p>

                                                <div class="flex justify-center">
                                                    <div class="max-w-xs">
                                                        <x-filament::input
                                                            wire:model.live="otpCode"
                                                            type="text"
                                                            placeholder="000000"
                                                            maxlength="6"
                                                            class="text-center font-mono text-lg"
                                                            wire:keydown.enter="confirmTwoFactorAuthentication"
                                                        />
                                                    </div>
                                                </div>

                                                <x-filament::button
                                                    wire:click="confirmTwoFactorAuthentication"
                                                    wire:loading.attr="disabled"
                                                    wire:target="confirmTwoFactorAuthentication"
                                                    class="flex items-center"
                                                >
                                                <span wire:loading.remove wire:target="confirmTwoFactorAuthentication"
                                                      class="flex items-center">
                                                    <x-heroicon-m-check-circle class="w-4 h-4 mr-1"/>
                                                    {{ __('filament-loginkit::filament-loginkit.two_factor.confirm_and_enable') }}
                                                </span>
                                                    <span wire:loading wire:target="confirmTwoFactorAuthentication"
                                                          class="flex items-center">
                                                    <svg class="animate-spin w-4 h-4 mr-1" fill="none"
                                                         viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                                                stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor"
                                                              d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                    </svg>
                                                    {{ __('filament-loginkit::filament-loginkit.two_factor.confirming') }}
                                                </span>
                                                </x-filament::button>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        @if($showRecoveryCodes)
                            <div class="border border-warning-200 dark:border-warning-700 rounded-lg">
                                <div
                                    class="bg-warning-50 dark:bg-warning-900/20 px-4 py-3 border-b border-warning-200 dark:border-warning-700 flex items-center justify-between">
                                    <h3 class="font-medium text-warning-900 dark:text-warning-100">
                                        {{ __('filament-loginkit::filament-loginkit.two_factor.recovery_codes') }}
                                    </h3>
                                    <x-filament::button
                                        wire:click="downloadRecoveryCodes"
                                        color="primary"
                                        size="sm"
                                    >
                                        <span class="flex items-center gap-2">
                                            <x-heroicon-m-arrow-down-tray class="w-4 h-4"/>
                                            {{ __('filament-loginkit::filament-loginkit.two_factor.download_and_close') }}
                                        </span>
                                    </x-filament::button>

                                </div>

                                <div class="p-4">
                                    <div
                                        class="bg-danger-50 dark:bg-danger-900/20 border border-danger-200 dark:border-danger-700 rounded-lg p-3 mb-4">
                                        <div class="flex items-start gap-2">
                                            <x-heroicon-s-exclamation-triangle
                                                class="w-5 h-5 text-danger-600 dark:text-danger-400 shrink-0 mt-0.5"/>
                                            <div class="text-sm text-danger-700 dark:text-danger-300">
                                                {{ __('filament-loginkit::filament-loginkit.two_factor.recovery_codes_warning') }}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-2">
                                        @foreach($user->recoveryCodes() as $index => $code)
                                            <div
                                                class="bg-gray-50 dark:bg-gray-800 border rounded-lg p-3 font-mono text-sm cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                                                onclick="copyToClipboard('{{ $code }}', this)">
                                                <span class="text-gray-500 dark:text-gray-400">{{ $index + 1 }}.</span>
                                                {{ $code }}
                                            </div>
                                        @endforeach
                                    </div>

                                    <div
                                        class="mt-4 bg-primary-50 dark:bg-primary-900/20 border border-primary-200 dark:border-primary-700 rounded-lg p-3">
                                        <div class="text-sm text-primary-700 dark:text-primary-300">
                                            {{ __('filament-loginkit::filament-loginkit.two_factor.recovery_codes_info') }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </x-filament::section>
            </div>
            @php
                $panel = filament()->getCurrentPanel();
                $basePath = $panel ? '/' . ltrim($panel->getPath(), '/') : '/';
            @endphp

            <div class="mt-10 flex justify-center">
                <a
                    href="{{ $basePath }}"
                    class="inline-flex items-center gap-2 px-6 py-3 rounded-md bg-primary-600 bg-primary-50 dark:bg-primary-900/20 font-medium hover:bg-primary-700 transition focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                >
                    <x-heroicon-m-home class="w-5 h-5"/>
                    {{ __('filament-loginkit::filament-loginkit.two_factor.return_home_page') }}
                </a>
            </div>
        @endif
    </div>

    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('download-recovery-codes', (data) => {
                const element = document.createElement('a');
                const file = new Blob([data[0].content], {type: 'text/plain'});
                element.href = URL.createObjectURL(file);
                element.download = data[0].filename;
                document.body.appendChild(element);
                element.click();
                document.body.removeChild(element);
            });
        });

        function copyToClipboard(text, element) {
            navigator.clipboard.writeText(text).then(() => {
                const originalBg = element.className;
                element.classList.add('bg-success-100', 'dark:bg-success-900/20', 'border-success-300', 'dark:border-success-700');

                setTimeout(() => {
                    element.className = originalBg;
                }, 1000);
            });
        }
    </script>
</x-filament-panels::page>
