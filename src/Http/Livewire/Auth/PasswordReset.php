<?php

namespace AuroraWebSoftware\FilamentLoginKit\Http\Livewire\Auth;

use Filament\Facades\Filament;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class PasswordReset extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $layout = 'filament-loginkit::layouts.login';

    protected static string $view = 'filament-loginkit::auth.password-reset';

    public ?string $email = '';

    public ?string $password = '';

    public ?string $token = '';

    public function mount(): void
    {
        $this->email = request()->get('email');

        if (Filament::auth()->check()) {
            redirect()->intended(Filament::getCurrentPanel()?->getUrl() ?? config('fortify.home'));
        }

        if (session('status')) {
            Notification::make()
                ->title(session('status'))
                ->success()
                ->send();
        }
    }

    public function hasLogo(): bool
    {
        return false;
    }

    protected function getFormSchema(): array
    {
        return [
            TextInput::make('email')
                ->label('E-mail')
                ->afterStateHydrated(function (TextInput $component) {
                    $component->state($this->email);
                })
                ->disabled(),
            TextInput::make('password')
                ->extraInputAttributes(['name' => 'password'])
                ->label('Wachtwoord')
                ->password()
                ->required()
                ->rules(['confirmed'])
                ->autocomplete('new-password'),
            TextInput::make('password_confirmation')
                ->extraInputAttributes(['name' => 'password_confirmation'])
                ->label('Bevestig wachtwoord')
                ->password()
                ->autocomplete('new-password')
                ->required(),
            Hidden::make('email')
                ->extraAttributes(['name' => 'email'])
                ->afterStateHydrated(fn ($component) => $component->state(request()->get('email'))),
            Hidden::make('token')
                ->extraAttributes(['name' => 'token'])
                ->afterStateHydrated(fn ($component) => $component->state(request()->route('token'))),
        ];
    }
}
