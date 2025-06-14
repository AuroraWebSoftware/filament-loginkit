<?php

namespace AuroraWebSoftware\FilamentLoginKit\Pages;

use AuroraWebSoftware\FilamentLoginKit\Enums\TwoFactorType;
use Carbon\Carbon;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Laravel\Fortify\Features;

class Account extends Page implements HasForms
{
    use InteractsWithForms;

    /* ---------------- Filament meta ---------------- */
    protected static string $view = 'filament-loginkit::account';

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $navigationLabel = 'Hesap';

    protected static ?string $title = 'Hesap Ayarları';

    /* ---------------- Form state dizileri ---------- */
    public array $account = [];

    public array $password = [];

    public array $twoFactor = [];

    /* ---------------- Diğer değişkenler ------------- */
    public $user;

    public bool $showingQrCode = false;

    public bool $showingRecoveryCodes = false;

    public bool $showingConfirmation = false;

    public string $otpCode = '';

    /* ---------------- Mount ------------------------- */
    public function mount(): void
    {
        $this->user = Auth::user();

        $this->account = [
            'name' => $this->user->name,
            'email' => $this->user->email,
            'phone_number' => $this->user->phone_number,
        ];

        $this->twoFactor = [
            'twoFactorType' => $this->user->two_factor_type ? TwoFactorType::from($this->user->two_factor_type)->value : null,
        ];
    }

    /* ---------------- Çok-lu form kayıt ------------- */
    protected function getForms(): array
    {
        return ['accountForm', 'passwordForm', 'twoFactorForm'];
    }

    /* ---------------- Form şemaları ----------------- */
    public function accountForm(Form $form): Form
    {
        return $form->schema([
            Section::make('Kullanıcı Bilgileri')->schema([
                Grid::make(3)->schema([
                    TextInput::make('name')->label('İsim')->required(),
                    TextInput::make('email')->label('E-posta')->email()->required(),
                    TextInput::make('phone_number')->label('Telefon'),
                ]),
            ]),
        ])->statePath('account');
    }

    public function passwordForm(Form $form): Form
    {
        return $form->schema([
            Section::make('Şifre Değiştir')->schema([
                TextInput::make('current_password')
                    ->label('Mevcut Şifre')->password()->required()->currentPassword(),
                TextInput::make('new_password')
                    ->label('Yeni Şifre')->password()->required()->rules([Password::defaults()]),
                TextInput::make('new_password_confirmation')
                    ->label('Yeni Şifre (Tekrar)')->password()->required()->same('new_password'),
            ]),
        ])->statePath('password');
    }

    public function twoFactorForm(Form $form): Form
    {
        return $form->schema([
            Section::make('İki Faktörlü Kimlik Doğrulama')
                ->description('Hesabınızın güvenliğini artırmak için iki faktörlü kimlik doğrulamayı etkinleştirin.')
                ->schema([
                    Radio::make('twoFactorType')
                        ->label('2FA Yöntemi Seçin')
                        ->options($this->resolve2faOptions())
                        ->descriptions($this->resolve2faDescriptions())
                        ->inline()
                        ->disabled($this->is2faEnabled())
                        ->required(),
                ]),
        ])->statePath('twoFactor');
    }

    protected function resolve2faOptions(): array
    {
        $enabledOptions = config('filament-loginkit.options', []);
        $options = [];

        foreach ($enabledOptions as $option) {
            if ($option instanceof TwoFactorType) {
                $options[$option->value] = $option->getLabel();
            }
        }

        return $options;
    }

    protected function resolve2faDescriptions(): array
    {
        return [
            TwoFactorType::sms->value => 'Telefon numaranıza SMS ile kod gönderilir',
            TwoFactorType::email->value => 'E-posta adresinize kod gönderilir',
            TwoFactorType::authenticator->value => 'Google Authenticator veya benzeri uygulama kullanılır',
        ];
    }

    /* ---------------- Blade'de rahat kullanım ------- */
    public function getCurrentTwoFactorTypeProperty(): ?string
    {
        return $this->twoFactor['twoFactorType'] ?? null;
    }

    public function is2faEnabled(): bool
    {
        return $this->user->two_factor_confirmed_at !== null;
    }

    public function canSendCode(): bool
    {
        return false; // Artık manuel kod gönderme yok
    }

    /* ============ Hesap & şifre işlemleri ============ */
    public function saveAccount(): void
    {
        $this->user->update($this->account);
        Notification::make()->title('Bilgiler güncellendi')->success()->send();
    }

    public function changePassword(): void
    {
        $data = $this->password;

        if (! Hash::check($data['current_password'], $this->user->password)) {
            Notification::make()->title('Mevcut şifre yanlış')->danger()->send();

            return;
        }

        $this->user->update(['password' => Hash::make($data['new_password'])]);
        $this->password = [];
        Notification::make()->title('Şifre değiştirildi')->success()->send();
    }

