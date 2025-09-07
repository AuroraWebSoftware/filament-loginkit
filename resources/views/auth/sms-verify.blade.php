<div class="fi-simple-page mx-auto w-full max-w-md p-6 space-y-6">
    <div class="space-y-6">
        <div class="text-center">
            <div
                class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/20">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h3 class="mt-3 text-lg font-semibold">
                {{ __('filament-loginkit::filament-loginkit.sms.code_title') }}
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ __('filament-loginkit::filament-loginkit.sms.code_instruction', [
                    'number' => $phone_number,
                    'length' => config('filament-loginkit.sms.code_length')
                ]) }}
            </p>
        </div>

        <form wire:submit.prevent="verify" class="space-y-4">
            <x-filament::input.wrapper>
                <x-filament::input
                    type="text"
                    inputmode="numeric"
                    pattern="[0-9]{{ '{' . (int) config('filament-loginkit.sms.code_length') . '}' }}"
                    maxlength="{{ (int) config('filament-loginkit.sms.code_length') }}"
                    wire:model.defer="sms_code"
                    autocomplete="one-time-code"
                />
            </x-filament::input.wrapper>

            <div class="grid grid-cols-2 gap-3">
                <x-filament::button tag="a" href="{{ filament()->getLoginUrl() }}" type="button" color="gray" outlined
                                    class="w-full">
                    {{ __('filament-loginkit::filament-loginkit.sms.back') }}
                </x-filament::button>

                <x-filament::button type="submit" color="primary" class="w-full" wire:loading.attr="disabled">
                    <x-filament::loading-indicator class="h-4 w-4 mr-2" wire:loading/>
                    {{ __('filament-loginkit::filament-loginkit.sms.verify') }}
                </x-filament::button>
            </div>
        </form>

        <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
            <div class="flex items-center justify-center space-x-2 text-sm">
        <span class="text-gray-500 dark:text-gray-400">
            {{ __('filament-loginkit::filament-loginkit.sms.not_received') }}
        </span>

                {{-- WAIT (spinner + kalan süre) --}}
                <span id="resend-wait" class="flex items-center space-x-2">
            <span class="h-4 w-4 animate-spin rounded-full border-2 border-primary-600 border-t-transparent"></span>
            <span id="resend-left" class="text-primary-600 dark:text-primary-400 font-medium">60s</span>
        </span>

                {{-- BUTTON (başta gizli) --}}
                <x-filament::button
                    id="resend-btn"
                    type="button"
                    color="primary"
                    size="sm"
                    outlined
                    class="hidden"
                    wire:click="resend"
                    wire:loading.attr="disabled"
                >
                    <x-filament::loading-indicator class="h-3 w-3 mr-1" wire:loading wire:target="resend" />
                    {{ __('filament-loginkit::filament-loginkit.sms.resend') }}
                </x-filament::button>
            </div>

            <p class="mt-2 text-xs text-center text-gray-400 dark:text-gray-500">
                {{ __('filament-loginkit::filament-loginkit.sms.code_expires', [
                    'minutes' => config('filament-loginkit.sms.code_ttl')
                ]) }}
            </p>
        </div>
    </div>


    @push('scripts')
        <script>
            (function () {
                let intervalId = null;

                function qs(id) { return document.getElementById(id); }
                function show(el) { el && el.classList.remove('hidden'); }
                function hide(el) { el && el.classList.add('hidden'); }

                function startCooldown(seconds) {
                    const waitEl = qs('resend-wait');
                    const leftEl = qs('resend-left');
                    const btnEl  = qs('resend-btn');


                    show(waitEl);
                    hide(btnEl);

                    clearCooldown();
                    let left = parseInt(seconds, 10) || 60;
                    if (leftEl) leftEl.textContent = left + 's';

                    intervalId = setInterval(() => {
                        left--;
                        if (leftEl) leftEl.textContent = (left > 0 ? left : 0) + 's';

                        if (left <= 0) {
                            clearCooldown();
                            hide(waitEl);
                            show(btnEl);
                        }
                    }, 1000);
                }

                function clearCooldown() {
                    if (intervalId) {
                        clearInterval(intervalId);
                        intervalId = null;
                    }
                }

                document.addEventListener('livewire:load', function () {
                    const initial = {{ (int) ($countdown ?? 0) }};
                    if (initial > 0) startCooldown(initial);
                    else {

                    }
                });

                window.addEventListener('start-countdown', function (e) {
                    const secs = (e.detail && e.detail.seconds) ? e.detail.seconds : 60;
                    startCooldown(secs);
                });

                window.addEventListener('beforeunload', clearCooldown);
            })();
        </script>
    @endpush

</div>
