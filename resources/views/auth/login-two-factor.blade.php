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

    <x-filament-panels::form wire:submit.prevent="submit" statePath="data">
        @csrf

        <input type="text" id="recovery_code" name="recovery_code" value="" style="display:none">

        {{ $this->form }}

        <x-filament::button type="submit" class="w-full" color="primary">
            {{ __('filament-loginkit::filament-loginkit.two_factor.login') }}
        </x-filament::button>
    </x-filament-panels::form>

    <script>
        document.addEventListener('livewire:initialized', () => {
            @this.
            on('resent', () => Livewire.dispatch('$refresh'))
        })
    </script>
</x-filament-panels::page.simple>
