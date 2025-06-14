<?php

namespace AuroraWebSoftware\FilamentLoginKit\Http\Livewire\Auth;

use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\View\View;

class RequestPasswordReset extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $layout = 'filament.layouts.login';

    public ?string $email = '';

    public function mount(): void
    {
        dd(3);
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
                ->label('E-mailxxx')
                ->email()
                ->required()
                ->autocomplete(),
        ];
    }

    public function render(): View
    {
        dd(4);
        return view('filament-loginkit::auth.request-password-reset', $this->getViewData())
            ->layout($this->getLayout(), [
                'livewire' => $this,
                'maxContentWidth' => $this->getMaxContentWidth(),
                ...$this->getLayoutData(),
            ]);
    }
}
