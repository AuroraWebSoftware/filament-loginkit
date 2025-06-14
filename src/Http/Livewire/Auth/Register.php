<?php

namespace AuroraWebSoftware\FilamentLoginKit\Http\Livewire\Auth;

use Filament\Pages\Auth\Register as BaseRegister;
use AuroraWebSoftware\FilamentLoginKit\Http\Responses\RegistrationResponse;

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
