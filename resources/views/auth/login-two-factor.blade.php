<x-filament-panels::page.simple>
    <h2 class="text-lg font-semibold text-gray-900 text-center dark:text-gray-100">
        {{ __('filament-loginkit::filament-loginkit.two_factor.heading') }}
    </h2>

    @if ($errors->any())
        <div class="mt-4 bg-red-50 text-red-700 p-4 rounded-lg">
            <p class="font-medium">
                {{ __('filament-loginkit::filament-loginkit.two_factor.invalid_code') }}
            </p>
        </div>
    @endif

    @error('data.code')
    <div class="mt-4 bg-red-50 text-red-700 p-4 rounded-lg">
        <p class="font-medium">{{ $message }}</p>
    </div>
    @enderror

    @if ($twoFactorType === 'email' || $twoFactorType === 'sms')
        <div class="flex justify-center" wire:poll.5s>
            {{ $this->resend }}
        </div>
    @endif

    <form wire:submit.prevent="submit" class="space-y-6 w-full max-w-xl mx-auto mt-2">
        @csrf

        {{ $this->form }}

        <x-filament::button type="submit" color="primary" class="w-full">
            {{ __('filament-loginkit::filament-loginkit.two_factor.login') }}
        </x-filament::button>
    </form>

{{--    <div class="fixed bottom-6 right-6">--}}
{{--        <form method="POST" action="{{ route('logout') }}">--}}
{{--            @csrf--}}
{{--            <button type="submit" class="text-sm underline font-medium text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-gray-100">--}}
{{--                {{ __('filament-loginkit::filament-loginkit.two_factor.logout') }}--}}
{{--            </button>--}}
{{--        </form>--}}
{{--    </div>--}}

    <script>
        document.addEventListener('livewire:initialized', () => {
            @this.
            on('resent', () => Livewire.dispatch('$refresh'))
        })
    </script>
</x-filament-panels::page.simple>
