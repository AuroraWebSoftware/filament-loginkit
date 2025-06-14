<x-filament-panels::page.simple>
    <h2 class="mt-5 text-lg font-semibold text-gray-900 text-center dark:text-gray-100">
        {{ __('Authenticate with your code') }}
    </h2>

    {{-- Genel hata mesajı --}}
    @if ($errors->any())
        <div class="mt-4 bg-red-50 text-red-700 p-4 rounded-lg">
            <p class="font-medium">{{ __('Invalid authentication code.') }}</p>
        </div>
    @endif

    {{-- Livewire doğrulama hatası --}}
    @error('data.code')
    <div class="mt-4 bg-red-50 text-red-700 p-4 rounded-lg">
        <p class="font-medium">{{ $message }}</p>
    </div>
    @enderror

    {{-- “Tekrar Gönder” yalnızca e-posta / SMS’te görünür --}}
    @if ($twoFactorType === 'email' || $twoFactorType === 'sms')
        <div class="my-4" wire:poll.5s>
            {{ $this->resend }}
        </div>
    @endif

    <x-filament-panels::form wire:submit.prevent="submit" statePath="data" class="space-y-8">
        @csrf

        {{-- Recovery Code (Gizli) --}}
        <input type="text" id="recovery_code" name="recovery_code" value="" style="display: none" />

        {{ $this->form }}

        <x-filament::button type="submit" class="w-full" color="primary">
            {{ __('Login') }}
        </x-filament::button>
    </x-filament-panels::form>

    <script>
        document.addEventListener('livewire:initialized', () => {
            @this.on('resent', () => Livewire.dispatch('$refresh'));
        });
    </script>
</x-filament-panels::page.simple>
