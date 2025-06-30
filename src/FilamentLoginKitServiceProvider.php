<?php

namespace AuroraWebSoftware\FilamentLoginKit;

use AuroraWebSoftware\FilamentLoginKit\Commands\FilamentLoginKitCommand;
use AuroraWebSoftware\FilamentLoginKit\Http\Responses\LoginResponse;
use AuroraWebSoftware\FilamentLoginKit\Http\Responses\TwoFactorChallengeViewResponse;
use AuroraWebSoftware\FilamentLoginKit\Http\Responses\TwoFactorLoginResponse;
use AuroraWebSoftware\FilamentLoginKit\Testing\TestsFilamentLoginKit;
use Filament\Events\ServingFilament;
use Filament\Facades\Filament;
use Filament\Support\Assets\Asset;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentColor;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentLoginKitServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-loginkit';

    public static string $viewNamespace = 'filament-loginkit';

    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package->name(static::$name)
            ->hasCommands($this->getCommands());
        //            ->hasInstallCommand(function (InstallCommand $command) {
        //                $command
        //                    ->publishConfigFile()
        //                    ->publishMigrations()
        //                    ->askToRunMigrations()
        //                    ->askToStarRepoOnGitHub(':vendor_slug/:package_slug');
        //            });

        $configFileName = $package->shortName();

        if (file_exists($package->basePath("/../config/filament-loginkit.php"))) {
            $package->hasConfigFile('filament-loginkit');
        }

        if (file_exists($package->basePath('/../database/migrations'))) {
            $package->hasMigrations($this->getMigrations());
        }

        if (file_exists($package->basePath('/../resources/lang'))) {
            $package->hasTranslations();
        }

        if (file_exists($package->basePath('/../resources/views'))) {
            $package->hasViews(static::$viewNamespace);
        }
    }

    public function packageRegistered(): void
    {
        $this->forceFortifyConfig();
    }

    public function packageBooted(): void
    {
        // Asset Registration
        FilamentAsset::register(
            $this->getAssets(),
            $this->getAssetPackageName()
        );

        FilamentAsset::registerScriptData(
            $this->getScriptData(),
            $this->getAssetPackageName()
        );

        Filament::serving(function () {
            if ($panel = Filament::getCurrentPanel()) {
                $panelColor = $panel->getColors()['primary'] ?? Color::Amber;

                FilamentColor::register([
                    'default' => is_string($panelColor)
                        ? Color::hex($panelColor)
                        : $panelColor,

                    'primary' => is_string($panelColor)
                        ? Color::hex($panelColor)
                        : $panelColor,
                ]);
            }
        });


        // Icon Registration
        FilamentIcon::register($this->getIcons());

        $colors = Filament::getCurrentPanel()?->getColors();
        $color = isset($colors['primary'])
            ? (is_string($colors['primary']) ? Color::hex($colors['primary']) : $colors['primary'])
            : \Filament\Support\Colors\Color::Amber;

        FilamentColor::register([
            'default' => $color,
        ]);

        // Handle Stubs
        if (app()->runningInConsole()) {
            foreach (app(Filesystem::class)->files(__DIR__ . '/../stubs/') as $file) {
                $this->publishes([
                    $file->getRealPath() => base_path("stubs/filament-loginkit/{$file->getFilename()}"),
                ], 'filament-loginkit-stubs');
            }

            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

            $this->publishes([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'filament-loginkit-migrations');

            $this->publishes([
                __DIR__ . '/../config/loginkit-auth.php' => config_path('filament-loginkit.php'),
            ], 'filament-loginkit-config');

            $this->publishes([
                __DIR__ . '/../resources/lang' => lang_path('vendor/filament-loginkit'),
            ], 'filament-loginkit-lang');

            $this->publishes([
                __DIR__ . '/../resources/dist/filament-loginkit.css' => public_path('vendor/filament-loginkit/filament-loginkit.css'),
                __DIR__ . '/../resources/dist/filament-loginkit.js' => public_path('vendor/filament-loginkit/filament-loginkit.js'),
            ], 'filament-loginkit-assets');

        }

        // Testing
        Testable::mixin(new TestsFilamentLoginKit);

        $this->registerContractsAndComponents();

        $this->registerCustomRateLimiters();

        $this->overrideFortifyViews();

        Route::domain(config('filament.domain'))
            ->middleware(config('filament.middleware.base'))
            ->name('filament.')
            ->group(function () {
                /**
                 * We do not need to override logout response and logout path as:
                 * - logout response for both filament and fortify does
                 *    basically the same things except fortify handle for api calls
                 * - for api calls still can use POST fortify's /logout route
                 * - filament's logout route is at /filament/logout
                 */

                /**
                 * Redeclare filament.auth.login route as fortify override it
                 * This route name is used multiple places in filament.
                 */
                Route::prefix(config('filament.path'))->group(function () {
                    Route::get('/filament-login', fn() => Redirect::route('login'))
                        ->name('auth.login');
                });
            });

        \Illuminate\Support\Facades\Event::listen(
            \Laravel\Fortify\Events\TwoFactorAuthenticationChallenged::class,
            [\AuroraWebSoftware\FilamentLoginKit\Listeners\SendTwoFactorCodeListener::class, 'handle']
        );
        \Illuminate\Support\Facades\Event::listen(
            \Laravel\Fortify\Events\TwoFactorAuthenticationEnabled::class,
            [\AuroraWebSoftware\FilamentLoginKit\Listeners\SendTwoFactorCodeListener::class, 'handle']
        );
    }

    protected function forceFortifyConfig(): void
    {
        config([
            'filament.auth.pages.login' => config('filament-loginkit.login'),
            'fortify.prefix' => 'fortify',
            'fortify.views' => true,
            'fortify.home' => config('filament.home_url'),
            'forms.dark_mode' => config('filament.dark_mode'),
        ]);
    }

    protected function registerCustomRateLimiters(): void
    {
        $loginMaxRequests = config('filament-loginkit.rate_limits.login.max_requests');
        $loginPerMinutes = config('filament-loginkit.rate_limits.login.per_minutes');

        RateLimiter::for('login', function (Request $request) use ($loginMaxRequests, $loginPerMinutes) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())) . '|' . $request->ip());

            return Limit::perMinutes($loginPerMinutes, $loginMaxRequests)->by($throttleKey);
        });

        $twoFactorMaxRequests = config('filament-loginkit.rate_limits.two_factor.max_requests', 5);
        $twoFactorPerMinutes = config('filament-loginkit.rate_limits.two_factor.per_minutes', 1);

        RateLimiter::for('two-factor', function (Request $request) use ($twoFactorMaxRequests, $twoFactorPerMinutes) {
            return Limit::perMinutes($twoFactorPerMinutes, $twoFactorMaxRequests)
                ->by($request->session()->get('login.id'));
        });
    }

    protected function overrideFortifyViews(): void
    {
        Fortify::loginView(function () {
            return app()->call(config('filament.auth.pages.login'));
        });

        if (Features::enabled(Features::resetPasswords())) {
            Fortify::requestPasswordResetLinkView(function () {
                return app()->call(config('filament-loginkit.request_password_reset'));
            });

            Fortify::resetPasswordView(function ($request) {
                return app()->call(config('filament-loginkit.password_reset'));
            });
        }

        if (Features::enabled(Features::emailVerification())) {
            Fortify::verifyEmailView(function () {
                return view('filament-loginkit::auth.verify-email');
            });
        }

        Fortify::confirmPasswordView(function () {
            return app()->call(config('filament-loginkit.password_confirmation'));
        });

        if (Features::enabled(Features::twoFactorAuthentication())) {
            Fortify::twoFactorChallengeView(function () {
                return app()->call(config('filament-loginkit.challenge'));
            });
        }
    }

    protected function registerContractsAndComponents(): void
    {
        Livewire::component(
            'password-reset',
            config('filament-loginkit.password_reset')
        );
        Livewire::component(
            'request-password-reset',
            config('filament-loginkit.request_password_reset')
        );
        Livewire::component(
            'login-two-factor',
            config('filament-loginkit.challenge')
        );
        Livewire::component(
            'two-factor',
            config('filament-loginkit.two_factor_settings')
        );

        $this->app->singleton(LoginResponseContract::class, LoginResponse::class);
        $this->app->singleton(TwoFactorLoginResponseContract::class, TwoFactorLoginResponse::class);
        $this->app->singleton(TwoFactorChallengeViewResponse::class, TwoFactorChallengeViewResponse::class);
    }


    protected function getAssetPackageName(): ?string
    {
        return 'aurorawebsoftware/filament-loginkit';
    }

    /**
     * @return array<Asset>
     */
    protected function getAssets(): array
    {
        return [
            // AlpineComponent::make('skeleton', __DIR__ . '/../resources/dist/components/skeleton.js'),
            Css::make('filament-loginkit-styles', __DIR__ . '/../resources/dist/filament-loginkit.css'),
            Js::make('filament-loginkit-scripts', __DIR__ . '/../resources/dist/filament-loginkit.js'),
        ];
    }

    /**
     * @return array<class-string>
     */
    protected function getCommands(): array
    {
        return [
            FilamentLoginKitCommand::class,
        ];
    }

    /**
     * @return array<string>
     */
    protected function getIcons(): array
    {
        return [];
    }

    /**
     * @return array<string>
     */
    protected function getRoutes(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getScriptData(): array
    {
        return [];
    }

    /**
     * @return array<string>
     */
    protected function getMigrations(): array
    {
        return [
            'create_user_auth_table',
        ];
    }
}
