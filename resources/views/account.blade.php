<x-filament-panels::page>
    <div class="space-y-10 divide-y divide-gray-900/10 dark:divide-gray-700/10">

        {{-- ◼ 1) Kullanıcı Bilgileri --}}
        <div class="py-6">
            <x-filament-panels::form wire:submit="saveAccount">
                {{ $this->accountForm }}
                <div class="flex justify-end mt-6">
                    <x-filament::button type="submit">
                        <x-heroicon-m-check class="w-4 h-4 mr-2" />
                        Kaydet
                    </x-filament::button>
                </div>
            </x-filament-panels::form>
        </div>

        {{-- ◼ 2) Şifre Değiştir --}}
        <div class="py-6">
            <x-filament-panels::form wire:submit="changePassword">
                {{ $this->passwordForm }}
                <div class="flex justify-end mt-6">
                    <x-filament::button type="submit">
                        <x-heroicon-m-key class="w-4 h-4 mr-2" />
                        Şifreyi Değiştir
                    </x-filament::button>
                </div>
            </x-filament-panels::form>
        </div>

        {{-- ◼ 3) İki Faktörlü Kimlik Doğrulama --}}
        <div class="py-6">
            <div class="space-y-6">
                {{-- 2FA Durumu --}}
                <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            @if($this->is2faEnabled())
                                <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                                <div>
                                    <h3 class="font-medium text-green-800 dark:text-green-200">2FA Etkin</h3>
                                    <p class="text-sm text-green-600 dark:text-green-400">
                                        Hesabınız {{ \AuroraWebSoftware\FilamentLoginKit\Enums\TwoFactorType::from($user->two_factor_type)->getLabel() }} ile korunuyor
                                    </p>
                                </div>
                            @else
                                <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                                <div>
                                    <h3 class="font-medium text-red-800 dark:text-red-200">2FA Devre Dışı</h3>
                                    <p class="text-sm text-red-600 dark:text-red-400">
                                        Hesabınız sadece şifre ile korunuyor
                                    </p>
                                </div>
                            @endif
                        </div>
                        @if($this->is2faEnabled())
                            <x-filament::button
                                color="danger"
                                wire:click="disable2fa"
                                outlined
                                size="sm"
                                wire:confirm="2FA'yı devre dışı bırakmak istediğinizden emin misiniz?"
                            >
                                <x-heroicon-m-shield-exclamation class="w-4 h-4 mr-2" />
                                Devre Dışı Bırak
                            </x-filament::button>
                        @endif
                    </div>
                </div>

                {{-- 2FA Kurulum/Deaktive --}}
                <div class="space-y-6">
                    @if($this->is2faEnabled())
                        {{-- Aktif 2FA Deaktive Etme --}}
                        <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-6 border border-red-200 dark:border-red-800">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="font-semibold text-red-900 dark:text-red-100">
                                        2FA Devre Dışı Bırak
                                    </h3>
                                    <p class="text-sm text-red-700 dark:text-red-300 mt-1">
                                        {{ \AuroraWebSoftware\FilamentLoginKit\Enums\TwoFactorType::from($user->two_factor_type)->getLabel() }} yöntemi ile korunuyorsunuz
                                    </p>
                                </div>
                                <x-filament::button
                                    color="danger"
                                    wire:click="disable2fa"
                                    wire:confirm="2FA'yı devre dışı bırakmak istediğinizden emin misiniz? Bu işlem hesabınızın güvenliğini azaltacaktır."
                                >
                                    <x-heroicon-m-shield-exclamation class="w-4 h-4 mr-2" />
                                    Devre Dışı Bırak
                                </x-filament::button>
                            </div>
                        </div>
                    @else
                        {{-- 2FA Kurulum Formu --}}
                        <x-filament-panels::form wire:submit="enable2fa">
                            {{ $this->twoFactorForm }}

                            <div class="flex justify-end mt-6">
                                <x-filament::button
                                    type="submit"
                                    wire:loading.attr="disabled"
                                    wire:target="enable2fa"
                                >
                                    <x-heroicon-m-shield-check class="w-4 h-4 mr-2" />
                                    <span wire:loading.remove wire:target="enable2fa">2FA'yı Etkinleştir</span>
                                    <span wire:loading wire:target="enable2fa">Kurulum başlatılıyor...</span>
                                </x-filament::button>
                            </div>
                        </x-filament-panels::form>
                    @endif
                </div>

                {{-- Authenticator QR Kodu --}}
                @if($showingQrCode)
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 border-2 border-dashed border-gray-300 dark:border-gray-600">
                        <div class="text-center space-y-4">
                            <div class="flex justify-center">
                                <x-heroicon-o-qr-code class="w-8 h-8 text-gray-400" />
                            </div>
                            <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                QR Kodunu Tarayın
                            </h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400 max-w-md mx-auto">
                                Google Authenticator, Authy veya benzeri bir uygulama ile bu QR kodunu tarayın
                            </p>
                            <div class="flex justify-center bg-white p-4 rounded-lg max-w-sm mx-auto border">
                                {!! $user->twoFactorQrCodeSvg() !!}
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded-lg">
                                <p class="text-xs text-gray-500 dark:text-gray-400 font-mono">
                                    Manuel giriş: {{ decrypt($user->two_factor_secret) }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Kurtarma Kodları --}}
                @if($showingRecoveryCodes)
                    <div class="bg-amber-50 dark:bg-amber-900/20 rounded-lg p-6 border border-amber-200 dark:border-amber-800">
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <x-heroicon-o-key class="w-5 h-5 text-amber-600 dark:text-amber-400" />
                                    <h4 class="font-semibold text-amber-900 dark:text-amber-100">
                                        Kurtarma Kodları
                                    </h4>
                                </div>
                                <x-filament::button
                                    wire:click="regenerateRecoveryCodes"
                                    size="sm"
                                    outlined
                                    color="warning"
                                    wire:confirm="Yeni kurtarma kodları oluşturmak istediğinizden emin misiniz? Eski kodlar geçersiz olacak."
                                >
                                    <x-heroicon-m-arrow-path class="w-4 h-4 mr-2" />
                                    Yenile
                                </x-filament::button>
                            </div>
                            <p class="text-sm text-amber-700 dark:text-amber-300">
                                Bu kodları güvenli bir yerde saklayın. Telefonunuza erişiminizi kaybettiğinizde hesabınıza giriş yapabilirsiniz.
                            </p>
                            <div class="bg-white dark:bg-gray-800 p-4 rounded-lg border">
                                <div class="grid grid-cols-2 gap-3">
                                    @foreach($user->recoveryCodes() as $code)
                                        <div class="flex items-center gap-2 p-2 bg-gray-50 dark:bg-gray-700 rounded font-mono text-sm">
                                            <x-heroicon-m-key class="w-4 h-4 text-gray-400" />
                                            {{ $code }}
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="flex items-center gap-2 text-sm text-amber-700 dark:text-amber-300">
                                <x-heroicon-m-exclamation-triangle class="w-4 h-4" />
                                Her kod sadece bir kez kullanılabilir
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Kod Onay Formu --}}
                @if($showingConfirmation)
                    <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-6 border border-green-200 dark:border-green-800">
                        <div class="space-y-4">
                            <div class="flex items-center gap-2">
                                <x-heroicon-o-shield-check class="w-5 h-5 text-green-600 dark:text-green-400" />
                                <h4 class="font-semibold text-green-900 dark:text-green-100">
                                    Doğrulama Kodu Girin
                                </h4>
                            </div>
                            <p class="text-sm text-green-700 dark:text-green-300">
                                @if($user->two_factor_type === \AuroraWebSoftware\FilamentLoginKit\Enums\TwoFactorType::authenticator->value)
                                    Authenticator uygulamanızdan 6 haneli kodu girin
                                @else
                                    Size gönderilen 6 haneli doğrulama kodunu girin
                                @endif
                            </p>
                            <div class="max-w-xs">
                                <x-filament::input
                                    wire:model.defer="otpCode"
                                    type="text"
                                    placeholder="000000"
                                    maxlength="6"
                                    class="text-center font-mono text-lg tracking-widest"
                                />
                            </div>
                            <x-filament::button
                                wire:click="confirmTwoFactorAuthentication"
                                wire:loading.attr="disabled"
                                wire:target="confirmTwoFactorAuthentication"
                            >
                                <x-heroicon-m-check-circle class="w-4 h-4 mr-2" />
                                <span wire:loading.remove wire:target="confirmTwoFactorAuthentication">Onayla</span>
                                <span wire:loading wire:target="confirmTwoFactorAuthentication">Onaylanıyor...</span>
                            </x-filament::button>
                        </div>
                    </div>
                @endif
            </div>
        </div>

    </div>
</x-filament-panels::page>
