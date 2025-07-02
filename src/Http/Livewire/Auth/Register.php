<?php

namespace AuroraWebSoftware\FilamentLoginKit\Http\Livewire\Auth;

use AuroraWebSoftware\FilamentLoginKit\Http\Responses\RegistrationResponse;
use Filament\Pages\Auth\Register as BaseRegister;

class Register extends BaseRegister
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament-loginkit::auth.register';

    public function register(): ?RegistrationResponse
    {
        parent::register();

        return app(RegistrationResponse::class);
    }
}
