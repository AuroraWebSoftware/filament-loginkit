<?php

namespace AuroraWebSoftware\FilamentLoginKit\Http\Livewire\Auth;

use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class RequestPasswordReset extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $layout = 'filament-loginkit::layouts.login';

    protected static string $view = 'filament-loginkit::auth.password-reset';

    public ?string $email = '';

    public function mount(): void
    {
        if (Filament::auth()->check()) {
            redirect()->intended(Filament::getCurrentPanel()?->getUrl() ?? config('fortify.home'));
        }

        if (session('status')) {
            Notification::make()
                ->title(session('status'))
                ->success()
                ->send();

            redirect()->route('login');
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
                ->extraInputAttributes(['name' => 'email'])
                ->label('E-mail')
                ->email()
                ->required()
                ->autocomplete(),
        ];
    }
}
