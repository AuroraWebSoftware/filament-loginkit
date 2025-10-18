<?php

namespace AuroraWebSoftware\FilamentLoginKit;

use AuroraWebSoftware\FilamentLoginKit\Http\Middleware\ForceTwoFactor;
use AuroraWebSoftware\FilamentLoginKit\Pages\Account;
use Closure;
use Filament\Contracts\Plugin;
use Filament\Navigation\MenuItem;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;

class FilamentLoginKitPlugin implements Plugin
{
    use EvaluatesClosures;

    private Closure | bool | null $forced = false;

    private Closure | bool $showInUserMenu = true;

    public function getId(): string
    {
        return 'filament-loginkit';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->login(config('filament-loginkit.login'))
            ->pages([
                config('filament-loginkit.two_factor_settings'),
                //                config('filament-loginkit.challenge'),
            ]);

        if ($this->isForced()) {
            $middlewareMethod = config('filament-two-factor-auth.enabled_features.multi_tenancy') ? 'tenantMiddleware' : 'authMiddleware';
            $panel->$middlewareMethod([
                ForceTwoFactor::class,
            ]);
        }

        if (! config('filament-loginkit.enabled_features.multi_tenancy') && $this->shouldShowInUserMenu()) {
            $panel->userMenuItems([
                'two-factor-authentication' => MenuItem::make()
                    ->icon('heroicon-o-lock-closed')
                    ->label(__('filament-loginkit::filament-loginkit.navigation.my_account'))
                    ->url(fn (): string => Account::getUrl()),
            ]);
        }

        if (config('filament-loginkit.enabled_features.register')) {
            $panel->registration(config('filament-loginkit.register'));
        }

        if (config('filament-loginkit.reset_password_enabled')) {
            $panel->passwordReset(config('filament-loginkit.request_password_reset'));
        }

    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }

    public function forced(Closure | bool | null $forced = true, bool $withTenancy = false): self
    {
        $this->forced = $forced;

        return $this;
    }

    public function isForced(): Closure | bool | null
    {
        return $this->evaluate($this->forced);
    }

    public function showInUserMenu(Closure | bool $showInUserMenu = true): self
    {
        $this->showInUserMenu = $showInUserMenu;

        return $this;
    }

    public function shouldShowInUserMenu(): bool
    {
        return $this->evaluate($this->showInUserMenu);
    }
}