    /* ============ 2FA – kod gönderimi ================ */
    private function sendCodeForType(string $type): void
    {
        // 6 haneli kod
        $code = (string) rand(100000, 999999);

        $this->user->forceFill([
            'two_factor_code' => Hash::make($code),
            'two_factor_expires_at' => now()->addMinutes(10),
        ])->save();

        // Notification'ları ayır
        if ($type === TwoFactorType::sms->value) {
            $this->user->notify(new \AuroraWebSoftware\FilamentLoginKit\Notifications\SmsLoginNotification($code));
        } elseif ($type === TwoFactorType::email->value) {
            $this->user->notify(new \AuroraWebSoftware\FilamentLoginKit\Notifications\SendOTP($code));
        }

        $this->showingConfirmation = true;

        $methodName = TwoFactorType::from($type)->getLabel();
        Notification::make()
            ->title("Doğrulama kodu {$methodName} ile gönderildi")
            ->success()
            ->send();
    }

    /* ============ 2FA – etkinleştir ================ */
    public function enable2fa(): void
    {
        $type = $this->currentTwoFactorType;

        if (! $type) {
            Notification::make()->title('Lütfen bir 2FA yöntemi seçin')->warning()->send();

            return;
        }

        if ($this->is2faEnabled()) {
            Notification::make()->title('2FA zaten etkin')->warning()->send();

            return;
        }

        // Türü kaydet
        $this->user->update(['two_factor_type' => $type]);

        if ($type === TwoFactorType::authenticator->value) {
            // Fortify aksiyonu → secret + recovery
            $this->enableTwoFactorAuthentication(app(EnableTwoFactorAuthentication::class));
        } else {
            // sms / email ⇒ otomatik kod gönder
            $this->sendCodeForType($type);
        }

        $methodName = TwoFactorType::from($type)->getLabel();
        Notification::make()
            ->title("2FA ({$methodName}) kurulumu başlatıldı")
            ->success()
            ->send();
    }

    /* ============ 2FA – doğrulama & tamamla ========= */
    public function confirmTwoFactorAuthentication(ConfirmTwoFactorAuthentication $fortify): void
    {
        if (! $this->otpCode) {
            Notification::make()->title('Lütfen doğrulama kodunu girin')->warning()->send();

            return;
        }

        $type = $this->user->two_factor_type;

        if ($type === TwoFactorType::authenticator->value) {
            try {
                // Fortify kod doğrulaması
                $fortify($this->user, $this->otpCode);
                $this->showingQrCode = false;
            } catch (\Exception $e) {
                Notification::make()->title('Authenticator kodu hatalı')->danger()->send();

                return;
            }
        } else {
            // sms / email kodu
            if (
                ! $this->user->two_factor_code ||
                ! Hash::check($this->otpCode, $this->user->two_factor_code) ||
                Carbon::parse($this->user->two_factor_expires_at)->isPast()
            ) {
                Notification::make()->title('Kod hatalı veya süresi geçti')->danger()->send();

                return;
            }
        }

        // Ortak başarı yolu
        $this->user->forceFill([
            'two_factor_confirmed_at' => now(),
            'two_factor_code' => null,
            'two_factor_expires_at' => null,
        ])->save();

        $this->reset('otpCode');
        $this->showingConfirmation = false;
        $this->showingRecoveryCodes = ($type === TwoFactorType::authenticator->value);

        $methodName = TwoFactorType::from($type)->getLabel();
        Notification::make()
            ->title("2FA ({$methodName}) başarıyla onaylandı")
            ->success()
            ->send();
    }

    /* ============ 2FA – devre dışı bırak ============ */
    public function disable2fa(DisableTwoFactorAuthentication $fortify): void
    {
        if (! $this->is2faEnabled()) {
            Notification::make()->title('2FA zaten devre dışı')->warning()->send();

            return;
        }

        // Authenticator kullanıldıysa secret + recovery temizle
        if ($this->user->two_factor_type === TwoFactorType::authenticator->value) {
            $fortify($this->user);
        }

        $this->user->forceFill([
            'two_factor_type' => null,
            'two_factor_code' => null,
            'two_factor_expires_at' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        $this->reset(['showingQrCode', 'showingRecoveryCodes', 'showingConfirmation']);

        // Form state'ini de güncelle
        $this->twoFactor['twoFactorType'] = null;

        Notification::make()->title('2FA devre dışı bırakıldı')->success()->send();
    }

    /* ============ Kurtarma kodları yenile ============ */
    public function regenerateRecoveryCodes(GenerateNewRecoveryCodes $gen): void
    {
        if ($this->user->two_factor_type !== TwoFactorType::authenticator->value) {
            Notification::make()->title('Kurtarma kodları sadece Authenticator için kullanılır')->warning()->send();

            return;
        }

        $gen($this->user);
        $this->showingRecoveryCodes = true;
        Notification::make()->title('Yeni kurtarma kodları oluşturuldu')->success()->send();
    }

    /* ============ Fortify yardımcıları (QR) ========== */
    protected function enableTwoFactorAuthentication(EnableTwoFactorAuthentication $en): void
    {
        $en($this->user);

        $this->user->update(['two_factor_type' => TwoFactorType::authenticator->value]);

        $this->showingQrCode = true;
        $this->showingConfirmation = Features::optionEnabled(
            Features::twoFactorAuthentication(),
            'confirm'
        );

        if (! $this->showingConfirmation) {
            $this->showingRecoveryCodes = true;
        }
    }
}
